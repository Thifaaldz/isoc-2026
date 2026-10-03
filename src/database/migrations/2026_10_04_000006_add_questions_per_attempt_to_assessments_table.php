<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'questions_per_attempt')) {
                // Jumlah soal acak per peserta dari bank soal; kosong = semua soal.
                $table->unsignedTinyInteger('questions_per_attempt')->nullable()->after('passing_score');
            }
        });

        // Materi peserta saat ini (DSC Online Trust & Safety - Modul Siswa): Pre/Post-Test 5 soal acak per peserta.
        $templateIds = DB::table('module_templates')
            ->where('audience', 'peserta')
            ->where('name', 'DSC Online Trust & Safety - Modul Siswa (6 Modul)')
            ->pluck('id');

        if ($templateIds->isNotEmpty()) {
            $eventIds = DB::table('learning_events')->whereIn('module_template_id', $templateIds)->pluck('id');

            DB::table('assessments')
                ->whereIn('type', ['pre', 'post'])
                ->where(fn ($query) => $query->whereIn('module_template_id', $templateIds)->orWhereIn('learning_event_id', $eventIds))
                ->update(['questions_per_attempt' => 5]);
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'questions_per_attempt')) {
                $table->dropColumn('questions_per_attempt');
            }
        });
    }
};
