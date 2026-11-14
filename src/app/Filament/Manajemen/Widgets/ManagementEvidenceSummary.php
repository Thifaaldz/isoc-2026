<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Ringkasan bukti dukung: status verifikasi seluruh berkas dan kelengkapan per lokasi. */
class ManagementEvidenceSummary extends Widget
{
    protected static string $view = 'filament.manajemen.evidence-summary';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        $rows = ProgramMetrics::get()->rows;
        $sum = fn (string $k) => (int) $rows->sum(fn ($r) => $r['evidence'][$k]);

        return [
            'cards' => [
                ['Total berkas', $sum('total'), 'gray'],
                ['Disetujui', $sum('approved'), 'success'],
                ['Menunggu verifikasi', $sum('pending'), 'warning'],
                ['Ditolak', $sum('rejected'), 'danger'],
                ['Lokasi foto wajib lengkap', $rows->filter(fn ($r) => $r['photos_total'] > 0 && $r['photos_done'] >= $r['photos_total'])->count() . ' / ' . $rows->count(), 'info'],
            ],
            'rows' => $rows,
        ];
    }
}
