<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Materi "Online Trust & Safety" (6 modul: slide PPT/PDF + video) dari docs/modul ajar/modul terbaru,
 * beserta pre/post-test siswa dan tutor (Modul ToT PPT untuk tutor).
 *
 * File materi diharapkan ada di storage/app/public/learning-materials/dsc-ots/ dan .../modul-ajar/ (disk public).
 * Jalankan: php artisan db:seed --class=MateriSeeder (aman diulang).
 */
class MateriSeeder extends Seeder
{
    private const DIR = 'learning-materials/dsc-ots/';

    public const MATERI_SISWA = 'DSC Online Trust & Safety - Modul Siswa (6 Modul)';

    private const MATERI_TUTOR = 'DSC Online Trust & Safety - ToT Tutor (6 Modul)';

    private const VIDEO_WAJIB = 'https://youtu.be/pLxS9dVhGGU';

    private const MODULES = [
        1 => ['title' => 'Kenali Online Scam', 'slug' => 'kenali-online-scam', 'video_note' => ' (opsional)', 'description' => 'Pola penipuan online, tanda bahaya promo dan hadiah palsu, verifikasi URL dan reputasi toko, serta prinsip menelepon langsung bila ada permintaan uang.'],
        2 => ['title' => 'Lindungi Device', 'slug' => 'lindungi-device', 'video_note' => '', 'description' => 'Bahaya file APK dari luar toko resmi, Guest Mode/Multi-User, antivirus, dan pembaruan sistem untuk melindungi perangkat.'],
        3 => ['title' => 'Mengenali Informasi Palsu', 'slug' => 'mengenali-informasi-palsu', 'video_note' => '', 'description' => 'Ciri hoaks, alasan hoaks mudah menyebar, serta langkah verifikasi sumber, konteks, waktu, dan dokumentasi sebelum membagikan informasi.'],
        4 => ['title' => 'Peretas Media Sosial', 'slug' => 'peretas-media-sosial', 'video_note' => '', 'description' => 'Penyebab akun media sosial dibajak, bahaya password reuse, dan perlindungan dengan MFA/2FA.'],
        5 => ['title' => 'Menjelajah Internet', 'slug' => 'menjelajah-internet', 'video_note' => '', 'description' => 'Risiko Wi-Fi publik, ciri situs aman (https:// dan ikon gembok), VPN, dan batasan transaksi finansial saat online.'],
        6 => ['title' => 'Apa yang Harus Dilakukan Setelahnya', 'slug' => 'apa-yang-harus-dilakukan-setelahnya', 'video_note' => '', 'description' => 'Tanggap insiden keamanan: isolasi, rotasi kredensial, remediasi, serta melapor dan mendokumentasikan kejadian.'],
    ];

    /** Modul ToT (file PPT asli dari docs/modul ajar/Modul Ajar ToT), sesuai soal Pre/Post-Test tutor. */
    private const TOT_MODULES = [
        1 => ['title' => 'Pengantar Keamanan Digital', 'file' => 'Modul-1_Pengantar Keamanan Digital.pptx', 'description' => 'Aset digital, ancaman phishing, dan jejak digital yang kekal serta dapat dilacak.'],
        2 => ['title' => 'Pemahaman Data Pribadi', 'file' => 'Modul-2_Pemahaman Data Pribadi.pptx', 'description' => 'Data pribadi umum dan spesifik, hak privasi, serta sanksi pengungkapan data pribadi orang lain.'],
        3 => ['title' => 'Perundungan Siber & Hoax', 'file' => 'Modul-3_Perundungan Siber & Hoax.pptx', 'description' => 'Ciri dan bentuk perundungan online, serta penyebab identitas netizen mudah dibajak.'],
        4 => ['title' => 'Hoax & Bijak Berinternet', 'file' => 'Modul-4_Hoax & Bijak Berinternet.pptx', 'description' => 'Etika digital, aturan emas bijak berinternet, dan langkah verifikasi hoaks.'],
        5 => ['title' => 'Microsite', 'file' => 'Modul-5_Microsite.pptx', 'description' => 'Fungsi microsite s.id, komponen microsite, dan fitur statistik pengunjung.'],
    ];

    private const TOT_DIR = 'learning-materials/modul-ajar/';

    public function run(): void
    {
        $this->warnMissingFiles();

        $tests = json_decode(file_get_contents(database_path('seeders/data/dsc-ots-tests.json')), true);
        $superAdmin = User::query()->where('email', 'su@isoc.id')->firstOrFail();

        $this->material(self::MATERI_SISWA, ModuleTemplate::AUDIENCE_PESERTA, $superAdmin, $tests['pre_peserta'], $tests['post_peserta'], 70,
            'Materi siswa Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia. Setiap modul berisi slide presentasi dan video ajar.');
        $this->material(self::MATERI_TUTOR, ModuleTemplate::AUDIENCE_TUTOR, $superAdmin, $tests['pre_tutor'], $tests['post_tutor'], 100,
            'Materi ToT tutor: 5 Modul ToT (PPT) sebagai bekal fasilitator, lalu 6 Materi Ajar Siswa (slide dan video) yang dibawakan di kelas. Tutor menyelesaikan Pre-Test dan Post-Test ToT.');
    }

