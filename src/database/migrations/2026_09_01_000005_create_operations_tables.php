<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('date');
            $table->time('start_time')->default('09:00');
            $table->time('end_time')->default('12:00'); // 180 menit
            $table->string('status', 20)->default('planned'); // planned/running/done
            $table->text('notes')->nullable();      // catatan simulasi, role-play, refleksi
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('hadir'); // hadir/izin/sakit/alpa
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // absensi_basah, follow_ig, join_wag, registrasi_ecert, foto_wajib, video_slogan
            $table->string('file_path')->nullable();
            $table->string('link')->nullable();
            $table->string('status', 20)->default('pending'); // pending/approved/rejected
            $table->text('review_notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wag_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // WAG ISOC Champion - <sekolah>
            $table->string('invite_link')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->unsignedInteger('active_members')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('peer_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('member_count')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status', 20)->default('pending'); // pending/issued/revoked
            $table->timestamp('issued_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('term'); // Termin 1 / 2
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending'); // pending/eligible/paid
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'term']);
        });
    }

    public function down(): void
    {
        foreach (['payments', 'certificates', 'peer_groups', 'wag_groups', 'evidences', 'attendances', 'training_sessions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
