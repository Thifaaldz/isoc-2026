<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\AssessmentAttempt;
use App\Models\LearningEvent;
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
        if (auth()->user()?->role === UserRole::Tutor && ! (bool) auth()->user()?->tutor?->tot_completed) {
            return false;
        }

        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor], true);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        $eventScope = function ($query) use ($user) {
            if (! $user || $user->role === UserRole::SuperAdmin) {
                return $query;
            }

            if ($user->role === UserRole::Admin) {
                return $query->where('created_by', $user->id);
            }

            if ($user->role === UserRole::Tutor) {
                return $query->whereHas('tutors', fn ($tutorQuery) => $tutorQuery->where('tutors.id', $user->tutor?->id));
            }

            return $query->whereRaw('1 = 0');
        };

        $schoolQuery = School::query();
        $participantQuery = Participant::query();
        $tutorQuery = Tutor::query();
        $wagQuery = WagGroup::query();

        if ($user?->role !== UserRole::SuperAdmin && $user?->school_id) {
            $schoolQuery->whereKey($user->school_id);
            $participantQuery->whereHas('learningEvents', $eventScope);
            $tutorQuery->whereHas('learningEvents', $eventScope);
            $wagQuery->where('school_id', $user->school_id);
        }

        $avg = fn (string $type, string $col) => round(
            (float) AssessmentAttempt::query()
                ->whereHas('assessment', fn ($q) => $q
                    ->where('type', $type)
                    ->whereHas('learningEvent', $eventScope))
                ->avg($col),
            1
        );

        return [
            Stat::make('Lokasi', $schoolQuery->count())->description('Target 20 lokasi'),
            Stat::make('Event Aktif', LearningEvent::query()->where($eventScope)->count())->description('Sesuai akses akun'),
            Stat::make('Peserta', $participantQuery->count())->description('Target 2.000 siswa'),
            Stat::make('Tutor', $tutorQuery->count())->description('Target 3 per lokasi'),
            Stat::make('Rata-rata Post-Test', $avg('post', 'score'))->description('Target ≥ 85'),
            Stat::make('Identifikasi Ancaman (%)', $avg('post', 'threat_identification'))->description('Target ≥ 82'),
            Stat::make('Self-Efficacy (Post)', $avg('post', 'self_efficacy'))->description('Baseline 55 → 70 → 85'),
            Stat::make('Anggota WAG Aktif', $wagQuery->sum('active_members'))->description('Target ≥ 1.400'),
        ];
    }
}
