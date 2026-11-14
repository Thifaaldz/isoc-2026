<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Manajemen\Widgets\ManagementTutorSummary;
use App\Filament\Manajemen\Tables\ManagementTables;
use Filament\Tables\Table;

/** Ringkasan tutor per lokasi, lalu tabel data tutor. */
class TutorsPage extends ManagementDataPage
{
    protected static ?string $title = 'Tutor & ToT';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'tutor-tot';

    public function getSubheading(): ?string
    {
        return 'Ringkasan kesiapan tutor per lokasi, lalu data lengkap setiap tutor.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ManagementTutorSummary::class];
    }

    public function dataHeading(): ?string
    {
        return 'Data tutor';
    }

    public function dataDescription(): ?string
    {
        return 'Semua tutor yang ditugaskan: lokasi, lembaga, kelulusan dan nilai ToT, serta kelengkapan profil honor.';
    }

    public function table(Table $table): Table
    {
        return ManagementTables::tutors($table);
    }
}
