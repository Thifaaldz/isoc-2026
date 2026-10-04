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
    // Paling atas di dashboard, sebelum kartu Update/Reminder dan widget akun.
    protected static ?int $sort = -7;

    protected function getColumns(): int
    {
        return 4;
    }

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

        // Non-Admin RTIK Pusat: selalu dibatasi ke event yang dikelola/didampingi
        // (akun fasilitator baru tidak harus punya sekolah utama).
        if ($user?->role !== UserRole::SuperAdmin) {
            $schoolIds = LearningEvent::query()->where($eventScope)->whereNotNull('school_id')->pluck('school_id')
                ->when($user?->school_id, fn ($ids) => $ids->push($user->school_id))
                ->unique()->values();
            $schoolQuery->whereKey($schoolIds);
            $participantQuery->whereHas('learningEvents', $eventScope);
            $tutorQuery->whereHas('learningEvents', $eventScope);
            $wagQuery->whereIn('school_id', $schoolIds);
        }

        $avg = fn (string $type, string $col) => round(
            (float) AssessmentAttempt::query()
                ->whereHas('assessment', fn ($q) => $q
                    ->where('type', $type)
                    ->whereHas('learningEvent', $eventScope))
                ->avg($col),
            1
        );

        // Hijau bila target TOR tercapai, kuning bila belum.
        $stat = fn (string $label, int | float $value, string $description, string $icon, int | float | null $target = null) => Stat::make($label, is_float($value) ? number_format($value, 1, ',', '.') : number_format($value, 0, ',', '.'))
            ->description($description)
            ->descriptionIcon($icon)
            ->color($target === null ? 'gray' : ($value >= $target ? 'success' : 'warning'));

        return [
            $stat('Lokasi', $schoolQuery->count(), 'Target 20 lokasi', 'heroicon-m-map-pin', $user?->role === UserRole::SuperAdmin ? 20 : null),
            $stat('Event', LearningEvent::query()->where($eventScope)->count(), 'Sesuai akses akun', 'heroicon-m-calendar-days'),
            $stat('Peserta', $participantQuery->count(), 'Target 2.000 siswa', 'heroicon-m-user-group', $user?->role === UserRole::SuperAdmin ? 2000 : null),
            $stat('Tutor', $tutorQuery->count(), 'Target 3 per lokasi', 'heroicon-m-academic-cap'),
            $stat('Rata-rata Post-Test', $avg('post', 'score'), 'Target ≥ 85', 'heroicon-m-clipboard-document-check', 85),
            $stat('Identifikasi Ancaman (%)', $avg('post', 'threat_identification'), 'Target ≥ 82', 'heroicon-m-shield-check', 82),
            $stat('Self-Efficacy (Post)', $avg('post', 'self_efficacy'), 'Baseline 55 → 70 → 85', 'heroicon-m-sparkles', 70),
            $stat('Anggota WAG Aktif', (int) $wagQuery->sum('active_members'), 'Target ≥ 1.400', 'heroicon-m-chat-bubble-left-right', $user?->role === UserRole::SuperAdmin ? 1400 : null),
        ];
    }
}
