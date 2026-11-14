<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Progres keseluruhan program dan jumlah event di tiap tahap. */
class ManagementStages extends Widget
{
    protected static string $view = 'filament.manajemen.stages';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        $m = ProgramMetrics::get();

        return ['progress' => $m->overallProgress(), 'stages' => $m->stageCounts(), 'total' => $m->rows->count()];
    }
}
