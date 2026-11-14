<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\ChartWidget;

/** Rata-rata nilai Pre-Test vs Post-Test per lokasi (hanya lokasi yang sudah ada nilainya). */
class ManagementScoreChart extends ChartWidget
{
    protected static ?string $heading = 'Rata-rata nilai Pre-Test vs Post-Test per lokasi';

    protected static ?string $description = 'Garis putus-putus = target Post-Test 85. Lokasi tanpa nilai tidak ditampilkan.';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '320px';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = ProgramMetrics::get()->rows->filter(fn ($r) => $r['avg_pre'] !== null || $r['avg_post'] !== null)->values();

        return [
            'labels' => $rows->map(fn ($r) => str($r['event']->title)->replace('Digital Safety Champions - ', '')->limit(18)->toString())->all(),
            'datasets' => [
                ['label' => 'Pre-Test', 'data' => $rows->map(fn ($r) => $r['avg_pre'] ?? 0)->all(), 'backgroundColor' => '#94a3b8', 'borderRadius' => 4, 'borderWidth' => 0],
                ['label' => 'Post-Test', 'data' => $rows->map(fn ($r) => $r['avg_post'] ?? 0)->all(), 'backgroundColor' => '#6366f1', 'borderRadius' => 4, 'borderWidth' => 0],
                ['label' => 'Target 85', 'type' => 'line', 'data' => $rows->map(fn () => 85)->all(), 'borderColor' => '#16a34a', 'borderDash' => [6, 4], 'pointRadius' => 0, 'fill' => false],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'max' => 100]],
        ];
    }
}
