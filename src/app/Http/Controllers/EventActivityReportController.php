<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Evidence;
use App\Models\LearningEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EventActivityReportController extends Controller
{
    public function preview(LearningEvent $event): Response
    {
        abort_unless($this->canAccess($event), 403);

        return $this->pdf($event)->stream($this->filename($event));
    }

    public function download(LearningEvent $event): Response
    {
        abort_unless($this->canAccess($event), 403);

        return $this->pdf($event)->download($this->filename($event));
    }

    private function canAccess(LearningEvent $event): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return match ($user->role) {
            UserRole::SuperAdmin => true,
            UserRole::Admin => $event->created_by === $user->id
                || in_array((int) $event->school_id, \App\Filament\Resources\LearningEventResource::managedSchoolIds(), true),
            UserRole::Tutor => $event->tutors()->where('tutors.user_id', $user->id)->exists(),
            default => false,
        };
    }

    private function pdf(LearningEvent $event)
    {
        $event->load([
            'school',
            'participants.user',
            'tutors.user',
            'meetings.materials',
            'assessments',
            'evidences',
        ]);

        $assessmentIds = $event->assessments->pluck('id');
        $attempts = AssessmentAttempt::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->get();

        $averageScore = function (string $type) use ($event, $attempts): ?float {
            $ids = $event->assessments->where('type', $type)->pluck('id');

            if ($ids->isEmpty()) {
                return null;
            }

            $scores = $attempts->whereIn('assessment_id', $ids)->pluck('score')->filter(fn ($score) => $score !== null);

            return $scores->isEmpty() ? null : round((float) $scores->avg(), 2);
        };

        $assessmentIdsByType = fn (string $type) => $event->assessments->where('type', $type)->pluck('id');
        $scoreFor = function (int $participantId, string $type) use ($attempts, $assessmentIdsByType): ?float {
            $scores = $attempts
                ->where('participant_id', $participantId)
                ->whereIn('assessment_id', $assessmentIdsByType($type))
                ->pluck('score')
                ->filter(fn ($score) => $score !== null);

            return $scores->isEmpty() ? null : round((float) $scores->avg(), 2);
        };

        $completionFor = function (string $type) use ($attempts, $assessmentIdsByType): int {
            return $attempts
                ->whereIn('assessment_id', $assessmentIdsByType($type))
                ->pluck('participant_id')
                ->unique()
                ->count();
        };

        $participantScores = $event->participants
            ->sortBy(fn ($participant) => $participant->user?->name)
            ->values()
            ->map(fn ($participant) => [
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '-',
                'identity' => $participant->nis ?: '-',
                'grade' => $participant->grade ?: '-',
                'pre' => $scoreFor($participant->id, 'pre'),
                'post' => $scoreFor($participant->id, 'post'),
                'quiz' => $scoreFor($participant->id, 'quiz'),
                'joined_wag' => (bool) $participant->joined_wag,
                'followed_instagram' => (bool) $participant->followed_instagram,
            ]);

        $senaLogo = public_path('images/sena-logo.png');

        return Pdf::loadView('reports.event-activity', [
            'event' => $event,
            'school' => $event->school,
            'participants' => $event->participants,
            'tutors' => $event->tutors,
            'meetings' => $event->meetings->sortBy('order'),
            'evidences' => $event->evidences->sortBy('type'),
            'evidenceTypes' => Evidence::TYPES,
            'preAverage' => $averageScore('pre'),
            'postAverage' => $averageScore('post'),
            'quizAverage' => $averageScore('quiz'),
            'preCompleted' => $completionFor('pre'),
            'postCompleted' => $completionFor('post'),
            'quizCompleted' => $completionFor('quiz'),
            'participantScores' => $participantScores,
            'evidencePreviewSrc' => file_exists($senaLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($senaLogo)) : null,
            'attempts' => $attempts,
            'preTotal' => Assessment::query()->where('learning_event_id', $event->id)->where('type', 'pre')->count(),
            'postTotal' => Assessment::query()->where('learning_event_id', $event->id)->where('type', 'post')->count(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);
    }

    private function filename(LearningEvent $event): string
    {
        return 'laporan-kegiatan-' . Str::slug($event->title) . '.pdf';
    }
}
