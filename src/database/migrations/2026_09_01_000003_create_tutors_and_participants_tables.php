<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('institution')->nullable();
            $table->boolean('tot_completed')->default(false); // Training of Trainers
            $table->boolean('is_cadre')->default(false);       // kader pelatih mandiri
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('nis', 30)->nullable();
            $table->string('grade', 10)->nullable(); // X / XI / XII
            $table->string('gender', 1)->nullable(); // L / P
            $table->date('birth_date')->nullable();
            $table->timestamp('consent_at')->nullable();      // persetujuan data pribadi
            $table->boolean('followed_instagram')->default(false);
            $table->boolean('joined_wag')->default(false);
            $table->boolean('registered_ecert')->default(false);
            $table->boolean('is_cadre')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'nis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
        Schema::dropIfExists('tutors');
    }
};
