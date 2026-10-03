<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_events', 'selected_meeting_ids')) {
                // Pertemuan/modul dari Materi Event yang dibawakan di event ini (kosong = semua, untuk event lama).
                $table->json('selected_meeting_ids')->nullable()->after('module_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            if (Schema::hasColumn('learning_events', 'selected_meeting_ids')) {
                $table->dropColumn('selected_meeting_ids');
            }
        });
    }
};
