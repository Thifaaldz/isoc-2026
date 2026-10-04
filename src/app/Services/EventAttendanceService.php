<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\Participant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Absensi peserta dengan kode absensi event (offline maupun online). */
class EventAttendanceService
{
    public const MODES = ['offline' => 'Offline (hadir di lokasi)', 'online' => 'Online (via Zoom/webinar)'];

    /** Huruf/angka yang mudah dibaca (tanpa 0/O dan 1/I). */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function generateCode(LearningEvent $event): string
    {
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        $event->update(['attendance_code' => $code]);

        return $code;
    }

    /** @return array<string, string> Mode absensi yang boleh dipilih untuk tipe event ini. */
    public function modesFor(LearningEvent $event): array
    {
        return match ($event->event_type ?: 'offline') {
            'webinar' => ['online' => self::MODES['online']],
            'hybrid' => self::MODES,
            default => ['offline' => self::MODES['offline']],
        };
    }

    /** Absensi dibuka pada hari pelaksanaan event (tanggal mulai s.d. tanggal selesai). */
    public function isOpen(LearningEvent $event): bool
    {
        if (! $event->starts_at) {
            return false;
        }

        $endsAt = ($event->ends_at ?? $event->starts_at)->copy()->endOfDay();

        return now()->between($event->starts_at->copy()->startOfDay(), $endsAt);
    }

    public function attendanceFor(Participant $participant, LearningEvent $event): ?Attendance
    {
        return Attendance::query()
            ->where('learning_event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->first();
    }

    /** @throws ValidationException */
    public function checkIn(Participant $participant, LearningEvent $event, string $code, string $mode): Attendance
    {
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$field => $message]);

        if (! $participant->learningEvents()->whereKey($event->id)->exists()) {
            $fail('eventId', 'Anda tidak terdaftar pada event ini.');
        }

        if (! $participant->isApprovedForEvent($event)) {
            $fail('eventId', 'Pendaftaran Anda di event ini belum disetujui admin/tutor.');
        }

        if (! $this->isOpen($event)) {
            $fail('eventId', 'Absensi hanya dibuka pada hari pelaksanaan event (' . $event->starts_at?->translatedFormat('d F Y') . ').');
        }

        if (! array_key_exists($mode, $this->modesFor($event))) {
            $fail('mode', 'Jenis kehadiran tidak sesuai dengan tipe event.');
        }

        $expected = (string) $event->attendance_code;

        if ($expected === '' || ! hash_equals(Str::upper($expected), Str::upper(trim($code)))) {
            $fail('code', $expected === '' ? 'Kode absensi event ini belum dibuat oleh tutor/fasilitator.' : 'Kode absensi salah.');
        }

        if ($existing = $this->attendanceFor($participant, $event)) {
            return $existing;
        }

        return Attendance::query()->create([
            'learning_event_id' => $event->id,
            'participant_id' => $participant->id,
            'status' => 'hadir',
            'mode' => $mode,
            'method' => 'kode',
            'checked_in_at' => now(),
            'recorded_by' => $participant->user_id,
        ]);
    }
}
