<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            $table->text('purpose')->nullable()->after('description');
            $table->unsignedSmallInteger('meeting_count')->default(1)->after('purpose');
        });

        Schema::table('learning_events', function (Blueprint $table) {
            $table->json('rundown_items')->nullable()->after('budget_items');
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropColumn('rundown_items');
        });

        Schema::table('module_templates', function (Blueprint $table) {
            $table->dropColumn(['purpose', 'meeting_count']);
        });
    }
};
