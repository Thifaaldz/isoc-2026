<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Services\ModuleTemplateApplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ModuleAjarSeeder extends Seeder
{
    public function run(): void
    {
        $event = LearningEvent::query()->where('slug', 'digital-safety-champions')->first();

        if (! $event) {
            $this->command?->warn('Event digital-safety-champions belum tersedia.');

            return;
        }

        $modules = $this->modules();
        $publicDisk = Storage::disk('public');
        $targetDirectory = 'learning-materials/modul-ajar';
        $sourceDirectory = base_path('../docs/modul ajar/Modul Ajar ToT-20260929T130806Z-1-001/Modul Ajar ToT');

        $publicDisk->makeDirectory($targetDirectory);
        $templateMeetings = [];

        foreach ($modules as $module) {
            $sourcePath = $sourceDirectory . DIRECTORY_SEPARATOR . $module['file'];
            $targetPath = $targetDirectory . '/' . $module['file'];

            if (is_file($sourcePath)) {
                File::copy($sourcePath, $publicDisk->path($targetPath));
            } elseif (! $publicDisk->exists($targetPath)) {
                $this->command?->warn('File modul ajar tidak ditemukan: ' . $module['file']);
            }

            Module::query()->updateOrCreate(
                ['number' => $module['order']],
                [
                    'title' => 'Modul ' . $module['order'] . ': ' . $module['title'],
                    'description' => $module['description'],
                    'content' => $module['content'],
                    'resource_url' => $publicDisk->url($targetPath),
                    'is_published' => true,
                ],
            );

            $meeting = LearningMeeting::query()->updateOrCreate(
                ['learning_event_id' => $event->id, 'order' => $module['order']],
                [
                    'title' => 'Pertemuan ' . $module['order'] . ': ' . $module['title'],
                    'description' => $module['description'],
                    'task_title' => $module['task_title'],
                    'task_description' => $module['task_description'],
                    'starts_at' => now()->setDate(2026, 10, 24)->setTime(9, 0)->addMinutes(($module['order'] - 1) * 25),
                    'is_published' => true,
                ],
            );

            LearningMaterial::query()
                ->where('learning_meeting_id', $meeting->id)
                ->delete();

            LearningMaterial::query()->create([
                'learning_meeting_id' => $meeting->id,
                'order' => 1,
                'title' => 'Slide Modul ' . $module['order'] . ': ' . $module['title'],
                'type' => 'ppt',
                'file_path' => $targetPath,
                'external_url' => null,
                'is_published' => true,
            ]);

            Assessment::query()->updateOrCreate(
                [
                    'learning_event_id' => $event->id,
                    'learning_meeting_id' => $meeting->id,
                    'type' => 'quiz',
                ],
                [
                    'title' => 'Kuis Modul ' . $module['order'] . ': ' . $module['title'],
                    'module_template_id' => null,
                    'passing_score' => 85,
                    'is_open' => true,
                    'questions' => $this->quizQuestions($module['title']),
                ],
            );

            $templateMeetings[] = [
                'order' => $module['order'],
                'title' => 'Pertemuan ' . $module['order'] . ': ' . $module['title'],
                'description' => $module['description'],
                'is_published' => true,
                'task_title' => $module['task_title'],
                'task_description' => $module['task_description'],
                'materials' => [
                    [
                        'order' => 1,
                        'title' => 'Slide Modul ' . $module['order'] . ': ' . $module['title'],
                        'type' => 'ppt',
                        'file_path' => $targetPath,
                        'external_url' => null,
                        'is_published' => true,
                    ],
                ],
                'quiz_title' => 'Kuis Modul ' . $module['order'] . ': ' . $module['title'],
                'passing_score' => 85,
                'is_quiz_open' => true,
                'quiz_questions' => $this->quizQuestions($module['title']),
            ];
        }

        $orders = collect($modules)->pluck('order')->all();

        LearningMeeting::query()
            ->where('learning_event_id', $event->id)
            ->whereNotIn('order', $orders)
            ->update(['is_published' => false]);

        Assessment::query()
            ->where('learning_event_id', $event->id)
            ->where('type', 'quiz')
            ->whereHas('meeting', fn ($query) => $query->whereNotIn('order', $orders))
            ->update(['is_open' => false]);

        $template = ModuleTemplate::query()->updateOrCreate(
            ['name' => 'Modul Ajar ToT - Digital Safety Champions'],
            [
                'created_by' => null,
                'description' => 'Template contoh dari folder docs/modul ajar. Berisi pertemuan, slide PPT, tugas, dan kuis per modul.',
                'purpose' => 'Membekali tutor dan peserta dengan pemahaman keamanan digital, perlindungan data pribadi, literasi informasi, dan praktik kampanye digital melalui microsite.',
                'meeting_count' => count($templateMeetings),
                'meetings' => null,
                'is_active' => true,
            ],
        );

        foreach ($template->learningMeetings as $templateMeeting) {
            Assessment::query()->where('learning_meeting_id', $templateMeeting->id)->delete();
            $templateMeeting->delete();
        }

        Assessment::query()
            ->where('module_template_id', $template->id)
            ->delete();

        foreach ([
            'pre' => 'Pre-Test: Digital Safety Champions',
            'post' => 'Post-Test: Digital Safety Champions',
        ] as $type => $title) {
            Assessment::query()->create([
                'learning_event_id' => null,
                'learning_meeting_id' => null,
                'module_template_id' => $template->id,
                'type' => $type,
                'title' => $title,
                'passing_score' => 85,
                'is_open' => true,
                'questions' => $this->quizQuestions($title),
            ]);
        }

        foreach ($modules as $module) {
            $targetPath = $targetDirectory . '/' . $module['file'];

            $templateMeeting = LearningMeeting::query()->create([
                'learning_event_id' => null,
                'module_template_id' => $template->id,
                'order' => $module['order'],
                'title' => 'Pertemuan ' . $module['order'] . ': ' . $module['title'],
                'description' => $module['description'],
                'task_title' => $module['task_title'],
                'task_description' => $module['task_description'],
                'starts_at' => null,
                'is_published' => true,
            ]);

            LearningMaterial::query()->create([
                'learning_meeting_id' => $templateMeeting->id,
                'order' => 1,
                'title' => 'Slide Modul ' . $module['order'] . ': ' . $module['title'],
                'type' => 'ppt',
                'file_path' => $targetPath,
                'external_url' => null,
                'is_published' => true,
            ]);

            Assessment::query()->create([
                'learning_event_id' => null,
                'learning_meeting_id' => $templateMeeting->id,
                'module_template_id' => $template->id,
                'type' => 'quiz',
                'title' => 'Kuis Modul ' . $module['order'] . ': ' . $module['title'],
                'passing_score' => 85,
                'is_open' => true,
                'questions' => $this->quizQuestions($module['title']),
            ]);
        }

        $event->update(['module_template_id' => $template->id]);
        $event->refresh()->load('moduleTemplate');

        app(ModuleTemplateApplier::class)->applyToEvent($event);
    }

    /** @return array<int, array<string, mixed>> */
    private function modules(): array
    {
        return [
            [
                'order' => 1,
                'title' => 'Pengantar Keamanan Digital',
                'file' => 'Modul-1_Pengantar Keamanan Digital.pptx',
                'description' => 'Memahami konsep dasar keamanan digital, jenis ancaman umum, dan kebiasaan aman saat menggunakan internet.',
                'content' => 'Peserta mengenal ruang lingkup keamanan digital, risiko harian di internet, serta langkah awal melindungi akun, perangkat, dan identitas digital.',
                'task_title' => 'Refleksi Keamanan Digital Harian',
                'task_description' => 'Tuliskan tiga risiko digital yang paling sering kamu temui dan jelaskan kebiasaan aman yang akan kamu lakukan setelah sesi ini.',
            ],
            [
                'order' => 2,
                'title' => 'Pemahaman Data Pribadi',
                'file' => 'Modul-2_Pemahaman Data Pribadi.pptx',
                'description' => 'Mengenali jenis data pribadi, risiko penyalahgunaan data, consent, dan cara menjaga informasi sensitif.',
                'content' => 'Peserta mempelajari contoh data pribadi, praktik berbagi data yang aman, serta dampak kebocoran data bagi diri sendiri dan lingkungan sekolah.',
                'task_title' => 'Audit Data Pribadi',
                'task_description' => 'Buat daftar data pribadi yang tidak boleh dibagikan sembarangan dan jelaskan alasan perlindungannya.',
            ],
            [
                'order' => 3,
                'title' => 'Perundungan Siber & Hoax',
                'file' => 'Modul-3_Perundungan Siber & Hoax.pptx',
                'description' => 'Mengidentifikasi perundungan siber, dampaknya, cara merespons, serta mengenali informasi palsu.',
                'content' => 'Peserta memahami bentuk cyberbullying, alur bantuan, etika berkomunikasi digital, dan prinsip dasar memeriksa kebenaran informasi.',
                'task_title' => 'Simulasi Respons Aman',
                'task_description' => 'Buat contoh respons yang tepat saat melihat perundungan siber atau menerima informasi yang belum jelas kebenarannya.',
            ],
            [
                'order' => 4,
                'title' => 'Hoax & Bijak Berinternet',
                'file' => 'Modul-4_Hoax & Bijak Berinternet.pptx',
                'description' => 'Melatih kebiasaan verifikasi informasi, berpikir kritis, dan menggunakan internet secara bertanggung jawab.',
                'content' => 'Peserta mempraktikkan cara membaca konteks, memeriksa sumber, memahami jejak digital, dan membuat keputusan yang lebih bijak saat online.',
                'task_title' => 'Cek Fakta Konten Viral',
                'task_description' => 'Pilih satu contoh klaim viral, lalu tuliskan langkah verifikasi sumber, konteks, dan bukti pendukungnya.',
            ],
            [
                'order' => 5,
                'title' => 'Microsite',
                'file' => 'Modul-5_Microsite.pptx',
                'description' => 'Membuat microsite sederhana sebagai media kampanye keamanan digital dan refleksi hasil pembelajaran.',
                'content' => 'Peserta mempelajari tujuan microsite, struktur konten kampanye, dan praktik membuat halaman sederhana untuk membagikan pesan keamanan digital.',
                'task_title' => 'Buat Microsite Kampanye',
                'task_description' => 'Buat microsite sederhana berisi pesan keamanan digital, tips utama, dan ajakan positif untuk teman sebaya.',
            ],
        ];
    }

    private function quizQuestions(string $title): array
    {
        return [
            [
                'question' => 'Apa langkah paling aman setelah mempelajari materi ' . $title . '?',
                'options' => [
                    ['text' => 'Menerapkan kebiasaan aman dan memeriksa risiko sebelum bertindak', 'is_correct' => true],
                    ['text' => 'Membagikan semua informasi pribadi agar mudah dikenali teman', 'is_correct' => false],
                    ['text' => 'Mengabaikan sumber informasi karena semua konten online pasti benar', 'is_correct' => false],
                    ['text' => 'Menggunakan satu password yang sama untuk semua akun', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Mengapa peserta perlu berdiskusi setelah menyelesaikan modul ' . $title . '?',
                'options' => [
                    ['text' => 'Agar dapat menghubungkan materi dengan kasus nyata di sekitar mereka', 'is_correct' => true],
                    ['text' => 'Agar tidak perlu mengerjakan praktik dan kuis', 'is_correct' => false],
                    ['text' => 'Agar bisa melewati modul berikutnya tanpa membaca materi', 'is_correct' => false],
                    ['text' => 'Agar data pribadi bisa dikumpulkan tanpa persetujuan', 'is_correct' => false],
                ],
            ],
        ];
    }
}
