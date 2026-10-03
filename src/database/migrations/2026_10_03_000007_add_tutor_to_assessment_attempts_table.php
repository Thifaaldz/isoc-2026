<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pre-test & post-test ToT dikerjakan tutor, jadi attempt bisa milik tutor (bukan hanya peserta). */
    public function up(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table): void {
            $table->foreignId('participant_id')->nullable()->change();
            $table->foreignId('tutor_id')->nullable()->after('participant_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tutor_id');
        });
    }
};
