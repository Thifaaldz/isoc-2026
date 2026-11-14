<?php

namespace App\Filament\Manajemen\Widgets;

use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Ringkasan tutor: total, lulus ToT, rata-rata nilai ToT, dan kesiapan per lokasi. */
class ManagementTutorSummary extends Widget
{
    protected static string $view = 'filament.manajemen.tutor-summary';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    protected function getViewData(): array
    {
        $m = ProgramMetrics::get();
        $tutors = $m->tutors();

        return [
            'cards' => [
                ['Penugasan tutor', $tutors->count(), null],
                ['Lulus ToT', $tutors->where('tot_completed', true)->count(), $tutors->count()],
                ['Nilai ToT sempurna (100)', $tutors->where('tot_perfect', true)->count(), $tutors->count()],
                ['Profil honor lengkap', $tutors->where('profile_complete', true)->count(), $tutors->count()],
            ],
            'avg' => $tutors->whereNotNull('tot_score')->avg('tot_score'),
            'rows' => $m->rows,
        ];
    }
}
