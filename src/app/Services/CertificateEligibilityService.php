<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\Participant;

class CertificateEligibilityService
{
    /**
     * Syarat sertifikat peserta: Pre-Test, Post-Test, dan link s.id/microsite.
     * Bila terpenuhi sertifikat langsung terbit tanpa cek eligibility oleh Admin RTIK.
     *
     * @return array{eligible: bool, notes: array<int, string>}
     */
    public function check(Participant $participant, ?LearningEvent $event): array
    {
        if (! $event) {
            return ['eligible' => false, 'notes' => ['Event belum ditentukan.']];
        }

        $notes = [];

        if (! $this->hasAttempt($participant, $event, 'pre')) {
            $notes[] = 'Pre-test belum selesai.';
        }

        if (! $this->hasAttempt($participant, $event, 'post')) {
            $notes[] = 'Post-test belum selesai.';
        }

        $hasMicrosite = MicrositePractice::query()
            ->where('participant_id', $participant->id)
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->exists();

        if (! $hasMicrosite) {
            $notes[] = 'Link s.id / microsite belum diisi.';
        }

        return [
            'eligible' => $notes === [],
            'notes' => $notes,
        ];
    }

    /** Ambil (atau buat) sertifikat peserta untuk event, lalu perbarui status eligibility & terbitnya. */
    public function ensureCertificate(Participant $participant, LearningEvent $event): Certificate
    {
        $certificate = Certificate::query()->firstOrCreate(
            [
                'learning_event_id' => $event->id,
                'participant_id' => $participant->id,
            ],
            [
                'certificate_template_id' => $event->certificate_template_id,
                'number' => 'DSC/' . now()->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT),
                'status' => 'pending',
                'eligibility_status' => 'pending',
            ],
        );

        return $this->updateCertificate($certificate);
    }

    public function updateCertificate(Certificate $certificate): Certificate
    {
        $result = $this->check($certificate->participant, $certificate->learningEvent);

        $updates = [
            'eligibility_status' => $result['eligible'] ? 'eligible' : 'blocked',
            'eligibility_checked_at' => now(),
            'eligibility_notes' => implode("\n", $result['notes']),
        ];

        if (! $certificate->certificate_template_id) {
            $updates['certificate_template_id'] = $certificate->learningEvent?->certificate_template_id
                ?: CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->value('id');
        }

        // Syarat terpenuhi: sertifikat langsung terbit (kecuali sudah dicabut).
        if ($result['eligible'] && $certificate->status === 'pending') {
            $updates['status'] = 'issued';
            $updates['issued_at'] = now();
        }

        // Hanya menulis bila status/catatan berubah (halaman peserta memanggil ini setiap kali dibuka).
        $changed = collect($updates)
            ->except('eligibility_checked_at')
            ->contains(fn ($value, string $key) => $certificate->getAttribute($key) != $value);

        if ($changed || ! $certificate->eligibility_checked_at) {
            $certificate->update($updates);
        }

        return $certificate;
    }

    private function hasAttempt(Participant $participant, LearningEvent $event, string $type): bool
    {
        return AssessmentAttempt::query()
            ->where('participant_id', $participant->id)
            ->whereHas('assessment', fn ($query) => $query
                ->where('learning_event_id', $event->id)
                ->where('type', $type))
            ->exists();
    }
}
