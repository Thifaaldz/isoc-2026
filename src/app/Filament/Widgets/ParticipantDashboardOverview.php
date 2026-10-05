<?php

namespace App\Filament\Widgets;

use App\Services\MicrositeLinkChecker;
use App\Rules\ReachableMicrositeUrl;
use App\Enums\UserRole;
use App\Filament\Pages\EventRundown;
use App\Filament\Pages\ParticipantLearning;
use App\Filament\Pages\ParticipantTests;
use App\Models\AssessmentAttempt;
use App\Models\MicrositePractice;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Services\CertificateEligibilityService;
use App\Services\EventEnrollmentService;
use Filament\Widgets\Widget;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
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

    public ?string $micrositeUrl = null;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        $this->selectedEventId = $this->resolveSelectedEvent()?->id;
        $this->micrositeUrl = MicrositeLinkChecker::sidPath($this->currentMicrosite()?->sid_url);
    }

    public function updatedSelectedEventId(): void
    {
        $this->selectedEventId = $this->resolveSelectedEvent()?->id;
        $this->micrositeUrl = MicrositeLinkChecker::sidPath($this->currentMicrosite()?->sid_url);
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
                'certificateUrl' => null,
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
                'certificateUrl' => null,
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
                'certificateUrl' => null,
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
        $bestScore = fn ($ids) => $ids->isEmpty() ? null : AssessmentAttempt::query()
            ->where('participant_id', $participant->id)
            ->whereIn('assessment_id', $ids)
            ->max('score');
        // Langkah menuju sertifikat: Pre-Test, kuis modul (bila ada), Post-Test, dan link s.id/microsite.
        $micrositeDone = MicrositePractice::query()
            ->where('participant_id', $participant->id)
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->exists();
        $totalSteps = max(1, 1 + $quizIds->count() + 1 + 1);
        $doneSteps = ($preDone ? 1 : 0) + $quizDone + ($postDone ? 1 : 0) + ($micrositeDone ? 1 : 0);

        // Semua bukti oke (pre-test, kuis modul, post-test, microsite valid): sertifikat terbit dan tampil di dashboard.
        $certificateUrl = null;

        if ($doneSteps >= $totalSteps) {
            $certificate = app(CertificateEligibilityService::class)->ensureCertificate($participant, $event);
            $certificateUrl = $certificate->isIssued() ? route('certificates.preview-pdf', $certificate) : null;
        }

        // Grup WhatsApp dibuat Admin ISOC per lokasi event.
        $wag = app(EventEnrollmentService::class)->wagGroupFor($event);

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
                'pre_available' => $preIds->isNotEmpty(),
                'post_available' => $postIds->isNotEmpty(),
                'pre_score' => $bestScore($preIds),
                'post_score' => $bestScore($postIds),
                // Post-test terbuka setelah pre-test dan semua kuis modul selesai (sama dengan halaman Tes).
                'post_unlocked' => $preDone && $quizDone >= $quizIds->count(),
                ...$this->postRetakeStats($event, $participant->id, $bestScore($postIds)),
                'microsite_done' => $micrositeDone,
                'progress' => (int) round(($doneSteps / $totalSteps) * 100),
            ],
            'approval' => $approval,
            'learningUrl' => ParticipantLearning::getUrl(['event' => $event->id]),
            'testsUrl' => ParticipantTests::getUrl(['event' => $event->id]),
            'rundownUrl' => EventRundown::getUrl(['event' => $event->id]),
            'certificateUrl' => $certificateUrl,
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

    /** Post-test boleh diulang (ParticipantLearning::POST_TEST_RETAKES kali) selama nilai terbaik belum mencapai passing score. */
    private function postRetakeStats(LearningEvent $event, int $participantId, $bestPost): array
    {
        $post = $event->assessments->firstWhere('type', 'post');
        $passing = (float) ($post?->passing_score ?: ParticipantLearning::DEFAULT_PASSING_SCORE);
        $attempts = $post ? AssessmentAttempt::query()->where('participant_id', $participantId)->where('assessment_id', $post->id)->count() : 0;
        $passed = $bestPost !== null && (float) $bestPost >= $passing;

        return [
            'post_passing' => $passing,
            'post_passed' => $passed,
            'post_retakes_left' => $attempts === 0 || $passed ? 0 : max(0, ParticipantLearning::POST_TEST_RETAKES - ($attempts - 1)),
        ];
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
            'pre_available' => false,
            'post_available' => false,
            'pre_score' => null,
            'post_score' => null,
            'post_unlocked' => false,
            'post_passing' => null,
            'post_passed' => false,
            'post_retakes_left' => 0,
            'microsite_done' => false,
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

    private function currentMicrosite(): ?MicrositePractice
    {
        $participant = auth()->user()?->participant;

        return $participant && $this->selectedEventId
            ? MicrositePractice::query()->where('participant_id', $participant->id)->where('learning_event_id', $this->selectedEventId)->latest()->first()
            : null;
    }

    /** Peserta menyematkan link s.id / microsite untuk event terpilih (syarat sertifikat). */
    public function saveMicrosite(): void
    {
        $participant = auth()->user()?->participant;
        $event = $this->resolveSelectedEvent();

        if (! $participant || ! $event || ! $participant->isApprovedForEvent($event)) {
            return;
        }

        // Peserta cukup mengetik bagian setelah s.id/; awalan https://s.id/ selalu dibuat sistem (awalan yang ikut ditempel dibuang).
        $this->micrositeUrl = MicrositeLinkChecker::sidPath($this->micrositeUrl);
        $url = $this->micrositeUrl === '' ? '' : 'https://s.id/' . $this->micrositeUrl;

        Validator::make(['micrositeUrl' => $url], [
            'micrositeUrl' => ['required', 'max:255', new ReachableMicrositeUrl()],
        ], [
            'micrositeUrl.required' => 'Link microsite wajib diisi, mis. ISOC_Champion.',
        ], [
            'micrositeUrl' => 'link microsite',
        ])->validate();

        MicrositePractice::query()->updateOrCreate(
            ['participant_id' => $participant->id, 'learning_event_id' => $event->id],
            ['sid_url' => $url, 'status' => 'reviewed'],
        );

        Notification::make()
            ->title('Link microsite tersimpan')
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
