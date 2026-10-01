<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\Participant;
use Illuminate\Database\Seeder;

class LearningEventDemoSeeder extends Seeder
{
    public function run(): void
    {
        $event = LearningEvent::query()->updateOrCreate(
            ['slug' => 'digital-safety-champions'],
            [
                'title' => 'Digital Safety Champions',
                'description' => 'Event pembelajaran literasi keamanan digital ISOC dengan 6 pertemuan, materi, pre-test, dan post-test.',
                'starts_at' => '2026-10-24 09:00:00',
                'ends_at' => '2026-11-28 12:00:00',
                'status' => 'active',
                'registration_open' => true,
                'is_published' => true,
            ],
        );

        Participant::query()->each(fn (Participant $participant) => $participant->learningEvents()->syncWithoutDetaching([
            $event->id => ['status' => 'registered', 'registered_at' => now()],
        ]));

        $meetings = [
            [
                'order' => 1,
                'title' => 'Pertemuan 1: Online Scam & Penipuan Daring',
                'description' => 'Mengenali pola phishing, social engineering, dan tautan berbahaya.',
            ],
            [
                'order' => 2,
                'title' => 'Pertemuan 2: Perlindungan Perangkat dan Akun',
                'description' => 'Praktik password kuat, MFA, update aplikasi, dan keamanan perangkat.',
            ],
            [
                'order' => 3,
                'title' => 'Pertemuan 3: Hoaks dan Verifikasi Informasi',
                'description' => 'Memeriksa sumber informasi, gambar, dan klaim viral.',
            ],
            [
                'order' => 4,
                'title' => 'Pertemuan 4: Keamanan Media Sosial',
                'description' => 'Mengamankan akun, privasi profil, dan respon saat akun diretas.',
            ],
            [
                'order' => 5,
                'title' => 'Pertemuan 5: Data Pribadi dan Jejak Digital',
                'description' => 'Memahami data pribadi, consent, dan risiko berbagi data.',
            ],
            [
                'order' => 6,
                'title' => 'Pertemuan 6: Penanganan Insiden Digital',
                'description' => 'Langkah pelaporan, dokumentasi bukti, dan rencana aksi setelah insiden.',
            ],
        ];

        foreach ($meetings as $meetingData) {
            $meeting = LearningMeeting::query()->updateOrCreate(
                ['learning_event_id' => $event->id, 'order' => $meetingData['order']],
                [
                    'title' => $meetingData['title'],
                    'description' => $meetingData['description'],
                    'starts_at' => now()->setDate(2026, 10, 24)->addWeeks($meetingData['order'] - 1)->setTime(9, 0),
                    'is_published' => true,
                ],
            );

            $slug = 'pertemuan-' . $meetingData['order'];
            $materials = [
                [
                    'order' => 1,
                    'title' => 'PDF Ringkasan ' . $meetingData['title'],
                    'type' => 'pdf',
                    'external_url' => "https://example.com/isoc/{$slug}/ringkasan.pdf",
                ],
                [
                    'order' => 2,
                    'title' => 'Slide PPT ' . $meetingData['title'],
                    'type' => 'ppt',
                    'external_url' => "https://example.com/isoc/{$slug}/slide.pptx",
                ],
                [
                    'order' => 3,
                    'title' => 'Video Pembelajaran ' . $meetingData['title'],
                    'type' => 'video',
                    'external_url' => "https://example.com/isoc/{$slug}/video",
                ],
            ];

            foreach ($materials as $material) {
                LearningMaterial::query()->updateOrCreate(
                    ['learning_meeting_id' => $meeting->id, 'order' => $material['order']],
                    [
                        'title' => $material['title'],
                        'type' => $material['type'],
                        'external_url' => $material['external_url'],
                        'file_path' => null,
                        'is_published' => true,
                    ],
                );
            }
        }

        $firstMeetingId = LearningMeeting::query()
            ->where('learning_event_id', $event->id)
            ->where('order', 1)
            ->value('id');

        Assessment::query()->updateOrCreate(
            ['learning_event_id' => $event->id, 'type' => 'pre'],
            [
                'learning_meeting_id' => $firstMeetingId,
                'title' => 'Pre-Test Digital Safety Champions',
                'form_url' => null,
                'passing_score' => 70,
                'is_open' => true,
                'questions' => $this->preTestQuestions(),
            ],
        );

        Assessment::query()->updateOrCreate(
            ['learning_event_id' => $event->id, 'type' => 'post'],
            [
                'learning_meeting_id' => $firstMeetingId,
                'title' => 'Post-Test Digital Safety Champions',
                'form_url' => null,
                'passing_score' => 85,
                'is_open' => true,
                'questions' => $this->postTestQuestions(),
            ],
        );
    }

