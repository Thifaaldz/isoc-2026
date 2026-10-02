<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            if (! Schema::hasColumn('participants', 'participant_category')) {
                $table->string('participant_category', 30)->default('pelajar')->after('school_id');
            }
        });

        Schema::table('microsite_practices', function (Blueprint $table) {
            if (! Schema::hasColumn('microsite_practices', 'learning_event_id')) {
                $table->foreignId('learning_event_id')
                    ->nullable()
                    ->after('participant_id')
                    ->constrained('learning_events')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('microsite_practices', function (Blueprint $table) {
            if (Schema::hasColumn('microsite_practices', 'learning_event_id')) {
                $table->dropForeign(['learning_event_id']);
                $table->dropColumn('learning_event_id');
            }
        });

        Schema::table('participants', function (Blueprint $table) {
            if (Schema::hasColumn('participants', 'participant_category')) {
                $table->dropColumn('participant_category');
            }
        });
    }
};
