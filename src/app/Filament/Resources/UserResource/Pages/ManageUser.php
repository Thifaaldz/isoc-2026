<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Widgets\UserStatsOverview;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageUser extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Buat pengguna')];
    }

    protected function getHeaderWidgets(): array
    {
        return [UserStatsOverview::class];
    }

    /** Tab filter cepat per role, lengkap dengan jumlah akun. */
    public function getTabs(): array
    {
        $counts = User::query()->selectRaw('role, COUNT(*) c')->groupBy('role')->pluck('c', 'role');
        $tab = fn (string $label, string $icon, UserRole ...$roles) => Tab::make($label)
            ->icon($icon)
            ->badge(collect($roles)->sum(fn (UserRole $r) => $counts[$r->value] ?? 0))
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', array_map(fn (UserRole $r) => $r->value, $roles)));

        return [
            'semua' => Tab::make('Semua')->icon('heroicon-o-users')->badge($counts->sum()),
            'peserta' => $tab('Peserta', 'heroicon-o-academic-cap', UserRole::Peserta),
            'tutor' => $tab('Tutor', 'heroicon-o-presentation-chart-bar', UserRole::Tutor),
            'daerah' => $tab('Admin RTIK Daerah', 'heroicon-o-map-pin', UserRole::Admin),
            'pusat' => $tab('Admin RTIK Pusat', 'heroicon-o-shield-check', UserRole::SuperAdmin),
        ];
    }
}