    private function preTestQuestions(): array
    {
        return [
            [
                'question' => 'Apa tindakan paling aman saat menerima tautan mencurigakan dari orang tidak dikenal?',
                'options' => [
                    ['text' => 'Langsung klik agar tahu isinya', 'is_correct' => false],
                    ['text' => 'Cek sumber tautan dan jangan masukkan data pribadi', 'is_correct' => true],
                    ['text' => 'Kirim ke grup kelas agar ramai', 'is_correct' => false],
                    ['text' => 'Balas pesan dengan nomor OTP', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Mengapa setiap akun sebaiknya memakai password berbeda?',
                'options' => [
                    ['text' => 'Agar semua akun mudah diingat teman', 'is_correct' => false],
                    ['text' => 'Agar jika satu akun bocor, akun lain tetap lebih aman', 'is_correct' => true],
                    ['text' => 'Agar tidak perlu verifikasi dua langkah', 'is_correct' => false],
                    ['text' => 'Agar akun terlihat aktif', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Apa contoh data pribadi yang perlu dilindungi?',
                'options' => [
                    ['text' => 'Nomor identitas, alamat, nomor telepon, dan password', 'is_correct' => true],
                    ['text' => 'Judul lagu favorit saja', 'is_correct' => false],
                    ['text' => 'Warna tas di sekolah', 'is_correct' => false],
                    ['text' => 'Nama mata pelajaran umum', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Apa yang sebaiknya dilakukan sebelum membagikan berita viral?',
                'options' => [
                    ['text' => 'Bagikan secepat mungkin', 'is_correct' => false],
                    ['text' => 'Cek sumber, tanggal, konteks, dan pembanding terpercaya', 'is_correct' => true],
                    ['text' => 'Percaya jika judulnya panjang', 'is_correct' => false],
                    ['text' => 'Percaya jika banyak emoji', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Apa manfaat autentikasi dua faktor?',
                'options' => [
                    ['text' => 'Menambah lapisan keamanan saat login', 'is_correct' => true],
                    ['text' => 'Menghapus semua risiko internet', 'is_correct' => false],
                    ['text' => 'Membuat password tidak diperlukan', 'is_correct' => false],
                    ['text' => 'Membuat akun otomatis viral', 'is_correct' => false],
                ],
            ],
        ];
    }

    private function postTestQuestions(): array
    {
        return [
            [
                'question' => 'Jika akun media sosial teman diretas, langkah awal yang paling tepat adalah...',
                'options' => [
                    ['text' => 'Menyebarkan tangkapan layar tanpa izin', 'is_correct' => false],
                    ['text' => 'Membantu mengamankan akun, mengganti password, dan mengaktifkan MFA', 'is_correct' => true],
                    ['text' => 'Membalas pesan penipu', 'is_correct' => false],
                    ['text' => 'Mengirim OTP ke pelaku', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Saat diminta mengisi formulir online, indikator aman yang perlu dicek adalah...',
                'options' => [
                    ['text' => 'Alamat situs, tujuan pengumpulan data, dan pihak penyelenggara', 'is_correct' => true],
                    ['text' => 'Warna tombol submit', 'is_correct' => false],
                    ['text' => 'Jumlah gambar di formulir', 'is_correct' => false],
                    ['text' => 'Apakah form dikirim lewat chat berantai', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Dokumentasi bukti insiden digital sebaiknya berisi...',
                'options' => [
                    ['text' => 'Screenshot, tautan, waktu kejadian, dan akun terkait', 'is_correct' => true],
                    ['text' => 'Komentar marah sebanyak mungkin', 'is_correct' => false],
                    ['text' => 'Data password semua korban', 'is_correct' => false],
                    ['text' => 'Unggahan publik tanpa sensor', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Apa praktik baik untuk menjaga privasi profil media sosial?',
                'options' => [
                    ['text' => 'Membuka semua data untuk publik', 'is_correct' => false],
                    ['text' => 'Mengatur privasi, membatasi data sensitif, dan meninjau daftar teman', 'is_correct' => true],
                    ['text' => 'Membagikan lokasi real-time setiap saat', 'is_correct' => false],
                    ['text' => 'Menyimpan password di bio', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Tujuan utama literasi keamanan digital adalah...',
                'options' => [
                    ['text' => 'Menakuti orang agar tidak memakai internet', 'is_correct' => false],
                    ['text' => 'Membantu pengguna internet lebih aman, kritis, dan bertanggung jawab', 'is_correct' => true],
                    ['text' => 'Membuat semua orang menjadi programmer', 'is_correct' => false],
                    ['text' => 'Menghapus kebutuhan aturan sekolah', 'is_correct' => false],
                ],
            ],
        ];
    }
}
