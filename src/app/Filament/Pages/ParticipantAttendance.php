<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Services\EventAttendanceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/** Peserta absen di event dengan kode hari H yang dibuat otomatis oleh sistem. */
class ParticipantAttendance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Seminar & Materi';

    protected static ?string $navigationLabel = 'Absensi';

    protected static ?string $title = 'Absensi Event';

    protected static ?string $slug = 'absensi';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.participant-attendance';

    public ?int $eventId = null;

    public ?string $mode = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        $service = app(EventAttendanceService::class);

        // Utamakan event yang absensinya sedang dibuka hari ini.
        $this->eventId = ($this->events->first(fn (LearningEvent $event) => $service->isOpen($event)) ?? $this->events->first())?->id;
        $this->syncMode();
    }

    public function updatedEventId(): void
    {
        $this->syncMode();
        $this->resetErrorBag();
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

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->eventId);
    }

    /** @return array<string, string> */
    public function getModeOptionsProperty(): array
    {
        return $this->selectedEvent ? app(EventAttendanceService::class)->modesFor($this->selectedEvent) : [];
    }

    public function getHistoryProperty(): EloquentCollection
    {
        $participantId = auth()->user()?->participant?->id;

        return Attendance::query()
            ->with('learningEvent.school')
            ->where('participant_id', $participantId)
            ->whereNotNull('learning_event_id')
            ->latest('checked_in_at')
            ->get();
    }

    public function isOpen(LearningEvent $event): bool
    {
        return app(EventAttendanceService::class)->isOpen($event);
    }

    public function codeForSelected(): ?string
    {
        return $this->selectedEvent ? app(EventAttendanceService::class)->codeFor($this->selectedEvent) : null;
    }

    public function attendanceForSelected(): ?Attendance
    {
        $participant = auth()->user()?->participant;

        return $participant && $this->selectedEvent
            ? app(EventAttendanceService::class)->attendanceFor($participant, $this->selectedEvent)
            : null;
    }

    public function submit(): void
    {
        $this->validate([
            'eventId' => ['required', 'integer'],
            'mode' => ['required', 'string'],
        ], [], ['eventId' => 'event', 'mode' => 'jenis kehadiran']);

        $participant = auth()->user()?->participant;
        $event = $this->selectedEvent;

        if (! $participant || ! $event) {
            $this->addError('eventId', 'Event tidak ditemukan.');

            return;
        }

        $attendance = app(EventAttendanceService::class)->checkIn($participant, $event, $this->mode);

        Notification::make()
            ->title('Absensi tercatat')
            ->body('Hadir ' . ($attendance->mode === 'online' ? 'online' : 'offline') . ' pada ' . $attendance->checked_in_at?->format('H:i') . ' WIB.')
            ->success()
            ->send();
    }

    private function syncMode(): void
    {
        $options = $this->modeOptions;

        if (! array_key_exists((string) $this->mode, $options)) {
            $this->mode = array_key_first($options);
        }
    }
}
