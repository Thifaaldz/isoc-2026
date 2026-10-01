<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\LearningEvent;
use App\Models\Participant;

class CertificateEligibilityService
{
    /** @return array{eligible: bool, notes: array<int, string>} */
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

        return [
            'eligible' => $notes === [],
            'notes' => $notes,
        ];
    }

    public function updateCertificate(Certificate $certificate): Certificate
    {
        $result = $this->check($certificate->participant, $certificate->learningEvent);

        $certificate->update([
            'eligibility_status' => $result['eligible'] ? 'eligible' : 'blocked',
            'eligibility_checked_at' => now(),
            'eligibility_notes' => implode("\n", $result['notes']),
        ]);

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
