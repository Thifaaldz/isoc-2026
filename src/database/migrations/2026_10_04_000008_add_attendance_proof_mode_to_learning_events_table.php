<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_events', 'attendance_proof_mode')) {
                // Bukti absensi laporan final: "system" (daftar hadir dari kode absensi) atau "manual" (upload absensi basah).
                $table->string('attendance_proof_mode', 10)->nullable()->after('final_report_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (Schema::hasColumn('learning_events', 'attendance_proof_mode')) {
                $table->dropColumn('attendance_proof_mode');
            }
        });
    }
};
