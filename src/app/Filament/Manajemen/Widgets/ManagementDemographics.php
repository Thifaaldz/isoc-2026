<?php

namespace App\Filament\Manajemen\Widgets;

use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Profil peserta: jenis kelamin, kategori, kelas, follow IG, join WAG. */
class ManagementDemographics extends Widget
{
    protected static string $view = 'filament.manajemen.demographics';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    protected function getViewData(): array
    {
        return ProgramMetrics::get()->demographics();
    }
}
