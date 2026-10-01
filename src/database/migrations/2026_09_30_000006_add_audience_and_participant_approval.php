<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->string('audience_type', 30)->default('school')->after('event_type');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->foreignId('school_id')->nullable()->change();
            $table->string('organization')->nullable()->after('grade');
            $table->string('position')->nullable()->after('organization');
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
        });

        Schema::table('learning_event_participant', function (Blueprint $table) {
            $table->string('admin_approval_status', 30)->default('approved')->after('status');
            $table->foreignId('admin_approved_by')->nullable()->after('admin_approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('admin_approved_at')->nullable()->after('admin_approved_by');
            $table->string('tutor_approval_status', 30)->default('approved')->after('admin_approved_at');
            $table->foreignId('tutor_approved_by')->nullable()->after('tutor_approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('tutor_approved_at')->nullable()->after('tutor_approved_by');
            $table->text('approval_notes')->nullable()->after('tutor_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('learning_event_participant', function (Blueprint $table) {
            $table->dropForeign(['admin_approved_by']);
            $table->dropForeign(['tutor_approved_by']);
            $table->dropColumn([
                'admin_approval_status',
                'admin_approved_by',
                'admin_approved_at',
                'tutor_approval_status',
                'tutor_approved_by',
                'tutor_approved_at',
                'approval_notes',
            ]);
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn(['organization', 'position']);
            $table->foreignId('school_id')->nullable(false)->change();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
        });

        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropColumn('audience_type');
        });
    }
};
