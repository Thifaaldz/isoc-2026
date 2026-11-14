<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
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
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        return ProgramMetrics::get()->demographics();
    }
}
