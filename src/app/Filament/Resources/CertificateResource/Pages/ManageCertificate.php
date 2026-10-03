<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;

class ManageCertificate extends ManageRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Cetak semua e-sertifikat terbit di satu event dalam satu PDF panjang.
            Actions\Action::make('downloadCertificates')
                ->label('Download e-Sertifikat')
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
            Actions\CreateAction::make(),
        ];
    }
}
