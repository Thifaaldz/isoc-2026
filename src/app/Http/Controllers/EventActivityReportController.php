<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\MicrositePractice;
use App\Support\TorEventTemplate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EventActivityReportController extends Controller
{
    public function preview(LearningEvent $event): Response
    {
        abort_unless($this->canAccess($event), 403);

        return $this->pdf($event)->stream($this->filename($event));
    }

    public function download(LearningEvent $event): Response
    {
        abort_unless($this->canAccess($event), 403);

        return $this->pdf($event)->download($this->filename($event));
    }

    private function canAccess(LearningEvent $event): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return match ($user->role) {
            UserRole::SuperAdmin => true,
            // Fasilitator hanya membuka laporan event yang ia buat.
            UserRole::Admin => (int) $event->created_by === (int) $user->id,
            UserRole::Tutor => $event->tutors()->where('tutors.user_id', $user->id)->exists(),
            default => false,
        };
    }

    private function pdf(LearningEvent $event)
    {
        $event->load([
            'school',
            'participants.user',
            'tutors.user',
            'meetings.materials',
            'assessments',
            'evidences',
            'orderedPartners',
            'creator',
        ]);

        $assessmentIds = $event->assessments->pluck('id');
        $attempts = AssessmentAttempt::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->get();

        $averageScore = function (string $type) use ($event, $attempts): ?float {
            $ids = $event->assessments->where('type', $type)->pluck('id');

            if ($ids->isEmpty()) {
                return null;
            }

            $scores = $attempts->whereIn('assessment_id', $ids)->pluck('score')->filter(fn ($score) => $score !== null);

            return $scores->isEmpty() ? null : round((float) $scores->avg(), 2);
        };

        $assessmentIdsByType = fn (string $type) => $event->assessments->where('type', $type)->pluck('id');
        $scoreFor = function (int $participantId, string $type) use ($attempts, $assessmentIdsByType): ?float {
            $scores = $attempts
                ->where('participant_id', $participantId)
                ->whereIn('assessment_id', $assessmentIdsByType($type))
                ->pluck('score')
                ->filter(fn ($score) => $score !== null);

            return $scores->isEmpty() ? null : round((float) $scores->avg(), 2);
        };

        // Rata-rata kolom asesmen lain (identifikasi ancaman, self-efficacy) per jenis tes.
        $averageColumn = function (string $type, string $column) use ($attempts, $assessmentIdsByType): ?float {
            $values = $attempts->whereIn('assessment_id', $assessmentIdsByType($type))->pluck($column)->filter(fn ($value) => $value !== null);

            return $values->isEmpty() ? null : round((float) $values->avg(), 2);
        };

        $completionFor = function (string $type) use ($attempts, $assessmentIdsByType): int {
            return $attempts
                ->whereIn('assessment_id', $assessmentIdsByType($type))
                ->pluck('participant_id')
                ->unique()
                ->count();
        };

        $participantScores = $event->participants
            ->sortBy(fn ($participant) => $participant->user?->name)
            ->values()
            ->map(fn ($participant) => [
                'name' => $participant->user?->name ?? '-',
                'email' => $participant->user?->email ?? '-',
                'identity' => $participant->nis ?: '-',
                'nik' => $participant->nik ?: '-',
                'grade' => $participant->grade ?: '-',
                'pre' => $scoreFor($participant->id, 'pre'),
                'post' => $scoreFor($participant->id, 'post'),
                'quiz' => $scoreFor($participant->id, 'quiz'),
                'joined_wag' => (bool) $participant->joined_wag,
                'followed_instagram' => (bool) $participant->followed_instagram,
            ]);

        $participants = $event->participants;
        $participantCount = $participants->count();
        $evidences = $event->evidences;
        $approvedTypeCount = fn (string $type) => $evidences->where('type', $type)->where('status', 'approved')->count();
        $microsites = MicrositePractice::query()
            ->with('participant.user')
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->latest()
            ->get()
            ->unique('participant_id');
        $issuedCertificates = Certificate::query()->where('learning_event_id', $event->id)->where('status', 'issued')->count();
        $followedInstagram = $participants->where('followed_instagram', true)->count();
        $joinedWag = $participants->where('joined_wag', true)->count();
        $preCompleted = $completionFor('pre');
        $postCompleted = $completionFor('post');
        $photoCounts = Evidence::photoCounts($event->id, approvedOnly: true);
        $legacyPhotos = $approvedTypeCount('foto_sesi');
        $targetParticipants = (int) ($event->target_participants ?: TorEventTemplate::DEFAULT_PARTICIPANTS);
        $targetTutors = (int) ($event->target_tutors ?: TorEventTemplate::DEFAULT_TUTORS);

        // Bukti dukung sesuai TOR ("Bukti Dukung" poin 1-7) beserta capaiannya di lokasi ini.
        $torEvidence = [
            ['Absensi nama & tanda tangan basah', "{$targetTutors} tutor + {$targetParticipants} peserta", $approvedTypeCount('absensi_basah') . ' berkas disetujui', $approvedTypeCount('absensi_basah') > 0],
            ['Follow Instagram ISOC @isoc.id.jkt', "{$participantCount} peserta", "{$followedInstagram} peserta", $participantCount > 0 && $followedInstagram >= $participantCount],
            ['Join WAG ISOC Champion', "{$participantCount} peserta", "{$joinedWag} peserta", $participantCount > 0 && $joinedWag >= $participantCount],
            ['Registrasi laman ISOC untuk e-Sertifikat', "{$participantCount} peserta", "{$participantCount} terdaftar, {$issuedCertificates} e-Sertifikat terbit", $participantCount > 0],
        ];

        foreach (Evidence::REQUIRED_PHOTOS as $type => $photo) {
            // Status laporan dihitung dari jumlah foto per jenis yang benar-benar disetujui.
            $torEvidence[] = ["Foto {$photo['step']}. {$photo['instruction']}", "{$photo['min']} foto", "{$photoCounts[$type]} foto disetujui", $photoCounts[$type] >= $photo['min']];
        }

        if ($legacyPhotos > 0) {
            $torEvidence[] = ['Foto kegiatan per sesi (format lama, sebelum checklist foto wajib a-g)', '-', "{$legacyPhotos} foto disetujui", true];
        }

        $torEvidence[] = ['Video Tutor & Peserta dengan slogan "ISOC - The Internet is for Everyone"', '1 video', $approvedTypeCount('video_slogan') . ' video disetujui', $approvedTypeCount('video_slogan') > 0];
        $torEvidence[] = ['Pre-Test di awal dan Post-Test di akhir sesi', "{$participantCount} peserta", "Pre {$preCompleted}, Post {$postCompleted} peserta", $participantCount > 0 && $preCompleted >= $participantCount && $postCompleted >= $participantCount];

        return Pdf::loadView('reports.event-activity', [
            'torEvidence' => $torEvidence,
            'microsites' => $microsites,
            'issuedCertificates' => $issuedCertificates,
            'followedInstagram' => $followedInstagram,
            'joinedWag' => $joinedWag,
            'targetParticipants' => $targetParticipants,
            'targetTutors' => $targetTutors,
            'threatAverage' => $averageColumn('post', 'threat_identification'),
            'selfEfficacyPre' => $averageColumn('pre', 'self_efficacy'),
            'selfEfficacyPost' => $averageColumn('post', 'self_efficacy'),
            'event' => $event,
            'school' => $event->school,
            'participants' => $event->participants,
            'tutors' => $event->tutors,
            'meetings' => $event->meetings->sortBy('order'),
            'evidences' => $event->evidences->sortBy('type'),
            'evidenceTypes' => Evidence::TYPES,
            'preAverage' => $averageScore('pre'),
            'postAverage' => $averageScore('post'),
            'quizAverage' => $averageScore('quiz'),
            'preCompleted' => $completionFor('pre'),
            'postCompleted' => $completionFor('post'),
            'quizCompleted' => $completionFor('quiz'),
            'participantScores' => $participantScores,
            'attempts' => $attempts,
            'preTotal' => Assessment::query()->where('learning_event_id', $event->id)->where('type', 'pre')->count(),
            'postTotal' => Assessment::query()->where('learning_event_id', $event->id)->where('type', 'post')->count(),
        ])->setPaper('a4', 'portrait')->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);
    }

    private function filename(LearningEvent $event): string
    {
        return 'laporan-kegiatan-' . Str::slug($event->title) . '.pdf';
    }
}
