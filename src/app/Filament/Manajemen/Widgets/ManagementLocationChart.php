<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\ChartWidget;

/** Perbandingan antar lokasi: peserta terdaftar, hadir, dan selesai post-test. */
class ManagementLocationChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan peserta per lokasi';

    protected static ?string $description = 'Terdaftar, hadir, dan selesai post-test di setiap lokasi.';

    protected static ?int $sort = 2;

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
        $rows = ProgramMetrics::get()->rows;
        $series = fn (string $key) => $rows->map(fn ($r) => (int) ($r['stats'][$key] ?? 0))->all();

        return [
            'labels' => $rows->map(fn ($r) => str($r['event']->title)->replace('Digital Safety Champions - ', '')->limit(18)->toString())->all(),
            'datasets' => [
                ['label' => 'Terdaftar', 'data' => $series('participants'), 'backgroundColor' => '#6366f1', 'borderRadius' => 4, 'borderWidth' => 0],
                ['label' => 'Hadir', 'data' => $series('attended'), 'backgroundColor' => '#14b8a6', 'borderRadius' => 4, 'borderWidth' => 0],
                ['label' => 'Selesai Post-Test', 'data' => $series('post'), 'backgroundColor' => '#f59e0b', 'borderRadius' => 4, 'borderWidth' => 0],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
