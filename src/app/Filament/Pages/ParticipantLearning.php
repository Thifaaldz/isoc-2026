<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\Participant;
use App\Services\CertificateEligibilityService;
use App\Services\EventAttendanceService;
use App\Support\MaterialViewer;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class ParticipantLearning extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Seminar & Materi';

    protected static ?string $navigationLabel = 'Modul';

    protected static ?string $title = 'Modul';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.participant-learning';

    /** Post-test yang nilainya belum mencapai passing score boleh diulang sebanyak ini (di luar percobaan pertama). */
    public const POST_TEST_RETAKES = 3;

    /** Batas lulus bila passing score tes belum diisi. */
    public const DEFAULT_PASSING_SCORE = 70;

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    public ?int $activeAssessmentId = null;

    public ?int $activeMaterialId = null;

    public ?int $activeMeetingId = null;

    /** @var array<int, string|int|null> */
    public array $answers = [];

    /** Cache per request (tidak disimpan di state Livewire) agar query tidak diulang setiap kali Blade memanggil getter. */
    protected array $memo = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public function mount(): void
    {
        if (! $this->events->firstWhere('id', (int) $this->selectedEventId)) {
            $this->selectedEventId = $this->events->first()?->id;
        }
    }

    public function updatedSelectedEventId(): void
    {
        $this->memo = [];
        $this->activeAssessmentId = null;
        $this->activeMaterialId = null;
        $this->activeMeetingId = null;
        $this->answers = [];
    }

    public function getParticipantProperty(): ?Participant
    {
        return auth()->user()?->participant;
    }

    public function getEventsProperty(): EloquentCollection
    {
        return $this->memo['events'] ??= (function () {
            if (! $this->participant) {
                return new EloquentCollection();
            }

            return LearningEvent::query()
                ->where('is_published', true)
                ->whereHas('participants', fn ($query) => $query->where('participants.id', $this->participant->id))
                ->orderByDesc('starts_at')
                ->get();
        })();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    public function getMeetingsProperty(): EloquentCollection
    {
        return $this->memo['meetings.' . $this->selectedEventId] ??= (function () {
            if (! $this->selectedEventId || ! $this->selectedEventApproved()) {
                return new EloquentCollection();
            }

            return $this->selectedEvent
                ? $this->selectedEvent
                    ->meetings()
                    ->with([
                        'materials' => fn ($query) => $query->where('is_published', true)->orderBy('order'),
                        'assessments' => fn ($query) => $query->where('is_open', true)->orderBy('id'),
                    ])
                    ->where('is_published', true)
                    ->orderBy('order')
                    ->get()
                : new EloquentCollection();
        })();
    }

    public function getAssessmentsProperty(): EloquentCollection
    {
        return $this->memo['assessments.' . $this->selectedEventId] ??= (function () {
            if (! $this->selectedEventId || ! $this->selectedEventApproved()) {
                return new EloquentCollection();
            }

            return Assessment::query()
                ->where('learning_event_id', $this->selectedEventId)
                ->where('is_open', true)
                ->orderByRaw("case type when 'pre' then 0 when 'quiz' then 1 when 'post' then 2 else 3 end")
                ->orderBy('id')
                ->get();
        })();
    }

    public function getActiveMaterialProperty(): ?LearningMaterial
    {
        if (! $this->activeMaterialId) {
            return null;
        }

        return $this->meetings
            ->flatMap(fn (LearningMeeting $meeting) => $meeting->materials)
            ->firstWhere('id', $this->activeMaterialId);
    }

    public function getActiveMeetingProperty(): ?LearningMeeting
    {
        if (! $this->activeMeetingId) {
            return null;
        }

        return $this->meetings->firstWhere('id', $this->activeMeetingId);
    }

    public function getCertificateProperty(): ?Certificate
    {
        if (! $this->participant || ! $this->selectedEvent) {
            return null;
        }

        // Sekali per request: view memanggil property ini lebih dari sekali.
        return $this->memo['certificate.' . $this->selectedEvent->id] ??= app(CertificateEligibilityService::class)->ensureCertificate($this->participant, $this->selectedEvent);
    }

    public function getCertificateNotesProperty(): array
    {
        $notes = trim((string) ($this->certificate?->eligibility_notes ?? ''));

        return $notes === '' ? [] : preg_split('/\r\n|\r|\n/', $notes);
    }

    public function getActiveAssessmentProperty(): ?Assessment
    {
        if (! $this->activeAssessmentId) {
            return null;
        }

        return $this->assessments->firstWhere('id', $this->activeAssessmentId);
    }

    public function materialUrl(LearningMaterial $material): ?string
    {
        return MaterialViewer::url($material);
    }

    public function previewMaterial(int $materialId): void
    {
        if (! $this->selectedEventApproved()) {
            Notification::make()
                ->title('Dashboard belum terbuka')
                ->body('Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $material = $this->meetings
            ->filter(fn (LearningMeeting $meeting) => $this->canAccessMeeting($meeting))
            ->flatMap(fn (LearningMeeting $meeting) => $meeting->materials)
            ->firstWhere('id', $materialId);

        if (! $material) {
            Notification::make()
                ->title('Materi belum bisa dibuka')
                ->body('Selesaikan urutan modul dan kuis sebelumnya terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $this->activeMaterialId = $material->id;
    }

    public function openMeeting(int $meetingId): void
    {
        if (! $this->selectedEventApproved()) {
            Notification::make()
                ->title('Modul belum bisa dibuka')
                ->body('Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $meeting = $this->meetings->firstWhere('id', $meetingId);

        if (! $meeting || ! $this->canAccessMeeting($meeting)) {
            Notification::make()
                ->title('Modul belum bisa dibuka')
                ->body($meeting ? $this->meetingLockReason($meeting) : 'Modul tidak ditemukan untuk event ini.')
                ->warning()
                ->send();

            return;
        }

        $this->activeMeetingId = $meeting->id;
        $this->activeMaterialId = $meeting->materials->first()?->id;
    }

    public function backToMeetingList(): void
    {
        $this->activeMeetingId = null;
        $this->activeMaterialId = null;
    }

    public function quizForMeeting(?LearningMeeting $meeting): ?Assessment
    {
        if (! $meeting) {
            return null;
        }

        return $this->assessments
            ->where('type', 'quiz')
            ->firstWhere('learning_meeting_id', $meeting->id);
    }

    public function materialViewer(?LearningMaterial $material): array
    {
        return MaterialViewer::for($material);
    }

    /** Semua percobaan peserta untuk satu tes (post-test bisa lebih dari satu karena boleh diulang). */
    public function attemptsFor(int $assessmentId): Collection
    {
        if (! $this->participant) {
            return collect();
        }

        $this->memo['attempts'] ??= AssessmentAttempt::query()
            ->where('participant_id', $this->participant->id)
            ->orderBy('id')
            ->get()
            ->groupBy('assessment_id');

        return $this->memo['attempts']->get($assessmentId, collect());
    }

    /** Percobaan dengan nilai terbaik (nilai inilah yang dipakai). */
    public function attemptFor(int $assessmentId): ?AssessmentAttempt
    {
        return $this->attemptsFor($assessmentId)->sortByDesc(fn (AssessmentAttempt $attempt) => [(float) $attempt->score, $attempt->id])->first();
    }

    public function passingScore(Assessment $assessment): float
    {
        return (float) ($assessment->passing_score ?: self::DEFAULT_PASSING_SCORE);
    }

    public function hasPassed(Assessment $assessment): bool
    {
        $best = $this->attemptFor($assessment->id);

        return $best !== null && (float) $best->score >= $this->passingScore($assessment);
    }

    /** Sisa kesempatan mengulang post-test (0 bila bukan post-test, belum dikerjakan, atau sudah lulus). */
    public function remainingRetakes(Assessment $assessment): int
    {
        $attempts = $this->attemptsFor($assessment->id)->count();

        if ($assessment->type !== 'post' || $attempts === 0 || $this->hasPassed($assessment)) {
            return 0;
        }

        return max(0, self::POST_TEST_RETAKES - ($attempts - 1));
    }

    public function canRetake(Assessment $assessment): bool
    {
        return $this->remainingRetakes($assessment) > 0;
    }

    public function startAssessment(int $assessmentId): void
    {
        if (! $this->selectedEventApproved()) {
            Notification::make()
                ->title('Tes belum bisa dikerjakan')
                ->body('Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $assessment = $this->assessments->firstWhere('id', $assessmentId);

        if (! $assessment) {
            return;
        }

        if ($this->attemptFor($assessment->id) && ! $this->canRetake($assessment)) {
            Notification::make()->title('Tes ini sudah pernah dikerjakan.')->warning()->send();
            return;
        }

        if (! $this->canStartAssessment($assessment)) {
            Notification::make()
                ->title('Tes belum bisa dikerjakan')
                ->body($this->assessmentLockReason($assessment))
                ->warning()
                ->send();

            return;
        }

        $this->activeAssessmentId = $assessment->id;
        $this->answers = [];
    }

    public function submitAssessment(int $assessmentId): void
    {
        $assessment = $this->assessments->firstWhere('id', $assessmentId);

        if (! $assessment || ! $this->participant) {
            return;
        }

        if (! $this->canStartAssessment($assessment)) {
            Notification::make()
                ->title('Tes belum bisa disubmit')
                ->body($this->assessmentLockReason($assessment))
                ->warning()
                ->send();

            $this->activeAssessmentId = null;
            $this->answers = [];

            return;
        }

        if ($this->attemptFor($assessment->id) && ! $this->canRetake($assessment)) {
            Notification::make()->title('Tes ini sudah pernah disubmit.')->warning()->send();
            $this->activeAssessmentId = null;
            $this->answers = [];
            return;
        }

        $questions = $this->questionsFor($assessment);

        if ($questions->isEmpty()) {
            Notification::make()->title('Soal belum tersedia.')->danger()->send();
            return;
        }

        $missing = $questions->keys()->first(fn ($index) => ! array_key_exists($index, $this->answers) || $this->answers[$index] === null || $this->answers[$index] === '');

        if ($missing !== null) {
            Notification::make()->title('Lengkapi semua jawaban terlebih dahulu.')->danger()->send();
            return;
        }

        $correct = $questions->filter(function (array $question, int $index): bool {
            $selected = (int) ($this->answers[$index] ?? -1);
            $options = collect($question['options'] ?? [])->values();
            $option = $options->get($selected);

            return (bool) ($option['is_correct'] ?? false);
        })->count();

        $total = $questions->count();
        $score = round(($correct / max(1, $total)) * 100, 2);

        AssessmentAttempt::query()->create([
            'assessment_id' => $assessment->id,
            'participant_id' => $this->participant->id,
            'answers' => $this->answers,
            'correct_count' => $correct,
            'total_questions' => $total,
            'score' => $score,
            'submitted_at' => now(),
        ]);

        unset($this->memo['attempts']);

        $scoreText = rtrim(rtrim(number_format($score, 2, ',', ''), '0'), ',');

        if ($assessment->type === 'post' && ! $this->hasPassed($assessment)) {
            $remaining = $this->remainingRetakes($assessment);

            Notification::make()
                ->title('Nilai post-test belum mencapai batas lulus')
                ->body("Skor kamu: {$scoreText} (minimal " . $this->passingScore($assessment) . '). '
                    . ($remaining > 0 ? "Kamu bisa mengulang post-test, sisa {$remaining} kesempatan." : 'Kesempatan mengulang sudah habis; nilai terbaikmu yang dipakai.'))
                ->warning()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Tes berhasil disubmit')
                ->body("Skor kamu: {$scoreText}")
                ->success()
                ->send();
        }

        $this->activeAssessmentId = null;
        $this->answers = [];
    }

    public function questionsFor(?Assessment $assessment): Collection
    {
        if (! $assessment) {
            return collect();
        }

        $questions = $assessment->questions;

        if (is_string($questions) && $questions !== '') {
            $questions = json_decode($questions, true);
        }

        $questions = collect(is_array($questions) ? $questions : [])->values();

        // Urutan soal diacak stabil per peserta (tetap sama saat halaman dimuat ulang); key tetap indeks
        // soal asli agar jawaban dan penilaian merujuk soal yang benar. Bila diatur, hanya N soal yang diambil.
        $seed = crc32('soal-' . $assessment->id . '-' . ($this->participant?->id ?? 0) . $this->retakeSeedSuffix($assessment));
        $order = $questions->keys()->sortBy(fn (int $index) => crc32($seed . '-' . $index));
        $limit = (int) ($assessment->questions_per_attempt ?? 0);

        if ($limit > 0 && $limit < $questions->count()) {
            $order = $order->take($limit);
        }

        return $order->mapWithKeys(fn (int $index) => [$index => $questions->get($index)]);
    }

    /**
     * Opsi jawaban dalam urutan acak yang stabil per peserta dan soal, dengan key tetap
     * indeks opsi asli sehingga penilaian di submitAssessment() tidak berubah.
     */
    public function displayOptions(Assessment $assessment, int $questionIndex, array $question): Collection
    {
        $options = collect($question['options'] ?? [])->values();
        $seed = crc32($assessment->id . '-' . $questionIndex . '-' . ($this->participant?->id ?? 0) . $this->retakeSeedSuffix($assessment));

        return $options
            ->keys()
            ->sortBy(fn (int $index) => crc32($seed . '-' . $index))
            ->mapWithKeys(fn (int $index) => [$index => $options->get($index)]);
    }

    /** Pengulangan post-test mendapat susunan soal & opsi acak yang baru (percobaan pertama tidak berubah). */
    private function retakeSeedSuffix(Assessment $assessment): string
    {
        $round = $assessment->type === 'post' ? $this->attemptsFor($assessment->id)->count() : 0;

        return $round > 0 ? '-ulang' . $round : '';
    }

    public function canStartAssessment(Assessment $assessment): bool
    {
        if (! $this->selectedEventApproved()) {
            return false;
        }

        if ($assessment->type === 'post') {
            return $this->hasCompletedType('pre') && $this->allQuizCompleted() && $this->hasCheckedIn();
        }

        if ($assessment->type === 'quiz') {
            return $this->hasCompletedType('pre') && $this->previousQuizCompleted($assessment);
        }

        return true;
    }

    public function assessmentLockReason(Assessment $assessment): string
    {
        if ($assessment->type === 'quiz' && ! $this->hasCompletedType('pre')) {
            return 'Pre-test harus diselesaikan sebelum mengerjakan kuis modul.';
        }

        if ($assessment->type === 'quiz' && ! $this->previousQuizCompleted($assessment)) {
            return 'Kuis modul sebelumnya harus diselesaikan terlebih dahulu.';
        }

        if ($assessment->type === 'post' && ! $this->hasCompletedType('pre')) {
            return 'Pre-test harus diselesaikan sebelum mengerjakan post-test.';
        }

        if ($assessment->type === 'post' && ! $this->allQuizCompleted()) {
            return 'Semua kuis modul harus selesai sebelum mengerjakan post-test.';
        }

        if ($assessment->type === 'post' && ! $this->hasCheckedIn()) {
            return 'Anda belum absen. Klik "Absen Sekarang" di kartu Absensi Kegiatan pada Dashboard sebelum mengerjakan post-test.';
        }

        return '';
    }

    /** Peserta wajib absen di event terpilih sebelum mengerjakan post-test. */
    public function hasCheckedIn(): bool
    {
        if (! $this->participant || ! $this->selectedEvent) {
            return false;
        }

        return $this->memo['checked_in.' . $this->selectedEventId] ??= app(EventAttendanceService::class)->hasCheckedIn($this->participant, $this->selectedEvent);
    }

    public function hasCompletedType(string $type): bool
    {
        if (! $this->participant || ! $this->selectedEventId) {
            return false;
        }

        return AssessmentAttempt::query()
            ->where('participant_id', $this->participant->id)
            ->whereHas('assessment', fn ($query) => $query
                ->where('learning_event_id', $this->selectedEventId)
                ->where('type', $type))
            ->exists();
    }

    public function allQuizCompleted(): bool
    {
        if (! $this->participant || ! $this->selectedEventId) {
            return false;
        }

        $quizIds = Assessment::query()
            ->where('learning_event_id', $this->selectedEventId)
            ->where('type', 'quiz')
            ->where('is_open', true)
            ->pluck('id');

        if ($quizIds->isEmpty()) {
            return true;
        }

        $completed = AssessmentAttempt::query()
            ->where('participant_id', $this->participant->id)
            ->whereIn('assessment_id', $quizIds)
            ->distinct('assessment_id')
            ->count('assessment_id');

        return $completed >= $quizIds->count();
    }

    public function canAccessMeeting(LearningMeeting $meeting): bool
    {
        if (! $this->selectedEventApproved()) {
            return false;
        }

        if ($this->allAssessmentsCompleted()) {
            return true;
        }

        if (! $this->hasCompletedType('pre')) {
            return false;
        }

        return $this->previousMeetingQuizzesCompleted($meeting);
    }

    public function meetingLockReason(LearningMeeting $meeting): string
    {
        if (! $this->selectedEventApproved()) {
            return 'Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.';
        }

        if ($this->allAssessmentsCompleted()) {
            return '';
        }

        if (! $this->hasCompletedType('pre')) {
            return 'Pre-test harus diselesaikan sebelum membuka modul.';
        }

        if (! $this->previousMeetingQuizzesCompleted($meeting)) {
            return 'Selesaikan kuis modul sebelumnya untuk membuka modul ini.';
        }

        return '';
    }

    public function allAssessmentsCompleted(): bool
    {
        if (! $this->participant || ! $this->selectedEventId) {
            return false;
        }

        $assessmentIds = Assessment::query()
            ->where('learning_event_id', $this->selectedEventId)
            ->where('is_open', true)
            ->pluck('id');

        if ($assessmentIds->isEmpty()) {
            return false;
        }

        $completed = AssessmentAttempt::query()
            ->where('participant_id', $this->participant->id)
            ->whereIn('assessment_id', $assessmentIds)
            ->distinct('assessment_id')
            ->count('assessment_id');

        return $completed >= $assessmentIds->count();
    }

    public function previousQuizCompleted(Assessment $assessment): bool
    {
        $quizIds = $this->orderedQuizAssessments()
            ->pluck('id')
            ->values();

        $index = $quizIds->search($assessment->id);

        if ($index === false || $index === 0) {
            return true;
        }

        return AssessmentAttempt::query()
            ->where('participant_id', $this->participant?->id)
            ->where('assessment_id', $quizIds[$index - 1])
            ->exists();
    }

    public function previousMeetingQuizzesCompleted(LearningMeeting $meeting): bool
    {
        $previousQuizIds = $this->orderedQuizAssessments()
            ->filter(fn (Assessment $assessment) => ($assessment->meeting?->order ?? 9999) < $meeting->order)
            ->pluck('id');

        if ($previousQuizIds->isEmpty()) {
            return true;
        }

        $completed = AssessmentAttempt::query()
            ->where('participant_id', $this->participant?->id)
            ->whereIn('assessment_id', $previousQuizIds)
            ->distinct('assessment_id')
            ->count('assessment_id');

        return $completed >= $previousQuizIds->count();
    }

    public function orderedQuizAssessments(): Collection
    {
        if (! $this->selectedEventId) {
            return collect();
        }

        return Assessment::query()
            ->with('meeting')
            ->where('learning_event_id', $this->selectedEventId)
            ->where('type', 'quiz')
            ->where('is_open', true)
            ->get()
            ->sortBy(fn (Assessment $assessment) => str_pad((string) ($assessment->meeting?->order ?? 9999), 4, '0', STR_PAD_LEFT) . '-' . str_pad((string) $assessment->id, 8, '0', STR_PAD_LEFT))
            ->values();
    }

    public function selectedEventApproved(): bool
    {
        if (! $this->participant || ! $this->selectedEvent) {
            return false;
        }

        return $this->memo['approved.' . $this->selectedEvent->id] ??= $this->participant->isApprovedForEvent($this->selectedEvent);
    }
}
