<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Bukti dukung yang dibuat otomatis dari data sistem (pilihan "Generate dari sistem" di Laporan Final):
 *  - attendance: PDF daftar hadir dari peserta yang memasukkan kode absensi (bukti absensi basah).
 *  - microsite: PDF rekap link microsite s.id peserta (bukti hasil microsite).
 * PDF disimpan sebagai bukti dukung yang langsung disetujui karena datanya berasal dari sistem.
 */
class SystemProofGenerator
{
    public const KINDS = [
        'attendance' => ['type' => 'absensi_basah', 'prefix' => 'evidences/daftar-hadir-sistem-', 'column' => 'attendance_proof_mode', 'view' => 'attendance.system-pdf', 'label' => 'Daftar hadir'],
        'microsite' => ['type' => 'praktik_microsite', 'prefix' => 'evidences/microsite-sistem-', 'column' => 'microsite_proof_mode', 'view' => 'reports.microsite-system-pdf', 'label' => 'Rekap microsite'],
    ];

    public static function path(string $kind, LearningEvent $event): string
    {
        return self::KINDS[$kind]['prefix'] . $event->id . '.pdf';
    }

    public function generate(LearningEvent $event, string $kind): Evidence
    {
        $event->loadMissing(['school', 'tutors.user', 'participants.user', 'creator']);
        $data = $this->data($event, $kind);
        $path = self::path($kind, $event);

        Storage::disk('public')->put($path, Pdf::loadView(self::KINDS[$kind]['view'], ['event' => $event, ...$data])->setPaper('a4', 'portrait')->output());

        return Evidence::query()->updateOrCreate(
            ['learning_event_id' => $event->id, 'type' => self::KINDS[$kind]['type'], 'file_path' => $path],
            [
                'school_id' => $event->school_id,
                'status' => 'approved',
                'uploaded_by' => auth()->id(),
                'verified_at' => now(),
                'review_notes' => 'Dibuat otomatis oleh sistem (' . $data['summary'] . ').',
            ],
        );
    }

    /** @return array<string, mixed> */
    private function data(LearningEvent $event, string $kind): array
    {
        if ($kind === 'attendance') {
            $attendances = $event->checkedInAttendances()->get();

            return ['attendances' => $attendances, 'registered' => $event->participants->count(), 'summary' => $attendances->count() . ' peserta hadir'];
        }

        $microsites = $this->microsites($event);

        return ['microsites' => $microsites, 'registered' => $event->participants->count(), 'summary' => $microsites->count() . ' link microsite'];
    }

    /** Link microsite terbaru tiap peserta event. */
    public function microsites(LearningEvent $event)
    {
        return MicrositePractice::query()
            ->with('participant.user')
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->latest()
            ->get()
            ->unique('participant_id')
            ->sortBy(fn (MicrositePractice $practice) => $practice->participant?->user?->name)
            ->values();
    }

    public function current(LearningEvent $event, string $kind): ?Evidence
    {
        return Evidence::query()
            ->where('learning_event_id', $event->id)
            ->where('type', self::KINDS[$kind]['type'])
            ->where('file_path', self::path($kind, $event))
            ->first();
    }

    /** Hapus PDF sistem saat admin beralih ke upload manual, agar tidak terhitung sebagai bukti manual. */
    public function remove(LearningEvent $event, string $kind): void
    {
        if ($evidence = $this->current($event, $kind)) {
            Storage::disk('public')->delete($evidence->file_path);
            $evidence->delete();
        }
    }

    public function url(Evidence $evidence): string
    {
        return Storage::disk('public')->url($evidence->file_path) . '?v=' . $evidence->updated_at?->timestamp;
    }
}
