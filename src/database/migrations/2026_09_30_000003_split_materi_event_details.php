<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_meetings', function (Blueprint $table) {
            $table->foreignId('module_template_id')
                ->nullable()
                ->after('learning_event_id')
                ->constrained('module_templates')
                ->cascadeOnDelete();
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE learning_meetings MODIFY learning_event_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        DB::table('learning_meetings')->whereNotNull('module_template_id')->delete();

        Schema::table('learning_meetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_template_id');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE learning_meetings MODIFY learning_event_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
