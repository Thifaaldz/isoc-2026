<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_templates', function (Blueprint $table): void {
            $table->string('audience', 20)->default('peserta')->after('name')->index();
        });

        // Materi Event yang sudah ada adalah modul ajar ToT, jadi dipakai sebagai materi tutor.
        DB::table('module_templates')->update(['audience' => 'tutor']);
    }

    public function down(): void
    {
        Schema::table('module_templates', function (Blueprint $table): void {
            $table->dropIndex(['audience']);
            $table->dropColumn('audience');
        });
    }
};
