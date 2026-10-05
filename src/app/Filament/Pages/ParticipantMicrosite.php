<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Rules\ReachableMicrositeUrl;
use App\Services\MicrositeLinkChecker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Validator;

/** Peserta menyematkan link s.id / microsite per event, sama seperti kartu Link Microsite di dashboard. */
class ParticipantMicrosite extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Tugas Peserta';

    protected static ?string $navigationLabel = 'Praktik Microsite';

    protected static ?string $title = 'Praktik Microsite';

    protected static ?string $slug = 'praktik-microsite';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.participant-microsite';

    /** @var array<int, string> Bagian setelah https://s.id/ per event. */
    public array $links = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        foreach ($this->events as $event) {
            $this->links[$event->id] = MicrositeLinkChecker::sidPath($this->practices->get($event->id)?->sid_url);
        }
    }

    public function getEventsProperty(): EloquentCollection
    {
        $participantId = auth()->user()?->participant?->id;

        if (! $participantId) {
            return new EloquentCollection();
        }

        return LearningEvent::query()
            ->with('school')
            ->where('is_published', true)
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participantId))
            ->orderByDesc('starts_at')
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, MicrositePractice> Link terakhir per event. */
    public function getPracticesProperty()
    {
        $participantId = auth()->user()?->participant?->id;

        return MicrositePractice::query()
            ->where('participant_id', $participantId)
            ->whereNotNull('learning_event_id')
            ->latest()
            ->get()
            ->unique('learning_event_id')
            ->keyBy('learning_event_id');
    }

    public function isApproved(LearningEvent $event): bool
    {
        return (bool) auth()->user()?->participant?->isApprovedForEvent($event);
    }

    public function save(int $eventId): void
    {
        $participant = auth()->user()?->participant;
        $event = $this->events->firstWhere('id', $eventId);

        if (! $participant || ! $event || ! $participant->isApprovedForEvent($event)) {
            return;
        }

        $this->resetErrorBag("links.{$eventId}");

        // Peserta cukup mengetik bagian setelah s.id/; awalan https://s.id/ selalu dibuat sistem (awalan yang ikut ditempel dibuang).
        $this->links[$eventId] = MicrositeLinkChecker::sidPath($this->links[$eventId] ?? '');
        $url = $this->links[$eventId] === '' ? '' : 'https://s.id/' . $this->links[$eventId];

        Validator::make(['links' => [$eventId => $url]], [
            "links.{$eventId}" => ['required', 'max:255', new ReachableMicrositeUrl()],
        ], [
            "links.{$eventId}.required" => 'Link microsite wajib diisi, mis. Daftar_Peserta.',
        ], [
            "links.{$eventId}" => 'link microsite',
        ])->validate();

        MicrositePractice::query()->updateOrCreate(
            ['participant_id' => $participant->id, 'learning_event_id' => $event->id],
            ['sid_url' => $url, 'status' => 'reviewed'],
        );

        Notification::make()
            ->title(MicrositeLinkChecker::SAVED_TITLE)
            ->body(MicrositeLinkChecker::savedMessage($url, $event->title))
            ->success()
            ->send();
    }
}
