<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('registration_open')->default(true);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_event_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('registered');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_event_id', 'participant_id'], 'lep_event_participant_unique');
        });

        Schema::create('learning_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_event_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->string('title');
            $table->string('type', 20)->default('pdf');
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('learning_event_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('learning_meeting_id')->nullable()->after('learning_event_id')->constrained()->nullOnDelete();
            $table->json('questions')->nullable()->after('form_url');
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('participant_id');
            $table->unsignedSmallInteger('correct_count')->default(0)->after('answers');
            $table->unsignedSmallInteger('total_questions')->default(0)->after('correct_count');
        });

        $eventId = DB::table('learning_events')->insertGetId([
            'title' => 'Digital Safety Champions',
            'slug' => 'digital-safety-champions',
            'description' => 'Event pembelajaran literasi keamanan digital ISOC.',
            'starts_at' => '2026-10-24 09:00:00',
            'status' => 'active',
            'registration_open' => true,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('participants')->pluck('id') as $participantId) {
            DB::table('learning_event_participant')->insert([
                'learning_event_id' => $eventId,
                'participant_id' => $participantId,
                'status' => 'registered',
                'registered_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $meetingId = DB::table('learning_meetings')->insertGetId([
            'learning_event_id' => $eventId,
            'order' => 1,
            'title' => 'Pertemuan 1: Keamanan Digital Dasar',
            'description' => 'Materi awal untuk mengenali risiko digital dan praktik aman.',
            'starts_at' => '2026-10-24 09:00:00',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('learning_materials')->insert([
            'learning_meeting_id' => $meetingId,
            'order' => 1,
            'title' => 'Materi Pembuka',
            'type' => 'link',
            'external_url' => 'https://www.internetsociety.org/',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('assessments')
            ->whereNull('learning_event_id')
            ->update([
                'learning_event_id' => $eventId,
                'learning_meeting_id' => $meetingId,
                'questions' => json_encode([
                    [
                        'question' => 'Apa langkah paling aman saat menerima tautan mencurigakan?',
                        'options' => [
                            ['text' => 'Langsung membukanya agar tahu isi tautan', 'is_correct' => false],
                            ['text' => 'Memeriksa sumber dan tidak memasukkan data pribadi', 'is_correct' => true],
                            ['text' => 'Membagikannya ke teman lebih dulu', 'is_correct' => false],
                            ['text' => 'Mengabaikan semua pesan dari internet', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question' => 'Apa tujuan kata sandi yang kuat dan unik?',
                        'options' => [
                            ['text' => 'Agar mudah ditebak teman dekat', 'is_correct' => false],
                            ['text' => 'Mengurangi risiko akun lain ikut bocor', 'is_correct' => true],
                            ['text' => 'Supaya semua akun memakai password sama', 'is_correct' => false],
                            ['text' => 'Agar tidak perlu autentikasi tambahan', 'is_correct' => false],
                        ],
                    ],
                ]),
            ]);
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            $table->dropColumn(['answers', 'correct_count', 'total_questions']);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['learning_meeting_id']);
            $table->dropForeign(['learning_event_id']);
            $table->dropColumn(['learning_meeting_id', 'learning_event_id', 'questions']);
        });

        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('learning_meetings');
        Schema::dropIfExists('learning_event_participant');
        Schema::dropIfExists('learning_events');
    }
};
