<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class ParticipantCompleteness extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $navigationLabel = 'Bukti Kelengkapan';

    protected static ?string $title = 'Bukti Kelengkapan';

    protected static ?string $slug = 'bukti-kelengkapan';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.participant-completeness';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        $this->selectedEventId ??= $this->events->first()?->id;
        $this->syncInitialApproval();
    }

    public function updatedSelectedEventId(): void
    {
        $this->syncInitialApproval();
    }

    public function syncInitialApproval(): void
    {
        $participant = auth()->user()?->participant;
        $event = $this->selectedEvent;

        if (! $participant || ! $event) {
            return;
        }

        $participant->syncInitialApprovalForEvent($event);
    }

    public function toggleJoinedWag(): void
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return;
        }

        $participant->update(['joined_wag' => ! $participant->joined_wag]);
        $this->syncInitialApproval();

        Notification::make()
            ->title('Status WhatsApp Group diperbarui')
            ->success()
            ->send();
    }

    public function getEventsProperty(): EloquentCollection
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return new EloquentCollection();
        }

        return LearningEvent::query()
            ->with('school.wagGroups')
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->orderByDesc('starts_at')
            ->get();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId) ?? $this->events->first();
    }

    public function getCompletenessProperty(): array
    {
        $participant = auth()->user()?->participant;
        $event = $this->selectedEvent;

        if (! $participant || ! $event) {
            return [
                'follow_evidence' => null,
                'follow_src' => null,
                'follow_url' => null,
                'follow_complete' => false,
                'wag' => null,
                'wag_complete' => false,
                'initial_approved' => false,
                'microsite' => null,
                'microsite_complete' => false,
                'all_complete' => false,
            ];
        }

        $followEvidence = Evidence::query()
            ->where('learning_event_id', $event->id)
            ->where('uploaded_by', $participant->user_id)
            ->where('type', 'follow_ig')
            ->latest()
            ->first();

        $microsite = MicrositePractice::query()
            ->where('participant_id', $participant->id)
            ->where('learning_event_id', $event->id)
            ->latest()
            ->first();

        $wag = $event->school?->wagGroups?->sortByDesc(fn ($group) => $group->status === 'active')->first();
        $followComplete = filled($followEvidence?->file_path);
        $wagComplete = (bool) $participant->joined_wag;
        $initialApproved = $participant->isApprovedForEvent($event);
        $micrositeComplete = filled($microsite?->sid_url);

        return [
            'follow_evidence' => $followEvidence,
            'follow_src' => $this->evidenceImageSrc($followEvidence),
            'follow_url' => $followEvidence?->file_path ? Storage::disk('public')->url($followEvidence->file_path) : null,
            'follow_complete' => $followComplete,
            'wag' => $wag,
            'wag_complete' => $wagComplete,
            'initial_approved' => $initialApproved,
            'microsite' => $microsite,
            'microsite_complete' => $micrositeComplete,
            'all_complete' => $followComplete && $wagComplete && $initialApproved && $micrositeComplete,
        ];
    }

    private function evidenceImageSrc(?Evidence $evidence): ?string
    {
        if (! $evidence?->file_path) {
            return null;
        }

        $extension = strtolower(pathinfo($evidence->file_path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        if (! Storage::disk('public')->exists($evidence->file_path)) {
            return null;
        }

        $path = Storage::disk('public')->path($evidence->file_path);
        $mime = mime_content_type($path) ?: 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }
}
