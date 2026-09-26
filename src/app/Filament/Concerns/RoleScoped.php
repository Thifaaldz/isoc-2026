<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Kontrol akses per role untuk Filament Resource yang dipakai bersama di 4 panel.
 * Override viewRoles(), manageRoles(), dan scopeType() di resource.
 *
 * scopeType():
 *   'school'      -> tabel punya kolom school_id (tutor & peserta hanya melihat sekolahnya)
 *   'school_self' -> resource School itu sendiri (kolom id)
 *   'participant' -> tabel punya participant_id (peserta hanya melihat miliknya, tutor per sekolah)
 *   null          -> tanpa pembatasan data
 */
trait RoleScoped
{
    /** @return array<UserRole> */
    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    /** @return array<UserRole> */
    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return null;
    }

    protected static function currentRole(): ?UserRole
    {
        return auth()->user()?->role;
    }

    protected static function roleIn(array $roles): bool
    {
        return in_array(static::currentRole(), $roles, true);
    }

    public static function canViewAny(): bool
    {
        return static::roleIn([...static::viewRoles(), ...static::manageRoles()]);
    }

    public static function canCreate(): bool
    {
        return static::roleIn(static::manageRoles());
    }

    public static function canEdit(Model $record): bool
    {
        return static::roleIn(static::manageRoles());
    }

    public static function canDelete(Model $record): bool
    {
        return static::roleIn(static::manageRoles());
    }

    public static function canDeleteAny(): bool
    {
        return static::roleIn(static::manageRoles());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || ! in_array($user->role, [UserRole::Tutor, UserRole::Peserta], true)) {
            return $query;
        }

        return match (static::scopeType()) {
            'school' => $query->where('school_id', $user->school_id),
            'session' => $query->whereHas('session', fn ($q) => $q->where('school_id', $user->school_id)),
            'school_self' => $query->where('id', $user->school_id),
            'participant' => $user->role === UserRole::Peserta
                ? $query->where('participant_id', $user->participant?->id)
                : $query->whereHas('participant', fn ($q) => $q->where('school_id', $user->school_id)),
            default => $query,
        };
    }
}
