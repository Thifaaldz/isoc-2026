<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Enums\UserRole;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Ringkasan akun di atas tabel Users & Akses: peserta, peserta aktif 7 hari, tutor & admin. */
class UserStatsOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $weekAgo = now()->subDays(7);
        $peserta = User::where('role', UserRole::Peserta);
        $pesertaTotal = (clone $peserta)->count();
        $pesertaBaru = (clone $peserta)->where('created_at', '>=', $weekAgo)->count();

        // Tren pendaftaran peserta 14 hari terakhir untuk grafik kecil di kartu.
        $start = now()->subDays(13)->startOfDay();
        $perDay = (clone $peserta)->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $trend = collect(range(0, 13))->map(fn ($i) => (int) ($perDay[$start->copy()->addDays($i)->toDateString()] ?? 0))->all();

        // Peserta aktif = mengerjakan tes atau absen dalam 7 hari terakhir.
        $aktif = collect()
            ->merge(AssessmentAttempt::whereNotNull('participant_id')->where('created_at', '>=', $weekAgo)->pluck('participant_id'))
            ->merge(Attendance::whereNotNull('participant_id')->where('created_at', '>=', $weekAgo)->pluck('participant_id'))
            ->unique()->count();

        $tutor = User::where('role', UserRole::Tutor)->count();
        $admin = User::whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])->count();
        $tutorTanpaEvent = Tutor::whereDoesntHave('learningEvents')->count();

        return [
            Stat::make('Peserta', number_format($pesertaTotal, 0, ',', '.'))
                ->icon('heroicon-o-academic-cap')
                ->description('+' . $pesertaBaru . ' dalam 7 hari')
                ->descriptionColor('primary')
                ->chart($trend)
                ->chartColor('primary'),
            Stat::make('Aktif minggu ini', number_format($aktif, 0, ',', '.'))
                ->icon('heroicon-o-bolt')
                ->description('peserta absen / mengerjakan tes dalam 7 hari')
                ->descriptionColor('success'),
            Stat::make('Tutor & admin', $tutor . ' / ' . $admin)
                ->icon('heroicon-o-presentation-chart-bar')
                ->description($tutorTanpaEvent . ' tutor belum ditugaskan ke event')
                ->descriptionColor($tutorTanpaEvent > 0 ? 'warning' : 'gray'),
        ];
    }
}
