<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Manajemen\Widgets\ManagementFunnel;
use App\Filament\Manajemen\Widgets\ManagementDemographics;
use App\Filament\Manajemen\Widgets\ManagementLocationChart;
use App\Filament\Manajemen\Widgets\ManagementScoreChart;
use App\Filament\Manajemen\Tables\ManagementTables;
use Filament\Tables\Table;

/** Ringkasan peserta & nilai, lalu tabel data peserta. */
class ParticipantScoresPage extends ManagementDataPage
{
    protected static ?string $title = 'Peserta & Nilai';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'peserta-nilai';

    public function getSubheading(): ?string
    {
        return 'Ringkasan capaian dan profil peserta, lalu data lengkap setiap peserta di bawah.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ManagementFunnel::class, ManagementDemographics::class, ManagementLocationChart::class, ManagementScoreChart::class];
    }

    public function dataHeading(): ?string
    {
        return 'Data peserta';
    }

    public function dataDescription(): ?string
    {
        return 'Semua peserta: kehadiran, nilai pre/post-test (tertinggi), microsite, follow IG, join WAG, dan sertifikat. Gunakan pencarian dan filter lokasi.';
    }

    public function table(Table $table): Table
    {
        return ManagementTables::participants($table);
    }
}
