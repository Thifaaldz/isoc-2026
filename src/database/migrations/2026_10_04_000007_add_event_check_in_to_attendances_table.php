<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Absensi lewat kode event tidak terikat ke Jadwal Sesi.
            $table->foreignId('training_session_id')->nullable()->change();

            if (! Schema::hasColumn('attendances', 'learning_event_id')) {
                $table->foreignId('learning_event_id')->nullable()->after('training_session_id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('attendances', 'mode')) {
                $table->string('mode', 10)->nullable()->after('status'); // offline/online
            }
            if (! Schema::hasColumn('attendances', 'method')) {
                $table->string('method', 10)->default('manual')->after('mode'); // manual/kode
            }
            if (! Schema::hasColumn('attendances', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('method');
            }
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['learning_event_id', 'participant_id'], 'attendances_event_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_event_participant_unique');
            $table->dropConstrainedForeignId('learning_event_id');
            $table->dropColumn(['mode', 'method', 'checked_in_at']);
        });
    }
};
