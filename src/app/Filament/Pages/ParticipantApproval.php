<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\Participant;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class ParticipantApproval extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Approval Peserta';

    protected static ?string $title = 'Approval Peserta';

    protected static ?string $slug = 'approval-peserta';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.participant-approval';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    /** Tidak termasuk menu Admin RTIK Daerah (hanya Kelola Event, Presensi, dan Peserta Lengkap). */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::Admin;
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Tutor], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->role === UserRole::Tutor ? 'Peserta & Lokasi' : 'Absensi & Peserta';
    }

    public function mount(): void
    {
        $this->selectedEventId ??= $this->events->first()?->id;
    }

    public function updatedSelectedEventId(): void
    {
        //
    }

    public function approve(int $eventId, int $participantId): void
    {
        $event = $this->events->firstWhere('id', $eventId);
        $participant = Participant::query()->find($participantId);

        if (! $event || ! $participant || ! $participant->learningEvents()->where('learning_events.id', $event->id)->exists()) {
            Notification::make()
                ->title('Data approval tidak ditemukan')
                ->warning()
                ->send();

            return;
        }

        $updates = [
            'admin_approval_status' => 'approved',
            'tutor_approval_status' => 'approved',
        ];

        if (auth()->user()?->role === UserRole::Tutor) {
            $updates['tutor_approved_by'] = auth()->id();
            $updates['tutor_approved_at'] = now();
        } else {
            $updates['admin_approved_by'] = auth()->id();
            $updates['admin_approved_at'] = now();
        }

        $participant->learningEvents()->updateExistingPivot($event->id, $updates);

        Notification::make()
            ->title('Peserta disetujui')
            ->body('Dashboard peserta untuk event umum sudah terbuka.')
            ->success()
            ->send();
    }

    public function getEventsProperty(): EloquentCollection
    {
        $user = auth()->user();

        if (! $user) {
            return new EloquentCollection();
        }

        $query = LearningEvent::query()
            ->where('is_published', true)
            ->orderByDesc('starts_at');

        if ($user->role === UserRole::Admin) {
            $query->where('created_by', $user->id);
        }

        if ($user->role === UserRole::Tutor) {
            $query->whereHas('tutors', fn ($tutorQuery) => $tutorQuery->where('tutors.id', $user->tutor?->id));
        }

        return $query->get();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    public function getRowsProperty(): Collection
    {
        if (! $this->selectedEvent) {
            return collect();
        }

        return $this->selectedEvent
            ->participants()
            ->with(['user', 'school'])
            ->get()
            ->sortBy(fn (Participant $participant) => $participant->user?->name ?? '')
            ->values()
            ->map(function (Participant $participant): array {
                $evidence = Evidence::query()
                    ->where('learning_event_id', $this->selectedEvent->id)
                    ->where('uploaded_by', $participant->user_id)
                    ->where('type', 'follow_ig')
                    ->latest()
                    ->first();
                $proofComplete = $participant->joined_wag && filled($evidence?->file_path);

                if ($proofComplete) {
                    $participant->syncInitialApprovalForEvent($this->selectedEvent);
                }

                $pivot = $participant->learningEvents()
                    ->where('learning_events.id', $this->selectedEvent->id)
                    ->first()
                    ?->pivot;

                return [
                    'participant' => $participant,
                    'status' => $this->approvalStatus($pivot),
                    'proof_complete' => $proofComplete,
                    'evidence' => $evidence,
                    'evidence_src' => $this->evidenceImageSrc($evidence),
                    'evidence_url' => $evidence?->file_path ? Storage::disk('public')->url($evidence->file_path) : null,
                ];
            });
    }

    private function approvalStatus(?object $pivot): string
    {
        if (($pivot->admin_approval_status ?? null) === 'approved' || ($pivot->tutor_approval_status ?? null) === 'approved') {
            return 'approved';
        }

        if (($pivot->admin_approval_status ?? null) === 'rejected' || ($pivot->tutor_approval_status ?? null) === 'rejected') {
            return 'rejected';
        }

        return 'pending';
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
