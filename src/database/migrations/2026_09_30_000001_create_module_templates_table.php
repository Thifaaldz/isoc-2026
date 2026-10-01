<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('meetings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('learning_events', function (Blueprint $table) {
            $table->foreignId('module_template_id')->nullable()->after('school_id')->constrained('module_templates')->nullOnDelete();
        });

        Schema::table('learning_meetings', function (Blueprint $table) {
            $table->string('task_title')->nullable()->after('description');
            $table->text('task_description')->nullable()->after('task_title');
        });
    }

    public function down(): void
    {
        Schema::table('learning_meetings', function (Blueprint $table) {
            $table->dropColumn(['task_title', 'task_description']);
        });

        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('module_template_id');
        });

        Schema::dropIfExists('module_templates');
    }
};
