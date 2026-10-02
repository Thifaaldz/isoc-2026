<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->default('national');
            $table->string('subtitle')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('learning_event_partner', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['learning_event_id', 'partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_event_partner');
        Schema::dropIfExists('partners');
    }
};
