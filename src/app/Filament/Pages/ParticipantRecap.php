<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\ParticipantRecapTable;
use Filament\Pages\Page;

/** Admin RTIK Daerah: daftar peserta yang sudah pre-test, post-test, join WhatsApp Group, dan follow Instagram ISOC. */
class ParticipantRecap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $navigationLabel = 'Peserta Lengkap';

    protected static ?string $title = 'Peserta Lengkap';

    protected static ?string $slug = 'peserta-lengkap';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.participant-recap';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getHeaderWidgets(): array
    {
        return [ParticipantRecapTable::class];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
}
