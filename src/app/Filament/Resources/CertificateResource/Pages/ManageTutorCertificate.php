<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Models\LearningEvent;
use App\Services\TutorCertificateService;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Pilihan "Sertifikat Tutor" di menu e-Certificate; nomor dibuat otomatis dari event dan tutor. */
class ManageTutorCertificate extends Page
{
    protected static string $resource = CertificateResource::class;

    protected static string $view = 'filament.resources.certificate-resource.pages.manage-tutor-certificate';

    protected static ?string $title = 'Sertifikat Tutor';

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
        return $this->eventOptions()->has($this->eventId) ? LearningEvent::query()->find($this->eventId) : null;
    }

    /** @return Collection<int, array{tutor: \App\Models\Tutor, number: string}> */
    public function rows(): Collection
    {
        $event = $this->selectedEvent;

        if (! $event) {
            return collect();
        }

        $service = app(TutorCertificateService::class);

        return $event->tutors()->with(['user', 'school'])->get()
            ->sortBy(fn ($tutor) => $tutor->user?->name)
            ->values()
            ->map(fn ($tutor) => ['tutor' => $tutor, 'number' => $service->number($event, $tutor)]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadAll')
                ->label('Download Semua Sertifikat Tutor')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => $this->rows()->isNotEmpty())
                ->url(fn () => route('tutor-certificates.event-download', $this->eventId)),
        ];
    }
}
