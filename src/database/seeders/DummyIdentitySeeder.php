<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

/**
 * NIK/NISN dummy (semua angka 0) untuk peserta dan tutor yang datanya masih kosong.
 * Jalankan: php artisan db:seed --class=DummyIdentitySeeder
 */
class DummyIdentitySeeder extends Seeder
{
    public const DUMMY_NIK = '0000000000000000';  // 16 digit

    public const DUMMY_NISN = '0000000000';       // 10 digit

    public function run(): void
    {
        $empty = fn (string $column) => fn ($query) => $query->whereNull($column)->orWhere($column, '');

        Participant::query()->where($empty('nik'))->update(['nik' => self::DUMMY_NIK]);
        Participant::query()->where($empty('nis'))->update(['nis' => self::DUMMY_NISN]);
        Tutor::query()->where($empty('nik'))->update(['nik' => self::DUMMY_NIK]);
    }
}
