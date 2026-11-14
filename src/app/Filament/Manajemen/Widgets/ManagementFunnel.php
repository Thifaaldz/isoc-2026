<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Corong peserta seluruh lokasi: terdaftar sampai sertifikat terbit. */
class ManagementFunnel extends Widget
{
    protected static string $view = 'filament.manajemen.funnel';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        return ['funnel' => ProgramMetrics::get()->funnel(), 'target' => ProgramMetrics::TARGET_PARTICIPANTS];
    }
}
