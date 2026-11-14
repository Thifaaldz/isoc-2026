<?php

namespace App\Filament\Manajemen\Pages;

use App\Enums\UserRole;
use App\Filament\Manajemen\Widgets\ManagementAlerts;
use App\Filament\Manajemen\Widgets\ManagementKpi;
use App\Filament\Manajemen\Widgets\ManagementLocationProgressChart;
use App\Filament\Manajemen\Widgets\ManagementTrendChart;
use App\Filament\Manajemen\Widgets\ManagementStages;
use Filament\Pages\Dashboard;

/** Beranda panel Manajemen: KPI program dan tahapan seluruh lokasi (read-only). Rincian ada di menu Monitoring. */
class ManagementDashboard extends Dashboard
{
    protected static ?string $title = 'Dashboard Manajerial';

    protected static ?string $navigationLabel = 'Ringkasan';

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan progres dan KPI seluruh lokasi. Klik kartu untuk melihat data lengkapnya.';
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }

    /** Hanya ringkasan; widget lain tampil di halaman Monitoring masing-masing. */
    public function getWidgets(): array
    {
        return [ManagementKpi::class, ManagementTrendChart::class, ManagementLocationProgressChart::class, ManagementAlerts::class, ManagementStages::class];
    }
}
