<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Tabel progres per lokasi dengan filter (read-only, tanpa tautan ke panel lain). */
class ManagementProgress extends Widget
{
    protected static string $view = 'filament.manajemen.progress';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public string $filter = 'all';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        $m = ProgramMetrics::get();
        $rows = $m->rows;
        $filters = [
            'all' => ['Semua', fn ($r) => true],
            'running' => ['Berjalan', fn ($r) => $r['stage'] < 5 && ! $r['overview']->isFinished()],
            'report' => ['Menunggu laporan', fn ($r) => $r['stage'] < 5 && $r['overview']->isFinished()],
            'done' => ['Selesai', fn ($r) => $r['stage'] === 5],
        ];
        $active = $filters[$this->filter] ?? $filters['all'];

        return [
            'rows' => $rows->filter($active[1])->values(),
            'filters' => collect($filters)->map(fn ($f) => ['label' => $f[0], 'count' => $rows->filter($f[1])->count()]),
            'total' => $rows->count(),
        ];
    }
}