    private function material(string $name, string $audience, User $creator, array $pre, array $post, int $passingScore, string $description): ModuleTemplate
    {
        $existing = ModuleTemplate::query()->where('name', $name)->first();

        if ($existing) {
            $existing->update(['description' => $description]);
            $this->buildMeetings($existing);

            return $existing;
        }

        $template = ModuleTemplate::query()->create([
            'created_by' => $creator->id,
            'name' => $name,
            'audience' => $audience,
            'description' => $description,
            'purpose' => 'Peserta mampu mengenali online scam, melindungi perangkat dan akun, memverifikasi informasi, menjelajah internet dengan aman, dan menangani insiden keamanan digital.',
            'meeting_count' => count(self::MODULES),
            'is_active' => true,
        ]);

        $label = $audience === ModuleTemplate::AUDIENCE_TUTOR ? 'ToT' : '';

        foreach (['pre' => [$pre, 'Pre-Test'], 'post' => [$post, 'Post-Test']] as $type => [$questions, $title]) {
            Assessment::query()->create([
                'module_template_id' => $template->id,
                'type' => $type,
                'title' => trim($title . ' ' . $label) . ': Online Trust & Safety',
                'passing_score' => $passingScore,
                'is_open' => true,
                'questions' => $questions,
            ]);
        }

        $this->buildMeetings($template);

        return $template;
    }

    /**
     * Pertemuan materi. Materi tutor: Modul ToT (PPT) lalu Materi Ajar Siswa (slide + video).
     * Materi siswa: Materi Ajar Siswa saja. Pertemuan dibangun ulang bila strukturnya belum sesuai.
     */
    private function buildMeetings(ModuleTemplate $template): void
    {
        $isTutor = $template->audience === ModuleTemplate::AUDIENCE_TUTOR;
        $expected = count(self::MODULES) + ($isTutor ? count(self::TOT_MODULES) : 0);
        $meetings = $template->learningMeetings()->whereNull('learning_event_id');

        if ($meetings->count() === $expected) {
            return;
        }

        foreach ($meetings->get() as $old) {
            $old->materials()->delete();
            Assessment::query()->where('learning_meeting_id', $old->id)->delete();
            $old->delete();
        }

        $order = 1;

        if ($isTutor) {
            foreach (self::TOT_MODULES as $number => $module) {
                $meeting = $this->meeting($template, $order++, "Modul ToT {$number}: {$module['title']}", $module['description'], 30);
                LearningMaterial::query()->create([
                    'learning_meeting_id' => $meeting->id,
                    'order' => 1,
                    'title' => "PPT Modul ToT {$number} - {$module['title']}",
                    'type' => 'ppt',
                    'file_path' => self::TOT_DIR . $module['file'],
                    'duration_minutes' => 30,
                    'is_published' => true,
                ]);
            }
        }

        foreach (self::MODULES as $number => $module) {
            $title = ($isTutor ? 'Materi Ajar Siswa ' : '') . "Modul {$number}: {$module['title']}";
            $meeting = $this->meeting($template, $order++, $title, $module['description'], 30);
            $materialOrder = 1;

            if ($number === 1) {
                LearningMaterial::query()->create([
                    'learning_meeting_id' => $meeting->id,
                    'order' => $materialOrder++,
                    'title' => 'Video Cybersecurity (wajib)',
                    'type' => 'video',
                    'external_url' => self::VIDEO_WAJIB,
                    'is_published' => true,
                ]);
            }

            LearningMaterial::query()->create([
                'learning_meeting_id' => $meeting->id,
                'order' => $materialOrder++,
                'title' => "Slide PPT Modul {$number} - {$module['title']}",
                'type' => 'pdf',
                'file_path' => self::DIR . "modul-{$number}-{$module['slug']}.pdf",
                'duration_minutes' => 15,
                'is_published' => true,
            ]);

            LearningMaterial::query()->create([
                'learning_meeting_id' => $meeting->id,
                'order' => $materialOrder,
                'title' => "Video Ajar Modul {$number} - {$module['title']}{$module['video_note']}",
                'type' => 'video',
                'file_path' => self::DIR . "video-{$number}-{$module['slug']}.mp4",
                'duration_minutes' => 15,
                'is_published' => true,
            ]);
        }

        $template->update(['meeting_count' => $expected]);
    }

    private function meeting(ModuleTemplate $template, int $order, string $title, string $description, int $minutes): LearningMeeting
    {
        return LearningMeeting::query()->create([
            'learning_event_id' => null,
            'module_template_id' => $template->id,
            'order' => $order,
            'title' => $title,
            'description' => $description,
            'duration_minutes' => $minutes,
            'is_published' => true,
        ]);
    }

    private function warnMissingFiles(): void
    {
        foreach (self::TOT_MODULES as $module) {
            foreach ([$module['file'], preg_replace('/\.pptx$/', '.pdf', $module['file'])] as $file) {
                if (! Storage::disk('public')->exists(self::TOT_DIR . $file)) {
                    $this->command?->warn('File materi ToT belum ada di storage/app/public/' . self::TOT_DIR . $file . ($file !== $module['file'] ? ' (versi PDF untuk preview, buat dengan: soffice --headless --convert-to pdf)' : ''));
                }
            }
        }

        foreach (self::MODULES as $number => $module) {
            foreach (["modul-{$number}-{$module['slug']}.pdf", "video-{$number}-{$module['slug']}.mp4"] as $file) {
                if (! Storage::disk('public')->exists(self::DIR . $file)) {
                    $this->command?->warn('File materi belum ada di storage/app/public/' . self::DIR . $file);
                }
            }
        }
    }
}
