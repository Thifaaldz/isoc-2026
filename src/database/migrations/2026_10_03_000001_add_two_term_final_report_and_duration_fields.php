<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_events', 'final_report_status')) {
                $table->string('final_report_status', 30)->default('draft')->after('publish_approval_status');
            }

            if (! Schema::hasColumn('learning_events', 'final_report_submitted_at')) {
                $table->timestamp('final_report_submitted_at')->nullable()->after('final_report_status');
            }

            if (! Schema::hasColumn('learning_events', 'final_report_approved_by')) {
                $table->foreignId('final_report_approved_by')->nullable()->after('final_report_submitted_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('learning_events', 'final_report_approved_at')) {
                $table->timestamp('final_report_approved_at')->nullable()->after('final_report_approved_by');
            }

            if (! Schema::hasColumn('learning_events', 'final_report_notes')) {
                $table->text('final_report_notes')->nullable()->after('final_report_approved_at');
            }
        });

        Schema::table('learning_meetings', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_meetings', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(25)->after('description');
            }
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_materials', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('type');
            }
        });

        DB::table('payments')->where('term', '>', 2)->delete();
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table): void {
            if (Schema::hasColumn('learning_materials', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });

        Schema::table('learning_meetings', function (Blueprint $table): void {
            if (Schema::hasColumn('learning_meetings', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }
        });

        Schema::table('learning_events', function (Blueprint $table): void {
            if (Schema::hasColumn('learning_events', 'final_report_approved_by')) {
                $table->dropForeign(['final_report_approved_by']);
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('learning_events', 'final_report_status') ? 'final_report_status' : null,
                Schema::hasColumn('learning_events', 'final_report_submitted_at') ? 'final_report_submitted_at' : null,
                Schema::hasColumn('learning_events', 'final_report_approved_by') ? 'final_report_approved_by' : null,
                Schema::hasColumn('learning_events', 'final_report_approved_at') ? 'final_report_approved_at' : null,
                Schema::hasColumn('learning_events', 'final_report_notes') ? 'final_report_notes' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
