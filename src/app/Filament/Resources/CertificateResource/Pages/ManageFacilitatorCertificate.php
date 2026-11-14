<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Models\LearningEvent;
use App\Support\SpeakerCertificates;
use App\Services\TutorCertificateService;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Pilihan "Sertifikat Fasilitator" di menu e-Certificate: sertifikat narasumber untuk pembuat event dan fasilitator tambahan. */
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

    /** @return Collection<int, array{user: \App\Models\User, number: string, manual: ?array}> */
    public function rows(): Collection
    {
        $event = $this->selectedEvent;

        if (! $event) {
            return collect();
        }

        $service = app(TutorCertificateService::class);
        $creatorId = $service->facilitator($event)?->id;
        $manual = $service->manualCertificates($event, SpeakerCertificates::ROLE_FACILITATOR);

        return $service->facilitators($event)
            ->map(fn ($user) => [
                'user' => $user,
                'number' => $service->facilitatorNumber($event, $user),
                // Pembuat event selalu punya sertifikat; hanya tambahan manual yang bisa dihapus.
                'manual' => $user->id === $creatorId ? null : $manual->get($user->id),
            ]);
    }

    public function deleteManualAction(): Actions\Action
    {
        return SpeakerCertificateActions::delete();
    }

    protected function getHeaderActions(): array
    {
        return [
            SpeakerCertificateActions::create(SpeakerCertificates::ROLE_FACILITATOR, $this),
        ];
    }
}
