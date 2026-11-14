<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Status Termin-1, laporan final (13 syarat), dan Termin-2 setiap lokasi. */
class ManagementReports extends Widget
{
    protected static string $view = 'filament.manajemen.reports';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        $rows = ProgramMetrics::get()->rows->map(fn (array $r) => [
            ...$r,
            'term1' => self::term($r['overview']->paymentStatus(1)),
            'term2' => self::term($r['overview']->paymentStatus(2)),
            'report' => $r['overview']->finalReportStatus(),
        ]);

        return [
            'rows' => $rows,
            'total' => $rows->count(),
            'summary' => [
                ['label' => 'Termin-1 cair', 'value' => $rows->where('term1.label', 'Cair')->count()],
                ['label' => 'Syarat laporan lengkap', 'value' => $rows->filter(fn ($r) => $r['proof_total'] > 0 && $r['proof_done'] >= $r['proof_total'])->count()],
                ['label' => 'Laporan menunggu approval', 'value' => $rows->filter(fn ($r) => $r['event']->final_report_status === 'submitted')->count()],
                ['label' => 'Laporan disetujui', 'value' => $rows->filter(fn ($r) => $r['event']->final_report_status === 'approved')->count()],
                ['label' => 'Termin-2 cair', 'value' => $rows->where('term2.label', 'Cair')->count()],
            ],
        ];
    }

    /** Label status termin: eligible/paid = cair. */
    private static function term(?string $status): array
    {
        return match ($status) {
            'eligible', 'paid' => ['label' => 'Cair', 'color' => 'success'],
            'revision' => ['label' => 'Revisi', 'color' => 'danger'],
            'pending' => ['label' => 'Menunggu', 'color' => 'gray'],
            default => ['label' => 'Belum ada', 'color' => 'gray'],
        };
    }
}
