<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tutor yang sudah terdaftar dan dipilih di wizard event; ditugaskan saat event di-approve Pusat. */
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->json('selected_tutor_ids')->nullable()->after('tutor_rows');
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->dropColumn('selected_tutor_ids');
        });
    }
};
