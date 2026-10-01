<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\TotAssessment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Tutor;
    }

    public function getTutorProperty()
    {
        return auth()->user()?->tutor;
    }

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

        $tutor = $this->tutor;

        if (! $tutor) {
            Notification::make()->title('Data tutor belum terhubung ke akun ini.')->danger()->send();

            return;
        }

        $tutor->update([
            'tot_completed' => true,
            'is_cadre' => true,
        ]);

        $events = $tutor->learningEvents()->get();

        foreach ($events as $event) {
            TotAssessment::query()->updateOrCreate(
                [
                    'learning_event_id' => $event->id,
                    'tutor_id' => $tutor->id,
                ],
                [
                    'score' => 100,
                    'completed_at' => now(),
                    'assessed_by' => auth()->id(),
                    'notes' => 'Lulus pelatihan awal tutor melalui LMS ToT.',
                ],
            );
        }

        Notification::make()
            ->title('ToT tutor selesai')
            ->body('Nilai ToT tersimpan sempurna. Admin lokal sekarang bisa memantau status kelulusan tutor.')
            ->success()
            ->send();
    }
}
