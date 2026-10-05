<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\Participant;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Absensi peserta dengan kode absensi event (offline maupun online).
 * Kode dibuat otomatis oleh sistem pada hari pelaksanaan dan langsung tampil di dashboard peserta.
 */
class EventAttendanceService
{
    public const MODES = ['offline' => 'Offline (hadir di lokasi)', 'online' => 'Online (via Zoom/webinar)'];

    /** Huruf/angka yang mudah dibaca (tanpa 0/O dan 1/I). */
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function generateCode(LearningEvent $event): string
    {
        $code = '';

        for ($i = 0; $i < 6; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
        }

        $event->update(['attendance_code' => $code]);

        return $code;
    }

    /** Kode absensi event: dibuat otomatis saat pertama dibutuhkan pada hari H; null di luar hari pelaksanaan. */
    public function codeFor(LearningEvent $event): ?string
    {
        if (! $this->isOpen($event)) {
            return null;
        }

        return $event->attendance_code ?: $this->generateCode($event);
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

    /** Peserta sudah tercatat hadir di event ini (syarat membuka post-test). */
    public function hasCheckedIn(Participant $participant, LearningEvent $event): bool
    {
        return Attendance::query()
            ->where('learning_event_id', $event->id)
            ->where('participant_id', $participant->id)
            ->where('status', 'hadir')
            ->exists();
    }

    /**
     * Admin/tutor mencatat hadir banyak peserta sekaligus (method "manual").
     * Peserta yang sudah punya catatan absensi di event ini (hadir/izin/sakit/alpa) tidak diubah.
     *
     * @param  iterable<int>  $participantIds  kosong = semua peserta terdaftar di event
     * @return array{created: int, skipped: int}
     */
    public function markPresent(LearningEvent $event, iterable $participantIds = [], ?string $mode = null, ?int $recordedBy = null): array
    {
        $registered = $event->participants()->pluck('participants.id');
        $targets = collect($participantIds)->map(fn ($id) => (int) $id)->whenEmpty(fn () => $registered)->intersect($registered)->unique();
        $existing = Attendance::query()
            ->where('learning_event_id', $event->id)
            ->whereIn('participant_id', $targets)
            ->pluck('participant_id');
        $mode = array_key_exists((string) $mode, $this->modesFor($event)) ? $mode : array_key_first($this->modesFor($event));
        $now = now();

        $rows = $targets->diff($existing)->map(fn (int $participantId) => [
            'learning_event_id' => $event->id,
            'participant_id' => $participantId,
            'status' => 'hadir',
            'mode' => $mode,
            'method' => 'manual',
            'checked_in_at' => $now,
            'recorded_by' => $recordedBy,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values();

        $rows->chunk(500)->each(fn ($chunk) => Attendance::query()->insert($chunk->all()));

        return ['created' => $rows->count(), 'skipped' => $existing->count()];
    }

    /**
     * Tanpa $code, kode hari ini diisi otomatis oleh sistem (kode yang sama yang tampil di dashboard peserta).
     *
     * @throws ValidationException
     */
    public function checkIn(Participant $participant, LearningEvent $event, string $mode, ?string $code = null): Attendance
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

        $expected = (string) $this->codeFor($event);

        if ($code !== null && ! hash_equals(Str::upper($expected), Str::upper(trim($code)))) {
            $fail('code', 'Kode absensi salah.');
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
