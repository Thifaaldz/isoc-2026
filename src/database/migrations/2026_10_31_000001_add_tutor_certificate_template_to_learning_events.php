<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->foreignId('tutor_certificate_template_id')
                ->nullable()
                ->after('certificate_template_id')
                ->constrained('certificate_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tutor_certificate_template_id');
        });
    }
};
