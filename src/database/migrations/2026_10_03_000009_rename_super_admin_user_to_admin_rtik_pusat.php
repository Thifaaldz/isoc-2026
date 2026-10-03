<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Nama akun super admin bawaan diganti menjadi Admin RTIK Pusat. */
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'su@isoc.id')
            ->where('name', 'Super Admin ISOC')
            ->update(['name' => 'Admin RTIK Pusat']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'su@isoc.id')
            ->where('name', 'Admin RTIK Pusat')
            ->update(['name' => 'Super Admin ISOC']);
    }
};
