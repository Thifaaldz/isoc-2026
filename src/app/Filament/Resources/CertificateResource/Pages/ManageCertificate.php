<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Services\CertificateEligibilityService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificate extends ManageRecords
{
    protected static string $resource = CertificateResource::class;

    public function getSubNavigation(): array
    {
        return CertificateResource::certificateTabs();
    }

    protected function getHeaderActions(): array
    {
        return [
            // Cetak semua e-sertifikat terbit di satu event dalam satu PDF panjang.
            Actions\Action::make('downloadCertificates')
                ->label('Download e-Sertifikat Peserta')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => auth()->user()?->role !== UserRole::Peserta)
                ->modalHeading('Download e-Sertifikat Peserta')
                ->modalDescription('Unduh semua e-sertifikat yang sudah terbit di satu event dalam satu file PDF (siap cetak) sebagai dokumen bukti dukung.')
                ->modalSubmitActionLabel('Download')
                ->form([
                    Forms\Components\Select::make('learning_event_id')
                        ->label('Event')
                        ->options(fn () => CertificateResource::eventOptions())
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(fn (array $data) => redirect()->route('certificates.event-download', $data['learning_event_id'])),
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // Nomor selalu dihitung ulang di server, tidak bergantung pada isian form.
                    $data['number'] = CertificateEligibilityService::numberFor($data['learning_event_id'], $data['participant_id']);
                    $data['certificate_template_id'] ??= CertificateResource::templateForEvent($data['learning_event_id']);

                    return $data;
                }),
        ];
    }
}
