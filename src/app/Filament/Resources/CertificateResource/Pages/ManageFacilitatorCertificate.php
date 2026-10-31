<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Models\LearningEvent;
use App\Models\User;
use App\Services\TutorCertificateService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Pilihan "Sertifikat Fasilitator" di menu e-Certificate: sertifikat narasumber untuk pembuat event. */
class ManageFacilitatorCertificate extends Page
{
    protected static string $resource = CertificateResource::class;

    protected static string $view = 'filament.resources.certificate-resource.pages.manage-facilitator-certificate';

    protected static ?string $title = 'Sertifikat Fasilitator';

    #[Url(as: 'event')]
    public ?int $eventId = null;

    public static function canAccess(array $parameters = []): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function mount(): void
    {
        if (! $this->eventOptions()->has($this->eventId)) {
            $this->eventId = $this->eventOptions()->keys()->first();
        }
    }

    public function getSubNavigation(): array
    {
        return CertificateResource::certificateTabs();
    }

    public function eventOptions(): Collection
    {
        return CertificateResource::eventOptions();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->eventOptions()->has($this->eventId) ? LearningEvent::query()->with('creator.school')->find($this->eventId) : null;
    }

    public function getFacilitatorProperty(): ?User
    {
        return $this->selectedEvent ? app(TutorCertificateService::class)->facilitator($this->selectedEvent) : null;
    }

    public function certificateNumber(): ?string
    {
        return $this->facilitator ? app(TutorCertificateService::class)->facilitatorNumber($this->selectedEvent, $this->facilitator) : null;
    }
}
