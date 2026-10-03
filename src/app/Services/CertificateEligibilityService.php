<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\Participant;

class CertificateEligibilityService
{
    /**
     * Bukti dukung event yang wajib disetujui RTIK Pusat sebelum sertifikat peserta eligible.
     * Bukti follow IG dan join WAG dicek per peserta lewat approval peserta, sedangkan
     * praktik microsite dicek per peserta lewat link s.id.
     */
    public const REQUIRED_EVENT_EVIDENCE = [
        'absensi_basah' => 'Absensi basah',
        'foto_sesi' => 'Foto kegiatan',
        'video_slogan' => 'Video slogan',
    ];

    /** @return array{eligible: bool, notes: array<int, string>} */
    public function check(Participant $participant, ?LearningEvent $event): array
    {
        if (! $event) {
            return ['eligible' => false, 'notes' => ['Event belum ditentukan.']];
        }

        $notes = [];

        if (! $participant->isApprovedForEvent($event)) {
            $notes[] = 'Bukti follow Instagram dan checklist join WAG belum approved.';
        }

        if (! $this->hasAttempt($participant, $event, 'pre')) {
            $notes[] = 'Pre-test belum selesai.';
        }

        if (! $this->hasAttempt($participant, $event, 'post')) {
            $notes[] = 'Post-test belum selesai.';
        }

        $quizIds = Assessment::query()
            ->where('learning_event_id', $event->id)
            ->where('type', 'quiz')
            ->where('is_open', true)
            ->pluck('id');

        if ($quizIds->isNotEmpty()) {
            $completedQuiz = AssessmentAttempt::query()
                ->where('participant_id', $participant->id)
                ->whereIn('assessment_id', $quizIds)
                ->distinct('assessment_id')
                ->count('assessment_id');

            if ($completedQuiz < $quizIds->count()) {
                $notes[] = 'Kuis modul belum lengkap.';
            }
        }

        $hasMicrosite = MicrositePractice::query()
            ->where('participant_id', $participant->id)
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->exists();

        if (! $hasMicrosite) {
            $notes[] = 'Link s.id / microsite belum diisi.';
        }

        $approvedEvidenceTypes = Evidence::query()
            ->where('learning_event_id', $event->id)
            ->whereIn('type', array_keys(self::REQUIRED_EVENT_EVIDENCE))
            ->where('status', 'approved')
            ->distinct()
            ->pluck('type');

        $missingEvidence = collect(self::REQUIRED_EVENT_EVIDENCE)
            ->reject(fn (string $label, string $type) => $approvedEvidenceTypes->contains($type));

        if ($missingEvidence->isNotEmpty()) {
            $notes[] = 'Bukti dukung kegiatan belum disetujui RTIK Pusat: ' . $missingEvidence->implode(', ') . '.';
        }

        return [
            'eligible' => $notes === [],
            'notes' => $notes,
        ];
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

        $certificate->update($updates);

        return $certificate->refresh();
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
