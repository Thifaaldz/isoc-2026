<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Tutor = 'tutor';
    case Peserta = 'peserta';
    /** Manajemen: hanya memantau progres & KPI seluruh program (read-only). */
    case Manajemen = 'manajemen';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Admin RTIK Pusat',
            self::Admin => 'Fasilitator (Admin RTIK Daerah)',
            self::Tutor => 'Tutor',
            self::Peserta => 'Peserta',
            self::Manajemen => 'Manajemen',
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
            self::Manajemen => 'manajemen',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all();
    }
}
