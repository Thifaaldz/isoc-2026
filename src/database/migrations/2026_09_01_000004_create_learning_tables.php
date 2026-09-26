<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique(); // Modul 1..6
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('resource_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['participant_id', 'module_id']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10); // pre / post
            $table->string('title');
            $table->string('form_url')->nullable(); // tautan formulir daring
            $table->unsignedSmallInteger('passing_score')->default(85);
            $table->boolean('is_open')->default(true);
            $table->timestamps();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('threat_identification', 5, 2)->nullable(); // % identifikasi ancaman
            $table->decimal('self_efficacy', 5, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('microsite_practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('sid_url'); // tautan s.id / microsite
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('submitted'); // submitted/reviewed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('microsite_practices');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('module_progress');
        Schema::dropIfExists('modules');
    }
};
