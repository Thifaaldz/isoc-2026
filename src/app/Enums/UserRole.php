<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Tutor = 'tutor';
    case Peserta = 'peserta';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Tutor => 'Tutor',
            self::Peserta => 'Peserta',
        };
    }

    /** ID panel Filament yang boleh diakses role ini. */
    public function panelId(): string
    {
        return match ($this) {
            self::SuperAdmin => 'superadmin',
            self::Admin => 'admin',
            self::Tutor => 'tutor',
            self::Peserta => 'peserta',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all();
    }
}
