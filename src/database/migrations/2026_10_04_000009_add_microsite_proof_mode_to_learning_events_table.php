<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_events', 'microsite_proof_mode')) {
                // Bukti hasil microsite laporan final: "system" (rekap link microsite peserta) atau "manual" (upload/tautan).
                $table->string('microsite_proof_mode', 10)->nullable()->after('attendance_proof_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (Schema::hasColumn('learning_events', 'microsite_proof_mode')) {
                $table->dropColumn('microsite_proof_mode');
            }
        });
    }
};
