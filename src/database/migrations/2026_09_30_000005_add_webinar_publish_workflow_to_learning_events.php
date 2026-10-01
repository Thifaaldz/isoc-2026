<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->string('event_type', 30)->default('offline')->after('description');
            $table->string('zoom_url')->nullable()->after('event_type');
            $table->string('publish_approval_status', 40)->default('draft')->after('workflow_status');
            $table->text('publish_revision_notes')->nullable()->after('central_admin_notes');
            $table->text('local_update_summary')->nullable()->after('publish_revision_notes');
            $table->timestamp('local_updated_at')->nullable()->after('local_update_summary');
            $table->foreignId('publish_approved_by')->nullable()->after('local_updated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('publish_approved_at')->nullable()->after('publish_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropForeign(['publish_approved_by']);
            $table->dropColumn([
                'event_type',
                'zoom_url',
                'publish_approval_status',
                'publish_revision_notes',
                'local_update_summary',
                'local_updated_at',
                'publish_approved_by',
                'publish_approved_at',
            ]);
        });
    }
};
