<?php

namespace App\Filament\Manajemen\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

/**
 * Pola halaman Monitoring: ringkasan (header widgets) dulu, lalu tabel data lengkap di bawahnya.
 * Tabel hanya baca: tidak ada aksi ubah/hapus.
 */
abstract class ManagementDataPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Monitoring';

    protected static string $view = 'filament.manajemen.data-page';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    /** Judul bagian tabel data; null = halaman tanpa tabel. */
    public function dataHeading(): ?string
    {
        return null;
    }

    public function dataDescription(): ?string
    {
        return null;
    }
}
