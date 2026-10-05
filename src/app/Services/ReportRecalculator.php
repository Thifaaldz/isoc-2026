<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\LearningEvent;

/**
 * "Hitung ulang" laporan kegiatan (Admin RTIK Pusat): perbarui data turunan yang tersimpan sebagai snapshot
 * agar preview laporan sesuai data terbaru. Data asli (nilai, absensi, microsite) tidak diubah.
 */
class ReportRecalculator
{
    public function __construct(
        private CertificateEligibilityService $certificates,
        private SystemProofGenerator $proofs,
    ) {}

    /** @return array{indicators: int, certificates_issued: int, certificates_total: int, proofs: array<int, string>} */
    public function recalculate(LearningEvent $event): array
    {
        // 1. Indikator laporan (identifikasi ancaman & self-efficacy) dari skor pre/post-test yang belum terisi.
        $indicators = 0;

        AssessmentAttempt::query()
            ->whereHas('assessment', fn ($query) => $query->where('learning_event_id', $event->id)->whereIn('type', AssessmentAttempt::INDICATOR_TYPES))
            ->whereNotNull('score')
            ->where(fn ($query) => $query->whereNull('threat_identification')->orWhereNull('self_efficacy'))
            ->get()
            ->each(function (AssessmentAttempt $attempt) use (&$indicators): void {
                $attempt->save(); // hook saving mengisi kolom yang masih kosong
                $indicators++;
            });

        // 2. Sertifikat: terbitkan untuk semua peserta yang sudah memenuhi syarat (tanpa menunggu peserta membuka dashboard).
        $issuedBefore = Certificate::query()->where('learning_event_id', $event->id)->where('status', 'issued')->count();
        $event->participants()->get()->each(fn ($participant) => $this->certificates->ensureCertificate($participant, $event));
        $issuedAfter = Certificate::query()->where('learning_event_id', $event->id)->where('status', 'issued')->count();

        // 3. PDF bukti sistem (daftar hadir / rekap microsite) yang sudah pernah dibuat: buat ulang dengan data terbaru.
        $proofs = [];

        foreach (SystemProofGenerator::KINDS as $kind => $config) {
            if ($event->proofMode($kind) === 'system' && $this->proofs->current($event, $kind)) {
                $this->proofs->generate($event, $kind);
                $proofs[] = $config['label'];
            }
        }

        return [
            'indicators' => $indicators,
            'certificates_issued' => $issuedAfter - $issuedBefore,
            'certificates_total' => $issuedAfter,
            'proofs' => $proofs,
        ];
    }
}
