<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_event_partner', function (Blueprint $table) {
            if (! Schema::hasColumn('learning_event_partner', 'show_on_certificate')) {
                // Logo mitra ini ikut tampil di sertifikat peserta event.
                $table->boolean('show_on_certificate')->default(true)->after('sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_event_partner', function (Blueprint $table) {
            if (Schema::hasColumn('learning_event_partner', 'show_on_certificate')) {
                $table->dropColumn('show_on_certificate');
            }
        });
    }
};
