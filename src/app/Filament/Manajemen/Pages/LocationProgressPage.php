<?php

namespace App\Filament\Manajemen\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;
use App\Filament\Manajemen\Widgets\ManagementProgress;

/** Ringkasan progres setiap lokasi; klik lokasi untuk Detail Lokasi. */
class LocationProgressPage extends Page
{
    protected static ?string $navigationGroup = 'Monitoring';

    protected static string $view = 'filament.manajemen.page';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected static ?string $title = 'Progres Lokasi';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'progres-lokasi';

    public function getSubheading(): ?string
    {
        return 'Ringkasan tahapan dan capaian setiap lokasi. Klik nama lokasi untuk melihat seluruh datanya.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ManagementProgress::class];
    }
}
