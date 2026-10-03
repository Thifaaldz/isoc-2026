<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use App\Models\User;
use App\Services\TutorMaterialMirror;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Materi "Online Trust & Safety" (6 modul: slide PPT/PDF + video) dari docs/modul ajar/modul terbaru,
 * beserta pre/post-test siswa dan tutor. Materi ToT berisi modul yang sama dengan materi siswa;
 * yang membedakan hanya Pre-Test dan Post-Test ToT.
 *
 * File materi diharapkan ada di storage/app/public/learning-materials/dsc-ots/ (disk public).
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

    public function run(): void
    {
        $this->warnMissingFiles();

        $tests = json_decode(file_get_contents(database_path('seeders/data/dsc-ots-tests.json')), true);
        $superAdmin = User::query()->where('email', 'su@isoc.id')->firstOrFail();

        $siswa = $this->material(self::MATERI_SISWA, ModuleTemplate::AUDIENCE_PESERTA, $superAdmin, $tests['pre_peserta'], $tests['post_peserta'], 70,
            'Materi siswa Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia. Setiap modul berisi slide presentasi dan video ajar.');
        $this->material(self::MATERI_TUTOR, ModuleTemplate::AUDIENCE_TUTOR, $superAdmin, $tests['pre_tutor'], $tests['post_tutor'], 100,
            'Materi ToT tutor: 6 modul yang sama dengan materi siswa (slide dan video) yang dibawakan di kelas. Tutor menyelesaikan Pre-Test dan Post-Test ToT.', $siswa);
    }

    /** $source diisi untuk materi tutor: pertemuannya auto-generated dari materi siswa, hanya tes ToT yang berbeda. */
    private function material(string $name, string $audience, User $creator, array $pre, array $post, int $passingScore, string $description, ?ModuleTemplate $source = null): ModuleTemplate
    {
        $existing = ModuleTemplate::query()->where('name', $name)->first();

        if ($existing) {
            $existing->update(['description' => $description, 'source_template_id' => $source?->id]);
            $source ? app(TutorMaterialMirror::class)->ensureFor($source) : $this->buildMeetings($existing);

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
            'source_template_id' => $source?->id,
        ]);

        $label = $audience === ModuleTemplate::AUDIENCE_TUTOR ? 'ToT' : '';

        foreach (['pre' => [$pre, 'Pre-Test'], 'post' => [$post, 'Post-Test']] as $type => [$questions, $title]) {
            Assessment::query()->create([
                'module_template_id' => $template->id,
                'type' => $type,
                'title' => trim($title . ' ' . $label) . ': Online Trust & Safety',
                'passing_score' => $passingScore,
                // Peserta: 5 soal acak per orang dari bank soal; ToT tutor tetap semua soal.
                'questions_per_attempt' => $audience === ModuleTemplate::AUDIENCE_PESERTA ? 5 : null,
                'is_open' => true,
                'questions' => $questions,
            ]);
        }

        $source ? app(TutorMaterialMirror::class)->ensureFor($source) : $this->buildMeetings($template);

        return $template;
    }

    /** Pertemuan materi (sama untuk siswa dan tutor). Pertemuan dibangun ulang bila strukturnya belum sesuai. */
    private function buildMeetings(ModuleTemplate $template): void
    {
        $expected = count(self::MODULES);
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

        foreach (self::MODULES as $number => $module) {
            $title = "Modul {$number}: {$module['title']}";
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
        foreach (self::MODULES as $number => $module) {
            foreach (["modul-{$number}-{$module['slug']}.pdf", "video-{$number}-{$module['slug']}.mp4"] as $file) {
                if (! Storage::disk('public')->exists(self::DIR . $file)) {
                    $this->command?->warn('File materi belum ada di storage/app/public/' . self::DIR . $file);
                }
            }
        }
    }
}
