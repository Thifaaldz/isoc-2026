<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\ChartWidget;

/** Grafik tren kumulatif capaian peserta (terdaftar, hadir, post-test, sertifikat) per hari. */
class ManagementTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren capaian peserta';

    protected static ?string $description = 'Jumlah kumulatif dari hari ke hari di seluruh lokasi.';

    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '440px';

    protected static ?string $pollingInterval = null;

    public ?string $filter = '60';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getFilters(): ?array
    {
        return ['30' => '30 hari', '60' => '60 hari', '90' => '90 hari', 'all' => 'Sejak awal'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $trend = ProgramMetrics::get()->trend($this->filter === 'all' ? null : max(7, (int) $this->filter));
        $colors = ['Terdaftar' => '#6366f1', 'Hadir' => '#14b8a6', 'Selesai Post-Test' => '#f59e0b', 'Sertifikat terbit' => '#16a34a'];

        return [
            'labels' => $trend['labels'],
            'datasets' => collect($trend['series'])->map(fn (array $data, string $name) => [
                'label' => $name,
                'data' => $data,
                'borderColor' => $colors[$name],
                'backgroundColor' => $colors[$name] . '22',
                'fill' => $name === 'Terdaftar',
                'tension' => 0.35,
                'pointRadius' => 0,
                'pointHoverRadius' => 4,
                'borderWidth' => 2.5,
            ])->values()->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'aspectRatio' => 1.05,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12, 'boxHeight' => 12]]],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false], 'ticks' => ['maxTicksLimit' => 10]],
            ],
        ];
    }
}
