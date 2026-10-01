<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;

class EventRundown extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Seminar & Materi';

    protected static ?string $navigationLabel = 'Rundown Acara';

    protected static ?string $title = 'Rundown Acara';

    protected static ?string $slug = 'rundown-acara';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.event-rundown';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Tutor, UserRole::Peserta], true);
    }

    public function mount(): void
    {
        $this->selectedEventId ??= $this->events->first()?->id;
    }

    public function getEventsProperty(): EloquentCollection
    {
        $user = auth()->user();

        if (! $user) {
            return new EloquentCollection();
        }

        $query = LearningEvent::query()
            ->with(['school', 'moduleTemplate'])
            ->where('is_published', true)
            ->orderByDesc('starts_at');

        if ($user->role === UserRole::Peserta) {
            $participantId = $user->participant?->id;

            return $participantId
                ? $query->whereHas('participants', fn ($participantQuery) => $participantQuery->where('participants.id', $participantId))->get()
                : new EloquentCollection();
        }

        if ($user->role === UserRole::Tutor) {
            $tutorId = $user->tutor?->id;

            return $tutorId
                ? $query->whereHas('tutors', fn ($tutorQuery) => $tutorQuery->where('tutors.id', $tutorId))->get()
                : new EloquentCollection();
        }

        return new EloquentCollection();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    public function getRundownItemsProperty(): array
    {
        if (! $this->selectedEventApproved()) {
            return [];
        }

        $items = $this->selectedEvent?->rundown_items;

        return is_array($items) ? $items : [];
    }

    public function selectedEventApproved(): bool
    {
        $user = auth()->user();

        if ($user?->role !== UserRole::Peserta || ! $this->selectedEvent || ! $user->participant) {
            return true;
        }

        return $user->participant->isApprovedForEvent($this->selectedEvent);
    }
}
