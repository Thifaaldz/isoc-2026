<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npsn', 20)->nullable()->unique();
            $table->string('type', 10)->default('SMA'); // SMA / SMK
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->text('address')->nullable();
            $table->string('pic_name')->nullable();
            $table->string('pic_phone', 30)->nullable();
            $table->unsignedSmallInteger('participant_target')->default(100);
            $table->date('training_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
