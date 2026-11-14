<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Manajemen\Widgets\ManagementReports;
use App\Filament\Manajemen\Tables\ManagementTables;
use Filament\Tables\Table;

/** Ringkasan laporan & termin, lalu tabel sertifikat. */
class ReportsTermsPage extends ManagementDataPage
{
    protected static ?string $title = 'Laporan & Termin';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'laporan-termin';

    public function getSubheading(): ?string
    {
        return 'Status pencairan termin dan laporan final, lalu data sertifikat yang terbit.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ManagementReports::class];
    }

    public function dataHeading(): ?string
    {
        return 'Data sertifikat peserta';
    }

    public function dataDescription(): ?string
    {
        return 'Nomor dan status sertifikat peserta di semua lokasi.';
    }

    public function table(Table $table): Table
    {
        return ManagementTables::certificates($table);
    }
}
