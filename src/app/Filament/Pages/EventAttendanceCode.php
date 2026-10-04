<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Services\EventAttendanceService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;

/** Tutor dan fasilitator (Admin RTIK Daerah) membuat kode absensi event dan memantau peserta yang sudah absen. */
class EventAttendanceCode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';


    protected static ?string $navigationLabel = 'Kode Absensi';

    protected static ?string $title = 'Kode Absensi Event';

    protected static ?string $slug = 'kode-absensi';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.event-attendance-code';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Tutor, UserRole::Admin], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->role === UserRole::Admin ? 'Absensi & Peserta' : 'Absensi & Sesi';
    }

    public function mount(): void
    {
        $service = app(EventAttendanceService::class);

        $this->selectedEventId ??= ($this->events->first(fn (LearningEvent $event) => $service->isOpen($event)) ?? $this->events->first())?->id;
    }

    public function getEventsProperty(): EloquentCollection
    {
        $user = auth()->user();

        $query = match ($user?->role) {
            // Tutor: event yang ditugaskan; fasilitator (Admin RTIK Daerah): event yang dibuatnya.
            UserRole::Tutor => $user->tutor
                ? LearningEvent::query()->whereHas('tutors', fn ($tutorQuery) => $tutorQuery->where('tutors.id', $user->tutor->id))
                : null,
            UserRole::Admin => LearningEvent::query()->where('created_by', $user->id),
            default => null,
        };

        if (! $query) {
            return new EloquentCollection();
        }

        return $query
            ->with('school')
            ->withCount([
                'participants',
                'attendances as checked_in_count' => fn ($query) => $query->whereNotNull('participant_id')->where('status', 'hadir'),
            ])
            ->whereNotIn('workflow_status', ['cancelled', 'rejected'])
            ->orderByDesc('starts_at')
            ->get();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    public function getCheckInsProperty(): EloquentCollection
    {
        if (! $this->selectedEvent) {
            return new EloquentCollection();
        }

        return Attendance::query()
            ->with('participant.user')
            ->where('learning_event_id', $this->selectedEvent->id)
            ->whereNotNull('participant_id')
            ->latest('checked_in_at')
            ->get();
    }

    public function isOpen(LearningEvent $event): bool
    {
        return app(EventAttendanceService::class)->isOpen($event);
    }

    public function selectEvent(int $eventId): void
    {
        $this->selectedEventId = $eventId;
    }

    public function generate(int $eventId): void
    {
        // Hanya event milik tutor/fasilitator ini.
        $event = $this->events->firstWhere('id', $eventId);

        if (! $event) {
            Notification::make()->title('Event tidak ditemukan')->danger()->send();

            return;
        }

        $code = app(EventAttendanceService::class)->generateCode($event);
        $this->selectedEventId = $event->id;

        Notification::make()
            ->title('Kode absensi dibuat')
            ->body("{$code} — bagikan ke peserta {$event->title}. Kode lama tidak berlaku lagi.")
            ->success()
            ->send();
    }
}
