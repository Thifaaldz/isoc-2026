<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assessments', 'module_template_id')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->foreignId('module_template_id')
                    ->nullable()
                    ->after('learning_meeting_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('assessments', 'module_template_id')) {
            Schema::table('assessments', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('module_template_id');
            });
        }
    }
};
