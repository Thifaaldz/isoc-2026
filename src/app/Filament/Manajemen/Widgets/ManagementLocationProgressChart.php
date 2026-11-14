<?php

namespace App\Filament\Manajemen\Widgets;

use App\Filament\Manajemen\Pages\LocationDetailPage;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Daftar progres tiap lokasi (HTML, tetap terbaca di layar kecil): nama, bar persen tahapan, dan tahap saat ini. */
class ManagementLocationProgressChart extends Widget
{
    protected static string $view = 'filament.manajemen.location-progress-list';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    protected function getViewData(): array
    {
        return [
            'rows' => ProgramMetrics::get()->rows
                ->sortByDesc('progress')
                ->values()
                ->map(fn (array $r) => [
                    'name' => str($r['event']->title)->replace('Digital Safety Champions - ', '')->toString(),
                    'progress' => $r['progress'],
                    'stage' => $r['stage'] < 5 ? ProgramMetrics::STAGES[$r['stage']] : 'Selesai',
                    'url' => LocationDetailPage::getUrl(['event' => $r['event']->id]),
                ]),
        ];
    }
}
