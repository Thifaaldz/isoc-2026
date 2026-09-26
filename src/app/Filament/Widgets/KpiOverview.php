<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\AssessmentAttempt;
use App\Models\Participant;
use App\Models\School;
use App\Models\Tutor;
use App\Models\WagGroup;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Ringkasan KPI program (PRD-FR-17). Hanya untuk Super Admin & Admin. */
class KpiOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    protected function getStats(): array
    {
        $avg = fn (string $type, string $col) => round(
            (float) AssessmentAttempt::whereHas('assessment', fn ($q) => $q->where('type', $type))->avg($col), 1
        );

        return [
            Stat::make('Sekolah / Lokasi', School::count())->description('Target 20 lokasi'),
            Stat::make('Peserta', Participant::count())->description('Target 2.000 siswa'),
            Stat::make('Tutor', Tutor::count())->description('Target 3 per lokasi'),
            Stat::make('Rata-rata Post-Test', $avg('post', 'score'))->description('Target ≥ 85'),
            Stat::make('Identifikasi Ancaman (%)', $avg('post', 'threat_identification'))->description('Target ≥ 82'),
            Stat::make('Self-Efficacy (Post)', $avg('post', 'self_efficacy'))->description('Baseline 55 → 70 → 85'),
            Stat::make('Anggota WAG Aktif', WagGroup::sum('active_members'))->description('Target ≥ 1.400'),
        ];
    }
}
