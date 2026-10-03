<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\LearningMaterial;
use App\Models\ModuleTemplate;
use App\Models\TotAssessment;
use App\Support\MaterialViewer;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class TutorTraining extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Pelatihan Tutor';

    protected static ?string $navigationLabel = 'ToT Awal';

    protected static ?string $title = 'Pelatihan Tutor Awal';

    protected static ?string $slug = 'pelatihan-tutor';

    protected static string $view = 'filament.pages.tutor-training';

    /** @var array<int, string|null> */
    public array $answers = [];

    /** Pre-test / post-test ToT yang sedang dikerjakan. */
    public ?int $activeTestId = null;

    /** @var array<int, string|int|null> */
    public array $testAnswers = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Tutor;
    }

    public function getTutorProperty()
    {
        return auth()->user()?->tutor;
    }

    /** Materi Event tipe Tutor yang dikelola Super Admin dari menu Materi Event dan Pertemuan & Materi. */
    public function getTutorTemplatesProperty(): Collection
    {
        return ModuleTemplate::query()
            ->forTutors()
            ->where('is_active', true)
            ->with([
                'learningMeetings' => fn ($query) => $query
                    ->where('is_published', true)
                    ->orderBy('order')
                    ->with(['materials' => fn ($materials) => $materials->where('is_published', true)->orderBy('order')]),
            ])
            ->orderBy('name')
            ->get();
    }

    public function materialUrl(LearningMaterial $material): ?string
    {
        return MaterialViewer::url($material);
    }

    /** Materi Event tipe Tutor terbaru yang memiliki Pre-Test dan Post-Test ToT. */
    public function getTestTemplateProperty(): ?ModuleTemplate
    {
        return ModuleTemplate::query()
            ->forTutors()
            ->where('is_active', true)
            ->whereHas('assessments', fn ($query) => $query->where('type', 'pre')->where('is_open', true))
            ->whereHas('assessments', fn ($query) => $query->where('type', 'post')->where('is_open', true))
            ->latest('id')
            ->first();
    }

    public function getPreTestProperty(): ?Assessment
    {
        return $this->testTemplate?->assessments()->where('type', 'pre')->where('is_open', true)->first();
    }

    public function getPostTestProperty(): ?Assessment
    {
        return $this->testTemplate?->assessments()->where('type', 'post')->where('is_open', true)->first();
    }

    public function getActiveTestProperty(): ?Assessment
    {
        return collect([$this->preTest, $this->postTest])->filter()->firstWhere('id', $this->activeTestId);
    }

    public function testAttempt(?Assessment $assessment): ?AssessmentAttempt
    {
        if (! $assessment || ! $this->tutor) {
            return null;
        }

        return AssessmentAttempt::query()
            ->where('tutor_id', $this->tutor->id)
            ->where('assessment_id', $assessment->id)
            ->first();
    }

    /** @return array<int, array<string, mixed>> */
    public function testQuestions(?Assessment $assessment): array
    {
        $questions = $assessment?->questions;

        if (is_string($questions)) {
            $questions = json_decode($questions, true);
        }

        return array_values(is_array($questions) ? $questions : []);
    }

    public function canStartTest(Assessment $assessment): bool
    {
        $attempt = $this->testAttempt($assessment);

        if ($assessment->type === 'pre') {
            return ! $attempt;
        }

        // Post-test boleh diulang sampai nilai sempurna, setelah pre-test selesai.
        return (bool) $this->testAttempt($this->preTest) && (! $attempt || (float) $attempt->score < 100);
    }

    public function startTest(int $assessmentId): void
    {
        $assessment = collect([$this->preTest, $this->postTest])->filter()->firstWhere('id', $assessmentId);

        if (! $assessment || ! $this->canStartTest($assessment)) {
            Notification::make()
                ->title('Tes belum bisa dikerjakan')
                ->body($assessment?->type === 'post' ? 'Selesaikan Pre-Test ToT terlebih dahulu.' : 'Pre-Test ToT sudah dikerjakan.')
                ->warning()
                ->send();

            return;
        }

        $this->activeTestId = $assessment->id;
        $this->testAnswers = [];
    }

    public function submitTest(): void
    {
        $assessment = $this->activeTest;
        $tutor = $this->tutor;

        if (! $assessment || ! $tutor || ! $this->canStartTest($assessment)) {
            $this->activeTestId = null;

            return;
        }

        $questions = $this->testQuestions($assessment);

        foreach (array_keys($questions) as $index) {
            if (! isset($this->testAnswers[$index]) || $this->testAnswers[$index] === '') {
                Notification::make()->title('Lengkapi semua jawaban terlebih dahulu.')->danger()->send();

                return;
            }
        }

        $correct = collect($questions)
            ->filter(fn (array $question, int $index) => (bool) (array_values($question['options'] ?? [])[(int) $this->testAnswers[$index]]['is_correct'] ?? false))
            ->count();
        $score = round($correct / max(1, count($questions)) * 100, 2);

        AssessmentAttempt::query()->updateOrCreate(
            ['tutor_id' => $tutor->id, 'assessment_id' => $assessment->id],
            [
                'participant_id' => null,
                'answers' => $this->testAnswers,
                'correct_count' => $correct,
                'total_questions' => count($questions),
                'score' => $score,
                'submitted_at' => now(),
            ],
        );

        $this->activeTestId = null;
        $this->testAnswers = [];

        if ($assessment->type === 'pre') {
            Notification::make()->title('Pre-Test ToT tersimpan')->body("Skor: {$score}. Pelajari materi ToT lalu kerjakan Post-Test ToT.")->success()->send();

            return;
        }

        $this->recordTotScore((int) round($score), 'Post-Test ToT: ' . $assessment->title . ' (benar ' . $correct . '/' . count($questions) . ').');

        $score >= 100
            ? Notification::make()->title('ToT tutor lulus')->body('Nilai Post-Test ToT sempurna (100).')->success()->send()
            : Notification::make()->title('Post-Test ToT belum sempurna')->body("Skor: {$score}. Pelajari kembali materi dan ulangi Post-Test ToT sampai nilai 100.")->warning()->send();
    }

    /** Simpan nilai ToT ke semua event tutor; nilai 100 menandai tutor lulus ToT. */
    private function recordTotScore(int $score, string $note): void
    {
        $tutor = $this->tutor;

        if (! $tutor) {
            return;
        }

        if ($score >= 100) {
            $tutor->update(['tot_completed' => true, 'is_cadre' => true]);
        }

        foreach ($tutor->learningEvents()->get() as $event) {
            TotAssessment::query()->updateOrCreate(
                ['learning_event_id' => $event->id, 'tutor_id' => $tutor->id],
                ['score' => $score, 'completed_at' => now(), 'assessed_by' => auth()->id(), 'notes' => $note],
            );
        }
    }

    /** Daftar modul bawaan, dipakai bila belum ada Materi Event tipe Tutor. */
    public function getModulesProperty(): array
    {
        return [
            ['title' => 'Modul 1 - Online Scam', 'summary' => 'Pola penipuan digital, red flag, verifikasi tautan, dan simulasi kasus scam.'],
            ['title' => 'Modul 2 - Perlindungan Device', 'summary' => 'Kunci layar, update sistem, backup, antivirus, dan kebiasaan aman memakai perangkat.'],
            ['title' => 'Modul 3 - Informasi Palsu', 'summary' => 'Cek sumber, baca konteks, reverse image search, dan teknik fasilitasi diskusi hoaks.'],
            ['title' => 'Modul 4 - Peretasan Media Sosial', 'summary' => 'Password manager, MFA, pemulihan akun, dan latihan respons ketika akun diretas.'],
            ['title' => 'Modul 5 - Internet Aman', 'summary' => 'Privasi, jejak digital, etika online, microsite s.id, dan role-play perlindungan data.'],
            ['title' => 'Modul 6 - Penanganan Insiden Digital', 'summary' => 'Alur lapor, dokumentasi bukti, eskalasi, open mic, dan refleksi peserta.'],
        ];
    }

    public function getQuestionsProperty(): array
    {
        return [
            [
                'question' => 'Berapa target minimal skor rata-rata kuis peserta dalam program DSC?',
                'options' => ['75', '80', '85', '90'],
                'answer' => '85',
            ],
            [
                'question' => 'Apa slogan video wajib ISOC yang dikumpulkan setelah pelatihan?',
                'options' => ['Internet sehat untuk semua', 'The Internet is for Everyone', 'Safety first on the web', 'Digital champion for school'],
                'answer' => 'The Internet is for Everyone',
            ],
            [
                'question' => 'Kapan peserta boleh mencetak sertifikat?',
                'options' => ['Setelah login pertama', 'Setelah pre-test saja', 'Setelah pre-test, kuis modul, post-test, dan checklist eligible lengkap', 'Setelah ikut WhatsApp Group saja'],
                'answer' => 'Setelah pre-test, kuis modul, post-test, dan checklist eligible lengkap',
            ],
            [
                'question' => 'Dokumen apa yang wajib dilengkapi tutor/admin lokal setelah pelatihan offline?',
                'options' => ['Absensi basah, foto per sesi, video, dan bukti dukung lokasi', 'Hanya daftar nilai', 'Hanya screenshot dashboard', 'Hanya sertifikat tutor'],
                'answer' => 'Absensi basah, foto per sesi, video, dan bukti dukung lokasi',
            ],
        ];
    }

    public function submitTraining(): void
    {
        $questions = $this->questions;
        $correct = collect($questions)
            ->filter(fn (array $question, int $index): bool => ($this->answers[$index] ?? null) === $question['answer'])
            ->count();

        if ($correct !== count($questions)) {
            Notification::make()
                ->title('ToT belum lulus')
                ->body('Tutor harus menjawab semua pertanyaan dengan benar sebelum event bisa dilanjutkan.')
                ->warning()
                ->send();

            return;
        }

        if (! $this->tutor) {
            Notification::make()->title('Data tutor belum terhubung ke akun ini.')->danger()->send();

            return;
        }

        $this->recordTotScore(100, 'Lulus pelatihan awal tutor melalui LMS ToT.');

        Notification::make()
            ->title('ToT tutor selesai')
            ->body('Nilai ToT tersimpan sempurna. Admin lokal sekarang bisa memantau status kelulusan tutor.')
            ->success()
            ->send();
    }
}
