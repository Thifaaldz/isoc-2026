<?php

namespace App\Filament\Manajemen\Widgets;

use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Daftar hal yang perlu perhatian manajemen (laporan terlambat, bukti menunggu, peserta minim, tutor). */
class ManagementAlerts extends Widget
{
    protected static string $view = 'filament.manajemen.alerts';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    protected function getViewData(): array
    {
        $alerts = ProgramMetrics::get()->alerts();

        return ['alerts' => $alerts->take(12), 'more' => max(0, $alerts->count() - 12), 'total' => $alerts->count()];
    }
}
