<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Manajemen\Widgets\ManagementEvidenceSummary;
use App\Filament\Manajemen\Tables\ManagementTables;
use Filament\Tables\Table;

/** Ringkasan bukti dukung per lokasi, lalu tabel semua berkas. */
class EvidencePage extends ManagementDataPage
{
    protected static ?string $title = 'Bukti Dukung';

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'bukti-dukung';

    public function getSubheading(): ?string
    {
        return 'Ringkasan verifikasi bukti dukung per lokasi, lalu seluruh berkas yang diunggah.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ManagementEvidenceSummary::class];
    }

    public function dataHeading(): ?string
    {
        return 'Data bukti dukung';
    }

    public function dataDescription(): ?string
    {
        return 'Setiap berkas foto, video, dan dokumen yang diunggah daerah beserta status verifikasinya. Klik "Lihat" untuk membuka berkas.';
    }

    public function table(Table $table): Table
    {
        return ManagementTables::evidence($table);
    }
}
