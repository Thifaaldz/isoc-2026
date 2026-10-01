<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('school_id')->constrained('users')->nullOnDelete();
            $table->string('workflow_status', 50)->default('draft')->after('status');
            $table->unsignedSmallInteger('target_participants')->default(100)->after('workflow_status');
            $table->unsignedTinyInteger('target_tutors')->default(3)->after('target_participants');
            $table->string('training_start_time', 5)->default('09:00')->after('ends_at');
            $table->string('training_end_time', 5)->default('12:00')->after('training_start_time');
            $table->string('attendance_code', 20)->nullable()->after('training_end_time');
            $table->string('participant_import_file')->nullable()->after('attendance_code');
            $table->string('tutor_import_file')->nullable()->after('participant_import_file');
            $table->string('preparation_document')->nullable()->after('tutor_import_file');
            $table->string('rab_file')->nullable()->after('preparation_document');
            $table->json('participant_rows')->nullable()->after('rab_file');
            $table->json('tutor_rows')->nullable()->after('participant_rows');
            $table->json('budget_items')->nullable()->after('tutor_rows');
            $table->json('evidence_checklist')->nullable()->after('budget_items');
            $table->text('local_admin_notes')->nullable()->after('evidence_checklist');
            $table->text('central_admin_notes')->nullable()->after('local_admin_notes');
        });

        Schema::create('learning_event_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_event_id', 'tutor_id'], 'let_event_tutor_unique');
        });

        Schema::create('tot_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->boolean('is_perfect')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['learning_event_id', 'tutor_id'], 'tot_event_tutor_unique');
        });

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique(['school_id', 'term']);
            });
        } catch (Throwable) {
            //
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('learning_event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->date('due_at')->nullable()->after('paid_at');
            $table->foreignId('approved_by')->nullable()->after('due_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('reminder_sent_at')->nullable()->after('approved_at');
            $table->json('checklist')->nullable()->after('reminder_sent_at');
            $table->string('invoice_path')->nullable()->after('checklist');
            $table->string('receipt_path')->nullable()->after('invoice_path');
            $table->unique(['learning_event_id', 'term'], 'payments_event_term_unique');
        });

        Schema::table('evidences', function (Blueprint $table) {
            $table->foreignId('learning_event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('session_index')->nullable()->after('type');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->foreignId('learning_event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('eligibility_status', 30)->default('pending')->after('status');
            $table->timestamp('eligibility_checked_at')->nullable()->after('eligibility_status');
            $table->text('eligibility_notes')->nullable()->after('eligibility_checked_at');
        });

        try {
            Schema::table('certificates', function (Blueprint $table) {
                $table->dropUnique(['participant_id']);
            });
        } catch (Throwable) {
            //
        }

        foreach (DB::table('certificates')->select(['id', 'participant_id'])->get() as $certificate) {
            $eventId = DB::table('learning_event_participant')
                ->where('participant_id', $certificate->participant_id)
                ->value('learning_event_id');

            DB::table('certificates')
                ->where('id', $certificate->id)
                ->update(['learning_event_id' => $eventId]);
        }

        Schema::table('certificates', function (Blueprint $table) {
            $table->unique(['learning_event_id', 'participant_id'], 'cert_event_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique('cert_event_participant_unique');
            $table->dropForeign(['learning_event_id']);
            $table->dropColumn(['learning_event_id', 'eligibility_status', 'eligibility_checked_at', 'eligibility_notes']);
            $table->unique('participant_id');
        });

        Schema::table('evidences', function (Blueprint $table) {
            $table->dropForeign(['learning_event_id']);
            $table->dropColumn(['learning_event_id', 'session_index']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_event_term_unique');
            $table->dropForeign(['learning_event_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'learning_event_id',
                'due_at',
                'approved_by',
                'approved_at',
                'reminder_sent_at',
                'checklist',
                'invoice_path',
                'receipt_path',
            ]);
            $table->unique(['school_id', 'term']);
        });

        Schema::dropIfExists('tot_assessments');
        Schema::dropIfExists('learning_event_tutor');

        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'school_id',
                'created_by',
                'workflow_status',
                'target_participants',
                'target_tutors',
                'training_start_time',
                'training_end_time',
                'attendance_code',
                'participant_import_file',
                'tutor_import_file',
                'preparation_document',
                'rab_file',
                'participant_rows',
                'tutor_rows',
                'budget_items',
                'evidence_checklist',
                'local_admin_notes',
                'central_admin_notes',
            ]);
        });
    }
};
