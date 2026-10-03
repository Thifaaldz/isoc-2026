<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('module_templates', 'source_template_id')) {
                // Materi tutor auto-generated dari Materi Event peserta ini.
                $table->foreignId('source_template_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('module_templates')
                    ->cascadeOnDelete();
            }
        });

        // Materi ToT DSC yang sudah ada isinya sama dengan materi siswa; tandai sebagai hasil generate.
        $sourceId = DB::table('module_templates')->where('name', 'DSC Online Trust & Safety - Modul Siswa (6 Modul)')->value('id');

        if ($sourceId) {
            DB::table('module_templates')
                ->where('name', 'DSC Online Trust & Safety - ToT Tutor (6 Modul)')
                ->whereNull('source_template_id')
                ->update(['source_template_id' => $sourceId]);
        }
    }

    public function down(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            if (Schema::hasColumn('module_templates', 'source_template_id')) {
                $table->dropConstrainedForeignId('source_template_id');
            }
        });
    }
};
