<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Index gabungan untuk query yang paling sering dipakai saat banyak peserta aktif bersamaan. */
return new class extends Migration
{
    private const INDEXES = [
        'assessment_attempts' => ['attempts_participant_assessment_index' => ['participant_id', 'assessment_id']],
        'evidences' => ['evidences_event_type_status_index' => ['learning_event_id', 'type', 'status']],
        'microsite_practices' => ['microsite_participant_event_index' => ['participant_id', 'learning_event_id']],
        'learning_events' => ['learning_events_public_listing_index' => ['is_published', 'status', 'starts_at']],
        'learning_meetings' => ['learning_meetings_event_order_index' => ['learning_event_id', 'order']],
    ];

    public function up(): void
    {
        // NISN/NIM tidak wajib unik; unique lama masih ada di database yang menjalankan migrasi NIK lebih awal.
        if (Schema::hasIndex('participants', 'participants_school_id_nis_unique')) {
            Schema::table('participants', function (Blueprint $table) {
                if (! Schema::hasIndex('participants', 'participants_school_id_index')) {
                    $table->index('school_id');
                }
                $table->dropUnique(['school_id', 'nis']);
            });
        }

        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach ($indexes as $name => $columns) {
                    if (! Schema::hasIndex($tableName, $name)) {
                        $table->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach (array_keys($indexes) as $name) {
                    if (Schema::hasIndex($tableName, $name)) {
                        $table->dropIndex($name);
                    }
                }
            });
        }
    }
};
