<?php

namespace App\Filament\Pages;

use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Services\TutorCertificateService;
use App\Services\TutorRegistrationService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;

/** Form sertifikat tutor: pilih event yang didampingi lalu pratinjau / unduh PDF, tanpa syarat kelulusan. */
class TutorCertificate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Sertifikat Tutor';

    protected static ?string $title = 'Sertifikat Tutor';

    protected static ?string $slug = 'sertifikat-tutor';

    protected static string $view = 'filament.pages.tutor-certificate';

    #[Url(as: 'event')]
    public ?int $eventId = null;

    public static function canAccess(): bool
    {
        return TutorRegistrationService::isTutor(auth()->user());
    }

    public function mount(): void
    {
        if (! $this->events->contains('id', $this->eventId)) {
            $this->eventId = $this->events->last()?->id;
        }
    }

    public function getTutorProperty(): ?Tutor
    {
        return auth()->user()?->tutor?->loadMissing(['user', 'school']);
    }

    public function getEventsProperty(): Collection
    {
        return $this->tutor ? app(TutorCertificateService::class)->events($this->tutor) : new Collection();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', $this->eventId);
    }

    public function certificateNumber(): ?string
    {
        return $this->selectedEvent && $this->tutor ? app(TutorCertificateService::class)->number($this->selectedEvent, $this->tutor) : null;
    }
}
