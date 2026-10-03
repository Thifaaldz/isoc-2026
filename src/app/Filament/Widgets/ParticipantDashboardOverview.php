<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Pages\EventRundown;
use App\Filament\Pages\ParticipantLearning;
use App\Filament\Pages\ParticipantTests;
use App\Models\AssessmentAttempt;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Services\EventEnrollmentService;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

class ParticipantDashboardOverview extends Widget
{
    use WithFileUploads;

    public const INSTAGRAM_URL = 'https://www.instagram.com/isoc.jkt/';

    protected static string $view = 'filament.widgets.participant-dashboard-overview';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -2;

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $instagramEvidence = null;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        $this->selectedEventId = $this->resolveSelectedEvent()?->id;
    }

    public function updatedSelectedEventId(): void
    {
        $this->selectedEventId = $this->resolveSelectedEvent()?->id;
    }

    protected function getViewData(): array
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return [
                'event' => null,
                'events' => collect(),
                'school' => null,
                'wag' => null,
                'stats' => $this->emptyStats(),
                'approval' => $this->emptyApproval(),
                'learningUrl' => ParticipantLearning::getUrl(),
                'testsUrl' => ParticipantTests::getUrl(),
                'rundownUrl' => EventRundown::getUrl(),
            ];
        }

        $events = LearningEvent::query()
            ->with(['school.wagGroups', 'meetings.materials', 'assessments'])
            ->where('is_published', true)
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->orderByDesc('starts_at')
            ->get();

        $event = $events->firstWhere('id', (int) $this->selectedEventId) ?? $events->first();
        $this->selectedEventId = $event?->id;

        if (! $event) {
            return [
                'event' => null,
                'events' => $events,
                'school' => $participant->school,
                'wag' => null,
                'stats' => $this->emptyStats(),
                'approval' => $this->emptyApproval(),
                'learningUrl' => ParticipantLearning::getUrl(),
                'testsUrl' => ParticipantTests::getUrl(),
                'rundownUrl' => EventRundown::getUrl(),
            ];
        }

        $pivot = $event->participants()
            ->where('participants.id', $participant->id)
            ->first()
            ?->pivot;
        $approval = [
            'admin' => $pivot?->admin_approval_status ?? 'approved',
            'tutor' => $pivot?->tutor_approval_status ?? 'approved',
            'notes' => $pivot?->approval_notes,
        ];
        $approval['approved'] = $approval['admin'] === 'approved'
            || $approval['tutor'] === 'approved';
        $approval['requires_approval'] = ! $approval['approved'];
        $approval['status'] = $approval['approved'] ? 'approved' : 'pending';

        if (! $approval['approved']) {
            return [
                'event' => $event,
                'events' => $events,
                'school' => $event->school,
                'wag' => app(EventEnrollmentService::class)->wagGroupFor($event),
                'proof' => [
                    'follow_complete' => Evidence::query()
                        ->where('learning_event_id', $event->id)
                        ->where('uploaded_by', $participant->user_id)
                        ->where('type', 'follow_ig')
                        ->whereNotNull('file_path')
                        ->exists(),
                    'wag_complete' => (bool) $participant->joined_wag,
                ],
                'stats' => $this->emptyStats(),
                'approval' => $approval,
                'learningUrl' => '#',
                'testsUrl' => '#',
                'rundownUrl' => '#',
            ];
        }

        $assessmentIds = $event->assessments->pluck('id');
        $attemptedAssessmentIds = AssessmentAttempt::query()
            ->where('participant_id', $participant->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->pluck('assessment_id')
            ->unique();

        $preIds = $event->assessments->where('type', 'pre')->pluck('id');
        $postIds = $event->assessments->where('type', 'post')->pluck('id');
        $quizIds = $event->assessments->where('type', 'quiz')->pluck('id');
        $quizDone = $quizIds->intersect($attemptedAssessmentIds)->count();
        $preDone = $preIds->intersect($attemptedAssessmentIds)->isNotEmpty();
        $postDone = $postIds->intersect($attemptedAssessmentIds)->isNotEmpty();
        $totalSteps = max(1, 1 + $quizIds->count() + 1);
        $doneSteps = ($preDone ? 1 : 0) + $quizDone + ($postDone ? 1 : 0);

        $wag = $event->school?->wagGroups
            ->sortByDesc(fn ($group) => $group->status === 'active')
            ->first();

        return [
            'event' => $event,
            'events' => $events,
            'school' => $event->school,
            'wag' => $wag,
            'stats' => [
                'meetings' => $event->meetings->where('is_published', true)->count(),
                'materials' => $event->meetings->flatMap(fn ($meeting) => $meeting->materials)->where('is_published', true)->count(),
                'pre_done' => $preDone,
                'quiz_done' => $quizDone,
                'quiz_total' => $quizIds->count(),
                'post_done' => $postDone,
                'progress' => (int) round(($doneSteps / $totalSteps) * 100),
            ],
            'approval' => $approval,
            'learningUrl' => ParticipantLearning::getUrl(['event' => $event->id]),
            'testsUrl' => ParticipantTests::getUrl(['event' => $event->id]),
            'rundownUrl' => EventRundown::getUrl(['event' => $event->id]),
        ];
    }

    private function resolveSelectedEvent(): ?LearningEvent
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return null;
        }

        $events = LearningEvent::query()
            ->where('is_published', true)
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->orderByDesc('starts_at')
            ->get();

        return $events->firstWhere('id', (int) $this->selectedEventId) ?? $events->first();
    }

    private function emptyStats(): array
    {
        return [
            'meetings' => 0,
            'materials' => 0,
            'pre_done' => false,
            'quiz_done' => 0,
            'quiz_total' => 0,
            'post_done' => false,
            'progress' => 0,
        ];
    }

    private function emptyApproval(): array
    {
        return [
            'requires_approval' => false,
            'approved' => true,
            'admin' => 'approved',
            'tutor' => 'approved',
            'status' => 'approved',
            'notes' => null,
        ];
    }

    public function submitInstagramEvidence(): void
    {
        $participant = auth()->user()?->participant;
        $event = $this->resolveSelectedEvent();

        if (! $participant || ! $event) {
            return;
        }

        $this->validate([
            'instagramEvidence' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ], [], [
            'instagramEvidence' => 'bukti follow Instagram',
        ]);

        $approved = app(EventEnrollmentService::class)->submitInstagramEvidence(
            $participant,
            $event,
            $this->instagramEvidence->store('evidences', 'public'),
        );

        $this->reset('instagramEvidence');

        Notification::make()
            ->title('Bukti follow Instagram tersimpan')
            ->body($approved ? 'Bukti dukung lengkap. Modul, tes, dan rundown sudah terbuka.' : 'Centang join WhatsApp Group untuk membuka modul dan tes.')
            ->success()
            ->send();
    }

    public function toggleJoinedWag(): void
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return;
        }

        $participant->update(['joined_wag' => ! $participant->joined_wag]);

        LearningEvent::query()
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->get()
            ->each(fn (LearningEvent $event) => $participant->syncInitialApprovalForEvent($event));

        Notification::make()
            ->title('Status WhatsApp Group diperbarui')
            ->success()
            ->send();
    }
}
