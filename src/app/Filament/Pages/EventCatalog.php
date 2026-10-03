<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Services\EventEnrollmentService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

/** Halaman peserta untuk melihat event yang sedang dibuka dan mengikuti event lain. */
class EventCatalog extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Seminar & Materi';

    protected static ?string $navigationLabel = 'Cari Event';

    protected static ?string $title = 'Cari & Ikuti Event';

    protected static ?string $slug = 'cari-event';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.event-catalog';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function getParticipantProperty(): ?Participant
    {
        return auth()->user()?->participant;
    }

    public function getEventsProperty(): Collection
    {
        return app(EventEnrollmentService::class)->availableEventsQuery()
            ->with(['school', 'orderedPartners'])
            ->withCount('participants')
            ->orderByDesc('registration_open')
            ->orderBy('starts_at')
            ->get();
    }

    /** @return array<int, int> */
    public function getEnrolledEventIdsProperty(): array
    {
        return $this->participant?->learningEvents()->pluck('learning_events.id')->all() ?? [];
    }

    public function joinAction(): Action
    {
        return Action::make('join')
            ->label('Ikuti Event')
            ->icon('heroicon-o-user-plus')
            ->modalHeading(fn (array $arguments) => 'Ikuti: ' . ($this->findEvent($arguments)?->title ?? 'Event'))
            ->modalDescription('Setelah terdaftar, lengkapi bukti dukung (follow Instagram dan join WhatsApp Group) di Dashboard untuk membuka modul dan tes.')
            ->modalSubmitActionLabel('Ikuti Event')
            ->form(fn (array $arguments) => [
                Forms\Components\Checkbox::make('consent')
                    ->label('Saya menyetujui pemrosesan data pendaftaran dan bersedia mengikuti ketentuan kegiatan.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Centang persetujuan terlebih dahulu.']),
            ])
            ->action(function (array $data, array $arguments): void {
                $event = $this->findEvent($arguments);
                $participant = $this->participant;

                if (! $event || ! $participant) {
                    Notification::make()->title('Event tidak ditemukan.')->danger()->send();

                    return;
                }

                $service = app(EventEnrollmentService::class);

                if ($service->isEnrolled($participant, $event)) {
                    Notification::make()->title('Kamu sudah terdaftar di event ini.')->info()->send();
                    $this->redirect('/peserta?event=' . $event->id);

                    return;
                }

                if (! $event->registration_open) {
                    Notification::make()->title('Pendaftaran event ini sudah ditutup.')->warning()->send();

                    return;
                }

                if ($service->isFull($event)) {
                    Notification::make()->title('Kuota peserta event ini sudah penuh.')->warning()->send();

                    return;
                }

                $service->enroll($participant, $event);

                Notification::make()
                    ->title('Berhasil mengikuti event')
                    ->body('Lengkapi bukti dukung di Dashboard untuk membuka modul dan tes.')
                    ->success()
                    ->send();

                $this->redirect('/peserta?event=' . $event->id);
            });
    }

    public function isFull(LearningEvent $event): bool
    {
        return app(EventEnrollmentService::class)->isFull($event);
    }

    private function findEvent(array $arguments): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) ($arguments['event'] ?? 0));
    }
}
