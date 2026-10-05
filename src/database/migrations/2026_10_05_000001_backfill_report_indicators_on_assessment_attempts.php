<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi indikator laporan (identifikasi ancaman & self-efficacy) dari skor pre-test/post-test
 * untuk hasil tes lama yang belum terisi. Nilai yang sudah diisi manual tidak diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $ids = DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->whereIn('assessments.type', ['pre', 'post'])
            ->whereNotNull('assessment_attempts.score')
            ->pluck('assessment_attempts.id');

        DB::table('assessment_attempts')->whereIn('id', $ids)->whereNull('threat_identification')->update(['threat_identification' => DB::raw('score')]);
        DB::table('assessment_attempts')->whereIn('id', $ids)->whereNull('self_efficacy')->update(['self_efficacy' => DB::raw('score')]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: tidak bisa membedakan nilai hasil backfill dengan nilai yang diisi manual.
    }
};
