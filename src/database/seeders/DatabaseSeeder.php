<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Semua akun memakai password: "password" (di-hash otomatis oleh cast User). */
    public function run(): void
    {
        if (function_exists('activity')) {
            activity()->disableLogging();
        }

        User::query()->firstOrCreate(['email' => 'su@isoc.id'], ['name' => 'Admin ISOC', 'password' => 'password', 'role' => UserRole::SuperAdmin]);
        User::query()->firstOrCreate(['email' => 'adm@isoc.id'], ['name' => 'Fasilitator', 'password' => 'password', 'role' => UserRole::Admin]);

        $this->call([
            CertificateTemplateSeeder::class,
            PartnerSeeder::class,
            MateriSeeder::class,
            RtikDaerahSeeder::class,
            DummyIdentitySeeder::class,
        ]);
    }
}
