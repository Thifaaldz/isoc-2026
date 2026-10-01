<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('orientation', 20)->default('landscape');
            $table->unsignedSmallInteger('width_mm')->default(297);
            $table->unsignedSmallInteger('height_mm')->default(210);
            $table->string('background_color', 20)->default('#ffffff');
            $table->string('background_image')->nullable();
            $table->json('elements')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('certificate_template_id')
                ->nullable()
                ->after('participant_id')
                ->constrained('certificate_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certificate_template_id');
        });

        Schema::dropIfExists('certificate_templates');
    }
};
