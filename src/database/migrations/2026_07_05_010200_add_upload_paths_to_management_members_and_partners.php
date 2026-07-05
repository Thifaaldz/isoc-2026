<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('management_members', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('photo_url');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('management_members', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
