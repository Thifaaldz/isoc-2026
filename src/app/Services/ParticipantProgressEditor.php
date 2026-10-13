<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\Participant;
use Illuminate\Validation\ValidationException;

/**
 * Tutor/Admin memperbarui data peserta di satu event dari halaman manajemen peserta:
 * nilai pre/post-test, link s.id, dan absensi. Sertifikat dihitung ulang setelah setiap perubahan.
 */
class ParticipantProgressEditor
{
    public const ATTENDANCE_STATUSES = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];

    public function __construct(private CertificateEligibilityService $certificates) {}

    /** Nilai terbaik peserta (sama dengan yang dipakai rekap & sertifikat). */
    public function score(LearningEvent $event, Participant $participant, string $type): ?float
    {
        $score = AssessmentAttempt::query()
            ->where('participant_id', $participant->id)
            ->whereIn('assessment_id', $event->assessments()->where('type', $type)->select('id'))
            ->max('score');

        return $score === null ? null : (float) $score;
    }

    /**
     * Nilai dari tutor menjadi nilai yang berlaku: attempt terbaik diganti nilainya (attempt lain yang lebih tinggi
     * ikut diturunkan), atau attempt baru dibuat bila peserta belum pernah mengerjakan.
     *
     * @throws ValidationException
     */
    public function setScore(LearningEvent $event, Participant $participant, string $type, float $score): void
    {
        $assessment = $event->assessments()->where('type', $type)->orderBy('id')->first();

        if (! $assessment) {
            throw ValidationException::withMessages([
                "{$type}_score" => ($type === 'pre' ? 'Pre-Test' : 'Post-Test') . ' belum dibuat untuk event ini.',
            ]);
        }

        $attempts = AssessmentAttempt::query()
            ->where('participant_id', $participant->id)
            ->whereIn('assessment_id', $event->assessments()->where('type', $type)->select('id'))
            ->get();
        $values = ['score' => $score, 'threat_identification' => $score, 'self_efficacy' => $score];

        if ($attempts->isEmpty()) {
            AssessmentAttempt::query()->create([
                'assessment_id' => $assessment->id,
                'participant_id' => $participant->id,
                ...$values,
                'submitted_at' => now(),
            ]);
        } else {
            $best = $attempts->sortByDesc('score')->first();
            $attempts->filter(fn (AssessmentAttempt $attempt) => $attempt->is($best) || (float) $attempt->score > $score)
                ->each(fn (AssessmentAttempt $attempt) => $attempt->update($values));
        }

        $this->certificates->ensureCertificate($participant, $event);
    }

    public function micrositeUrl(LearningEvent $event, Participant $participant): ?string
    {
        return MicrositePractice::query()
            ->where('participant_id', $participant->id)
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->latest()
            ->value('sid_url');
    }

    /** Link s.id yang sudah divalidasi (ReachableMicrositeUrl) disimpan seperti saat peserta mengisinya sendiri. */
    public function setMicrosite(LearningEvent $event, Participant $participant, string $url): void
    {
        MicrositePractice::query()->updateOrCreate(
            ['participant_id' => $participant->id, 'learning_event_id' => $event->id],
            ['sid_url' => $url, 'status' => 'reviewed'],
        );

        $this->certificates->ensureCertificate($participant, $event);
    }

    /** Catat / ubah absensi peserta (method manual). */
    public function setAttendance(LearningEvent $event, Participant $participant, string $status, ?string $mode, ?int $recordedBy): Attendance
    {
        $modes = app(EventAttendanceService::class)->modesFor($event);
        $existing = app(EventAttendanceService::class)->attendanceFor($participant, $event);

        return Attendance::query()->updateOrCreate(
            ['learning_event_id' => $event->id, 'participant_id' => $participant->id],
            [
                'status' => $status,
                'mode' => array_key_exists((string) $mode, $modes) ? $mode : array_key_first($modes),
                'method' => 'manual',
                // Jam absen asli dipertahankan bila peserta sebelumnya sudah hadir.
                'checked_in_at' => $status === 'hadir' ? ($existing?->status === 'hadir' ? $existing->checked_in_at : now()) : null,
                'recorded_by' => $recordedBy,
            ],
        );
    }
}
