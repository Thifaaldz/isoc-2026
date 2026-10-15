<?php

namespace App\Filament\Pages;

use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Services\TutorRegistrationService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/** Halaman tutor untuk melihat event yang dibuka dan mendaftar sebagai tutor event (divalidasi Admin RTIK Pusat). */
class TutorEventCatalog extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Daftar Event';

    protected static ?string $title = 'Daftar Event sebagai Tutor';

    protected static ?string $slug = 'daftar-event';

    protected static string $view = 'filament.pages.tutor-event-catalog';

    public static function canAccess(): bool
    {
        return TutorRegistrationService::isTutor(auth()->user());
    }

    public function getTutorProperty(): ?Tutor
    {
        return auth()->user()?->tutor;
    }

    /** @return Collection<int, LearningEvent> */
    public function getEventsProperty(): Collection
    {
        return app(TutorRegistrationService::class)->availableEvents();
    }

    /** Status pendaftaran tutor per event (pending / assigned / rejected). @return array<int, string> */
    public function getStatusesProperty(): array
    {
        return $this->tutor?->registrations()->pluck('status', 'learning_event_id')->all() ?? [];
    }

    public function closedReason(LearningEvent $event): ?string
    {
        return app(TutorRegistrationService::class)->closedReason($event, $this->tutor);
    }

    public function joinAction(): Action
    {
        return Action::make('join')
            ->label('Daftar Jadi Tutor')
            ->icon('heroicon-o-user-plus')
            ->modalHeading(fn (array $arguments) => 'Daftar tutor: ' . ($this->findEvent($arguments)?->title ?? 'Event'))
            ->modalDescription('Pendaftaran menunggu validasi Admin RTIK Pusat. Sambil menunggu, kerjakan ToT untuk event ini dari menu ToT Awal.')
            ->modalSubmitActionLabel('Daftar')
            ->form([
                Forms\Components\Checkbox::make('consent')
                    ->label('Saya menyetujui pemrosesan data pendaftaran dan bersedia menjadi tutor sesuai ketentuan kegiatan.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Centang persetujuan terlebih dahulu.']),
            ])
            ->action(function (array $arguments): void {
                $event = $this->findEvent($arguments);
                $tutor = $this->tutor;

                if (! $event || ! $tutor) {
                    Notification::make()->title('Event tidak ditemukan.')->danger()->send();

                    return;
                }

                $service = app(TutorRegistrationService::class);

                if ($reason = $service->closedReason($event, $tutor)) {
                    Notification::make()->title($reason)->warning()->send();

                    return;
                }

                $service->apply($tutor, $event);

                Notification::make()
                    ->title('Pendaftaran tutor terkirim')
                    ->body('Menunggu validasi Admin RTIK Pusat. Kerjakan ToT untuk event ini dari menu ToT Awal.')
                    ->success()
                    ->send();

                $this->redirect(TutorTraining::getUrl(['event' => $event->id]));
            });
    }

    private function findEvent(array $arguments): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) ($arguments['event'] ?? 0));
    }
}
