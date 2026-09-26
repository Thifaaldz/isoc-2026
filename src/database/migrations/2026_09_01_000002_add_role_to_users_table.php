<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('peserta')->index()->after('password');
            $table->string('phone', 30)->nullable()->after('role');
            $table->foreignId('school_id')->nullable()->after('phone')->constrained('schools')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('school_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['role', 'phone', 'is_active']);
        });
    }
};
