<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\MicrositePractice;
use App\Models\Module;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Partner;
use App\Models\PeerGroup;
use App\Models\School;
use App\Models\Tutor;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WagGroup;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Semua akun demo memakai password: "password" (di-hash otomatis oleh cast User). */
    public function run(): void
    {
        if (function_exists('activity')) {
            activity()->disableLogging();
        }

        $superAdmin = User::create(['name' => 'Super Admin ISOC', 'email' => 'su@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin]);
        $admin = User::create(['name' => 'Admin RTIK Local', 'email' => 'adm@isoc.id', 'password' => 'password', 'role' => UserRole::Admin]);

        $school = School::create([
            'name' => 'SMK Negeri 2 Jakarta',
            'npsn' => '20100002',
            'type' => 'SMK',
            'province' => 'DKI JAKARTA',
            'province_code' => '31',
            'city' => 'KOTA JAKARTA SELATAN',
            'city_code' => '3171',
            'district' => 'TEBET',
            'district_code' => '3171090',
            'village' => 'TEBET BARAT',
            'village_code' => '3171090002',
            'address' => 'Jl. Tebet Barat Raya No. 1, Jakarta Selatan',
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=SMK%20Negeri%202%20Jakarta',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '081200000000',
            'participant_target' => 10,
            'training_date' => '2026-10-24',
        ]);

        $admin->update(['school_id' => $school->id, 'phone' => '081288880000']);

        [$tutors, $tutorRows] = $this->seedTutors($school);
        [$participants, $participantRows] = $this->seedParticipants($school);
        $budgetItems = $this->budgetItems();

        $event = LearningEvent::query()->updateOrCreate(
            ['slug' => 'digital-safety-champions'],
            [
                'school_id' => $school->id,
                'created_by' => $admin->id,
                'title' => 'Digital Safety Champions - SMK Negeri 2 Jakarta',
                'description' => 'Seminar dan pelatihan keamanan digital dengan 6 modul OTS, ToT tutor, kuis modul, pre-test, post-test, bukti dukung, dan termin pembayaran.',
                'event_type' => 'offline',
                'audience_type' => 'school',
                'zoom_url' => null,
                'starts_at' => '2026-10-24 09:00:00',
                'ends_at' => '2026-10-24 12:00:00',
                'training_start_time' => '09:00',
                'training_end_time' => '12:00',
                'attendance_code' => 'DSC24A',
                'workflow_status' => 'tot_in_progress',
                'publish_approval_status' => 'published',
                'target_participants' => 10,
                'target_tutors' => 3,
                'status' => 'active',
                'registration_open' => true,
                'is_published' => true,
                'participant_rows' => $participantRows,
                'tutor_rows' => $tutorRows,
                'budget_items' => $budgetItems,
                'evidence_checklist' => $this->evidenceChecklist(),
                'local_admin_notes' => 'Data persiapan, peserta, tutor, dan RAB awal sudah dilengkapi oleh Admin RTIK Local.',
                'central_admin_notes' => 'Termin 1 sudah diverifikasi. ToT tutor sedang berjalan sebelum pelatihan lapangan.',
                'publish_revision_notes' => null,
                'local_update_summary' => 'Seeder: event offline lengkap dari Admin RTIK Local.',
                'local_updated_at' => now(),
                'publish_approved_by' => $superAdmin->id,
                'publish_approved_at' => now(),
            ],
        );

        $event->participants()->detach();
        $event->tutors()->detach();
        $event->meetings()->delete();
        $event->assessments()->delete();

        foreach ($participants as $participant) {
            $event->participants()->syncWithoutDetaching([$participant->id => [
                'status' => 'registered',
                'registered_at' => now(),
                'admin_approval_status' => 'approved',
                'tutor_approval_status' => 'approved',
            ]]);
        }

        foreach ($tutors as $tutor) {
            $event->tutors()->syncWithoutDetaching([$tutor->id => ['status' => 'assigned', 'assigned_at' => now()]]);
        }

        $this->seedPayments($event, $school, $superAdmin);
        $partners = $this->seedPartners();
        $event->partners()->sync($partners->pluck('id')->all());
        $this->seedModules();
        $meetings = $this->seedMeetings($event);
        $this->seedAssessments($event, $meetings, $participants);
        $this->call(ModuleAjarSeeder::class);
        $this->seedOperations($event, $school, $participants, $tutors, $admin);
        $this->seedCertificate($event, $participants->first());
        $this->seedWebinarEvent($school, $admin, $superAdmin, $participants->first(), $tutors->first(), $budgetItems, $partners);

        LearningEvent::query()
            ->whereKey($event->id)
            ->update(['workflow_status' => 'tot_in_progress']);
    }

    private function seedTutors(School $school): array
    {
        $rows = [
            ['name' => 'Tutor ISOC Utama', 'email' => 'tutor@isoc.id', 'phone' => '081311110000', 'institution' => 'RTIK Jakarta'],
            ['name' => 'Tutor Keamanan Digital', 'email' => 'tutor2@isoc.id', 'phone' => '081311110001', 'institution' => 'RTIK Jakarta'],
            ['name' => 'Fasilitator Sekolah', 'email' => 'tutor3@isoc.id', 'phone' => '081311110002', 'institution' => 'SMK Negeri 2 Jakarta'],
        ];

        $tutors = collect($rows)->map(function (array $row) use ($school): Tutor {
            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => 'password',
                'role' => UserRole::Tutor,
                'school_id' => $school->id,
                'phone' => $row['phone'],
            ]);

            return Tutor::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'institution' => $row['institution'],
                'tot_completed' => false,
                'is_cadre' => false,
            ]);
        });

        return [$tutors, $rows];
    }

    private function seedParticipants(School $school): array
    {
        $rows = [
            ['name' => 'Peserta Contoh', 'email' => 'peserta@isoc.id', 'phone' => '081322220000', 'nis' => '0031000001', 'grade' => 'SMA 10', 'gender' => 'L', 'birth_date' => '2010-02-14'],
            ['name' => 'Alya Putri', 'email' => 'alya.putri@isoc.id', 'phone' => '081322220001', 'nis' => '0031000002', 'grade' => 'SMA 10', 'gender' => 'P', 'birth_date' => '2010-05-02'],
            ['name' => 'Dimas Pratama', 'email' => 'dimas.pratama@isoc.id', 'phone' => '081322220002', 'nis' => '0031000003', 'grade' => 'SMA 11', 'gender' => 'L', 'birth_date' => '2009-08-20'],
            ['name' => 'Nabila Salsabila', 'email' => 'nabila.salsabila@isoc.id', 'phone' => '081322220003', 'nis' => '0031000004', 'grade' => 'SMA 11', 'gender' => 'P', 'birth_date' => '2009-11-11'],
            ['name' => 'Raka Wijaya', 'email' => 'raka.wijaya@isoc.id', 'phone' => '081322220004', 'nis' => '0031000005', 'grade' => 'SMA 12', 'gender' => 'L', 'birth_date' => '2008-04-18'],
            ['name' => 'Siti Nurhaliza', 'email' => 'siti.nurhaliza@isoc.id', 'phone' => '081322220005', 'nis' => '0031000006', 'grade' => 'SMA 10', 'gender' => 'P', 'birth_date' => '2010-06-10'],
            ['name' => 'Fajar Ramadhan', 'email' => 'fajar.ramadhan@isoc.id', 'phone' => '081322220006', 'nis' => '0031000007', 'grade' => 'SMA 11', 'gender' => 'L', 'birth_date' => '2009-03-22'],
            ['name' => 'Maya Lestari', 'email' => 'maya.lestari@isoc.id', 'phone' => '081322220007', 'nis' => '0031000008', 'grade' => 'SMA 12', 'gender' => 'P', 'birth_date' => '2008-12-05'],
            ['name' => 'Bagas Saputra', 'email' => 'bagas.saputra@isoc.id', 'phone' => '081322220008', 'nis' => '0031000009', 'grade' => 'SMA 10', 'gender' => 'L', 'birth_date' => '2010-09-17'],
            ['name' => 'Intan Permata', 'email' => 'intan.permata@isoc.id', 'phone' => '081322220009', 'nis' => '0031000010', 'grade' => 'SMA 11', 'gender' => 'P', 'birth_date' => '2009-01-29'],
        ];

        $participants = collect($rows)->map(function (array $row) use ($school): Participant {
            $user = User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => 'password',
                'role' => UserRole::Peserta,
                'school_id' => $school->id,
                'phone' => $row['phone'],
            ]);

            return Participant::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'nis' => $row['nis'],
                'grade' => $row['grade'],
                'gender' => $row['gender'],
                'birth_date' => $row['birth_date'],
                'consent_at' => now(),
                'followed_instagram' => true,
                'joined_wag' => true,
                'registered_ecert' => true,
            ]);
        });

        return [$participants, $rows];
    }

    private function seedPartners()
    {
        return collect([
            [
                'name' => 'APJII',
                'category' => 'national',
                'subtitle' => 'Asosiasi Penyelenggara Jasa Internet Indonesia',
                'logo_path' => 'images/partners/apjii.png',
                'website_url' => 'https://apjii.or.id',
            ],
            [
                'name' => 'PANDI',
                'category' => 'national',
                'subtitle' => 'Pengelola Nama Domain Internet Indonesia',
                'logo_path' => 'images/partners/pandi.png',
                'website_url' => 'https://pandi.id',
            ],
        ])->map(fn (array $partner) => Partner::query()->updateOrCreate(
            ['name' => $partner['name']],
            array_merge($partner, ['status' => 'active']),
        ));
    }

    private function budgetItems(): array
    {
        return [
            ['category' => 'konsumsi', 'description' => 'Konsumsi peserta dan tutor', 'quantity' => 110, 'unit' => 'paket', 'unit_price' => 25000, 'amount' => 2750000, 'vendor' => 'Kantin Sekolah', 'receipt_number' => 'INV-KON-001', 'notes' => 'Snack dan air mineral.'],
            ['category' => 'banner_publikasi', 'description' => 'Banner kegiatan', 'quantity' => 2, 'unit' => 'pcs', 'unit_price' => 250000, 'amount' => 500000, 'vendor' => 'Digital Print Tebet', 'receipt_number' => 'INV-BNR-001', 'notes' => 'Banner ruang pelatihan dan dokumentasi.'],
            ['category' => 'atk', 'description' => 'ATK peserta', 'quantity' => 100, 'unit' => 'paket', 'unit_price' => 10000, 'amount' => 1000000, 'vendor' => 'Toko ATK Maju', 'receipt_number' => 'INV-ATK-001', 'notes' => 'Pulpen dan lembar kerja.'],
            ['category' => 'transportasi', 'description' => 'Transport tutor dan fasilitator', 'quantity' => 3, 'unit' => 'orang', 'unit_price' => 150000, 'amount' => 450000, 'vendor' => 'RTIK Jakarta', 'receipt_number' => 'TRP-001', 'notes' => 'Transport lokal.'],
            ['category' => 'dokumentasi', 'description' => 'Dokumentasi foto dan video', 'quantity' => 1, 'unit' => 'paket', 'unit_price' => 750000, 'amount' => 750000, 'vendor' => 'Tim Dokumentasi Sekolah', 'receipt_number' => 'DOC-001', 'notes' => 'Foto per sesi dan video slogan.'],
        ];
    }

    private function evidenceChecklist(): array
    {
        return collect([
            'Data sekolah lengkap',
            'Data peserta lengkap',
            'Data tutor lengkap',
            'RAB manual lengkap',
            'ToT tutor sempurna',
            'Absensi basah',
            'Foto kegiatan',
            'Video slogan',
            'Bukti pre-test dan post-test',
            'Kuis modul terselesaikan',
        ])->map(fn (string $label) => ['label' => $label, 'done' => true])->all();
    }

    private function seedPayments(LearningEvent $event, School $school, User $superAdmin): void
    {
        $termAmount = round(collect($event->budget_items)->sum('amount') / 3, 2);

        foreach ([1, 2, 3] as $term) {
            Payment::create([
                'learning_event_id' => $event->id,
                'school_id' => $school->id,
                'term' => $term,
                'amount' => $termAmount,
                'status' => $term === 1 ? 'eligible' : 'pending',
                'due_at' => now()->addDays($term * 7)->toDateString(),
                'approved_by' => $term === 1 ? $superAdmin->id : null,
                'approved_at' => $term === 1 ? now() : null,
                'checklist' => $this->paymentChecklist($term),
                'notes' => 'Termin ' . $term . ' untuk seminar Digital Safety Champions.',
            ]);
        }
    }

    private function paymentChecklist(int $term): array
    {
        $items = match ($term) {
            1 => ['Data sekolah', 'Data peserta', 'Data tutor', 'Jadwal pelatihan', 'RAB awal', 'Akun peserta dan tutor'],
            2 => ['ToT tutor sempurna', 'Absensi basah', 'Foto kegiatan', 'Video slogan', 'Pre-test dan post-test', 'Bukti 6 modul'],
            default => ['RAB final', 'Kuitansi dan invoice', 'Laporan KPI', 'Sertifikat eligible', 'WAG aktif', 'Laporan akhir'],
        };

        return collect($items)->map(fn (string $label) => ['label' => $label, 'done' => $term === 1])->all();
    }

    private function seedModules(): void
    {
        $modules = [
            [1, 'Online Scam & Penipuan Daring', 'Mengenali phishing, social engineering, dan tautan berbahaya.'],
            [2, 'Perlindungan Perangkat dan Akun', 'Password kuat, MFA, update aplikasi, dan keamanan perangkat.'],
            [3, 'Hoaks dan Verifikasi Informasi', 'Memeriksa sumber, konteks, dan klaim viral.'],
            [4, 'Keamanan Media Sosial', 'Privasi profil, pemulihan akun, dan pencegahan peretasan.'],
            [5, 'Internet Aman dan Data Pribadi', 'Consent, jejak digital, dan praktik microsite s.id.'],
            [6, 'Penanganan Insiden Digital', 'Dokumentasi bukti, pelaporan, dan rencana mitigasi.'],
        ];

        foreach ($modules as [$number, $title, $description]) {
            Module::create([
                'number' => $number,
                'title' => "Modul {$number}: {$title}",
                'description' => $description,
                'content' => "Konten contoh untuk {$title}.",
                'resource_url' => "https://example.com/isoc/modules/modul-{$number}",
                'is_published' => true,
            ]);
        }
    }

    private function seedMeetings(LearningEvent $event)
    {
        $meetings = [
            [1, 'Pertemuan 1: Online Scam & Penipuan Daring'],
            [2, 'Pertemuan 2: Perlindungan Perangkat dan Akun'],
            [3, 'Pertemuan 3: Hoaks dan Verifikasi Informasi'],
            [4, 'Pertemuan 4: Keamanan Media Sosial'],
            [5, 'Pertemuan 5: Internet Aman dan Data Pribadi'],
            [6, 'Pertemuan 6: Penanganan Insiden Digital'],
        ];

        return collect($meetings)->map(function (array $data) use ($event): LearningMeeting {
            [$order, $title] = $data;

            $meeting = LearningMeeting::create([
                'learning_event_id' => $event->id,
                'order' => $order,
                'title' => $title,
                'description' => 'Materi, slide, video, dan kuis untuk ' . strtolower($title) . '.',
                'starts_at' => now()->setDate(2026, 10, 24)->setTime(9, 0)->addMinutes(($order - 1) * 25),
                'is_published' => true,
            ]);

            foreach ([
                ['PDF Ringkasan ' . $title, 'pdf', "https://example.com/isoc/pertemuan-{$order}/ringkasan.pdf"],
                ['Slide PPT ' . $title, 'ppt', "https://example.com/isoc/pertemuan-{$order}/slide.pptx"],
                ['Video Pembelajaran ' . $title, 'video', "https://example.com/isoc/pertemuan-{$order}/video"],
            ] as $index => [$materialTitle, $type, $url]) {
                LearningMaterial::create([
                    'learning_meeting_id' => $meeting->id,
                    'order' => $index + 1,
                    'title' => $materialTitle,
                    'type' => $type,
                    'external_url' => $url,
                    'is_published' => true,
                ]);
            }

            return $meeting;
        });
    }

    private function seedAssessments(LearningEvent $event, $meetings, $participants): void
    {
        $pre = Assessment::create([
            'learning_event_id' => $event->id,
            'learning_meeting_id' => $meetings->first()->id,
            'type' => 'pre',
            'title' => 'Pre-Test Digital Safety Champions',
            'passing_score' => 70,
            'is_open' => true,
            'questions' => $this->questions('pre'),
        ]);

        $post = Assessment::create([
            'learning_event_id' => $event->id,
            'learning_meeting_id' => $meetings->first()->id,
            'type' => 'post',
            'title' => 'Post-Test Digital Safety Champions',
            'passing_score' => 85,
            'is_open' => true,
            'questions' => $this->questions('post'),
        ]);

        $quizzes = collect();

        foreach ($meetings as $meeting) {
            $quizzes->push(Assessment::create([
                'learning_event_id' => $event->id,
                'learning_meeting_id' => $meeting->id,
                'type' => 'quiz',
                'title' => 'Kuis ' . $meeting->title,
                'passing_score' => 85,
                'is_open' => true,
                'questions' => $this->questions('quiz'),
            ]));
        }

        foreach ($participants as $index => $participant) {
            AssessmentAttempt::create([
                'assessment_id' => $pre->id,
                'participant_id' => $participant->id,
                'score' => 65 + $index,
                'threat_identification' => 60 + $index,
                'self_efficacy' => 55,
                'submitted_at' => now()->subDays(2),
                'correct_count' => 3,
                'total_questions' => 5,
            ]);

            AssessmentAttempt::create([
                'assessment_id' => $post->id,
                'participant_id' => $participant->id,
                'score' => 86 + $index,
                'threat_identification' => 82 + $index,
                'self_efficacy' => 70 + $index,
                'submitted_at' => now()->subDay(),
                'correct_count' => 5,
                'total_questions' => 5,
            ]);

            foreach ($quizzes as $quizIndex => $quiz) {
                AssessmentAttempt::create([
                    'assessment_id' => $quiz->id,
                    'participant_id' => $participant->id,
                    'score' => min(100, 88 + $index + ($quizIndex % 2)),
                    'threat_identification' => min(100, 84 + $index),
                    'self_efficacy' => min(100, 72 + $index),
                    'submitted_at' => now()->subDay()->addMinutes($quizIndex * 8),
                    'correct_count' => 5,
                    'total_questions' => 5,
                ]);
            }
        }

        MicrositePractice::create([
            'participant_id' => $participants->first()->id,
            'sid_url' => 'https://s.id/dsc-smkn2-jakarta',
            'notes' => 'Microsite praktik peserta untuk simulasi kampanye keamanan digital.',
            'status' => 'reviewed',
        ]);
    }

    private function questions(string $type): array
    {
        $question = match ($type) {
            'post' => 'Jika akun media sosial teman diretas, langkah awal yang paling tepat adalah...',
            'quiz' => 'Apa kebiasaan aman yang perlu dilakukan setelah menerima tautan mencurigakan?',
            default => 'Apa tindakan paling aman saat menerima tautan mencurigakan dari orang tidak dikenal?',
        };

        return [
            [
                'question' => $question,
                'options' => [
                    ['text' => 'Cek sumber, jangan isi data pribadi, dan laporkan jika mencurigakan', 'is_correct' => true],
                    ['text' => 'Langsung klik agar tahu isinya', 'is_correct' => false],
                    ['text' => 'Sebarkan ke grup agar ramai', 'is_correct' => false],
                    ['text' => 'Kirim OTP agar proses cepat', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Mengapa MFA atau verifikasi dua langkah penting?',
                'options' => [
                    ['text' => 'Menambah lapisan keamanan saat login', 'is_correct' => true],
                    ['text' => 'Menghapus semua risiko internet', 'is_correct' => false],
                    ['text' => 'Membuat password tidak diperlukan', 'is_correct' => false],
                    ['text' => 'Membuat akun otomatis viral', 'is_correct' => false],
                ],
            ],
            [
                'question' => 'Apa contoh data pribadi yang harus dilindungi?',
                'options' => [
                    ['text' => 'Nomor identitas, alamat, nomor telepon, dan password', 'is_correct' => true],
                    ['text' => 'Judul lagu favorit saja', 'is_correct' => false],
                    ['text' => 'Warna tas sekolah', 'is_correct' => false],
                    ['text' => 'Nama mata pelajaran umum', 'is_correct' => false],
                ],
            ],
        ];
    }

    private function seedOperations(LearningEvent $event, School $school, $participants, $tutors, User $admin): void
    {
        $session = TrainingSession::create([
            'school_id' => $school->id,
            'title' => 'Pelatihan OTS Digital Safety Champions',
            'date' => $school->training_date,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => 'done',
            'notes' => 'Ice breaking, 6 modul OTS, praktik microsite s.id, role-play, diskusi, open mic, dan refleksi.',
        ]);

        foreach ($participants as $participant) {
            Attendance::create(['training_session_id' => $session->id, 'participant_id' => $participant->id, 'status' => 'hadir', 'recorded_by' => $admin->id]);
        }

        foreach ($tutors as $tutor) {
            Attendance::create(['training_session_id' => $session->id, 'tutor_id' => $tutor->id, 'status' => 'hadir', 'recorded_by' => $admin->id]);
        }

        WagGroup::create(['school_id' => $school->id, 'name' => 'WAG ISOC Champion - SMK Negeri 2 Jakarta', 'invite_link' => 'https://chat.whatsapp.com/contoh-dsc-smkn2', 'member_count' => 75, 'active_members' => 62]);
        PeerGroup::create(['school_id' => $school->id, 'tutor_id' => $tutors->first()->id, 'name' => 'Kelompok Belajar Sebaya SMK Negeri 2 Jakarta', 'member_count' => 25]);

        foreach (array_keys(Evidence::TYPES) as $index => $type) {
            Evidence::create([
                'learning_event_id' => $event->id,
                'school_id' => $school->id,
                'type' => $type,
                'session_index' => str_contains($type, 'foto') ? (($index % 6) + 1) : null,
                'file_path' => 'images/sena-logo.png',
                'link' => 'https://example.com/bukti/dsc-smkn2/' . $type,
                'status' => $index < 9 ? 'approved' : 'pending',
                'uploaded_by' => $admin->id,
                'verified_by' => $index < 9 ? 1 : null,
                'verified_at' => $index < 9 ? now() : null,
            ]);
        }
    }

    private function seedCertificate(LearningEvent $event, Participant $participant): void
    {
        $template = CertificateTemplate::create([
            'name' => 'Template Sertifikat DSC - Landscape',
            'orientation' => 'landscape',
            'width_mm' => 297,
            'height_mm' => 210,
            'background_color' => '#ffffff',
            'is_default' => true,
            'elements' => $this->certificateTemplateElements(),
        ]);

        $event->update(['certificate_template_id' => $template->id]);

        Certificate::create([
            'learning_event_id' => $event->id,
            'participant_id' => $participant->id,
            'certificate_template_id' => $template->id,
            'number' => 'DSC/2026/0001',
            'status' => 'issued',
            'eligibility_status' => 'eligible',
            'eligibility_checked_at' => now(),
            'issued_at' => now(),
        ]);
    }

    private function certificateTemplateElements(): array
    {
        return [
            ['type' => 'event_partner_logos', 'label' => 'Logo Mitra Event', 'x' => 18, 'y' => 13, 'width' => 86, 'height' => 16],
            ['type' => 'sena_logo', 'label' => 'Logo Sena', 'image_path' => '/images/sena-logo.png', 'x' => 230, 'y' => 13, 'width' => 45, 'height' => 18],
            ['type' => 'white_box', 'label' => 'Penutup nomor sertifikat', 'x' => 65, 'y' => 56.8, 'width' => 167, 'height' => 12, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Nomor sertifikat', 'content' => 'No. {{certificate_number}}', 'x' => 70, 'y' => 57.8, 'width' => 157, 'height' => 9, 'font_size' => 14, 'font_weight' => '500', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup label penerima', 'x' => 66, 'y' => 69, 'width' => 165, 'height' => 8, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Diberikan kepada', 'content' => 'diberikan kepada:', 'x' => 70, 'y' => 70.2, 'width' => 157, 'height' => 8, 'font_size' => 18, 'font_weight' => '400', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup nama peserta', 'x' => 44, 'y' => 78.2, 'width' => 209, 'height' => 18, 'color' => '#ffffff'],
            ['type' => 'participant_name', 'label' => 'Nama peserta', 'x' => 48, 'y' => 80, 'width' => 201, 'height' => 15, 'font_size' => 26, 'font_weight' => '800', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'white_box', 'label' => 'Penutup narasi kegiatan', 'x' => 0, 'y' => 96.5, 'width' => 297, 'height' => 43, 'color' => '#ffffff'],
            ['type' => 'custom_text', 'label' => 'Narasi kegiatan', 'content' => "Atas dedikasi, integritas, dan keberhasilan menyelesaikan seluruh rangkaian pelatihan intensif\n{{event_title}}\nyang diselenggarakan pada {{event_date}}, serta dinyatakan kompeten sebagai:\n{{competency_title}}", 'x' => 14, 'y' => 98.8, 'width' => 269, 'height' => 40, 'font_size' => 13.5, 'font_weight' => '700', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'TTD Penyelenggara', 'content' => "________________________\n{{organizer_name}}\nPenyelenggara", 'x' => 35, 'y' => 148, 'width' => 85, 'height' => 34, 'font_size' => 9, 'font_weight' => '600', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'custom_text', 'label' => 'TTD Tutor / Mitra', 'content' => "________________________\n{{tutor_name}}\n{{tutor_institution}}", 'x' => 177, 'y' => 148, 'width' => 85, 'height' => 34, 'font_size' => 9, 'font_weight' => '600', 'align' => 'center', 'color' => '#202427'],
            ['type' => 'qr_code', 'label' => 'QR Verifikasi', 'x' => 255, 'y' => 172, 'width' => 24, 'height' => 24],
        ];
    }

    private function seedWebinarEvent(
        School $school,
        User $admin,
        User $superAdmin,
        Participant $participant,
        Tutor $tutor,
        array $budgetItems,
        $partners,
    ): void {
        $event = LearningEvent::query()->updateOrCreate(
            ['slug' => 'webinar-digital-safety-awareness'],
            [
                'school_id' => $school->id,
                'created_by' => $admin->id,
                'title' => 'Webinar Digital Safety Awareness - ISOC 2026',
                'description' => 'Webinar penguatan keamanan digital untuk peserta Digital Safety Champions. Peserta mengikuti sesi melalui Zoom, membaca materi, dan menyelesaikan pre-test, kuis modul, serta post-test.',
                'event_type' => 'webinar',
                'audience_type' => 'general',
                'zoom_url' => 'https://zoom.us/j/9876543210?pwd=isoc2026',
                'starts_at' => '2026-11-05 13:00:00',
                'ends_at' => '2026-11-05 15:00:00',
                'training_start_time' => '13:00',
                'training_end_time' => '15:00',
                'attendance_code' => 'WEB26A',
                'workflow_status' => 'verified_term_1',
                'publish_approval_status' => 'published',
                'target_participants' => 100,
                'target_tutors' => 3,
                'status' => 'active',
                'registration_open' => true,
                'is_published' => true,
                'participant_rows' => [[
                    'name' => $participant->user?->name,
                    'email' => $participant->user?->email,
                    'phone' => $participant->user?->phone,
                    'nis' => $participant->nis,
                    'grade' => $participant->grade,
                    'gender' => $participant->gender,
                    'birth_date' => $participant->birth_date ? (string) $participant->birth_date : null,
                ]],
                'tutor_rows' => [[
                    'name' => $tutor->user?->name,
                    'email' => $tutor->user?->email,
                    'phone' => $tutor->user?->phone,
                    'institution' => $tutor->institution,
                ]],
                'budget_items' => $budgetItems,
                'rundown_items' => [
                    ['start_time' => '13:00', 'end_time' => '13:10', 'activity' => 'Pembukaan webinar dan orientasi Zoom', 'pic' => 'Admin RTIK Local', 'notes' => 'Cek nama peserta dan aturan sesi online.'],
                    ['start_time' => '13:10', 'end_time' => '13:30', 'activity' => 'Pre-Test dan baseline keamanan digital', 'pic' => 'Tutor', 'notes' => 'Peserta mengisi pre-test sebelum materi.'],
                    ['start_time' => '13:30', 'end_time' => '14:35', 'activity' => 'Materi keamanan digital dan studi kasus', 'pic' => 'Tutor', 'notes' => 'Paparan modul, diskusi, dan kuis.'],
                    ['start_time' => '14:35', 'end_time' => '14:50', 'activity' => 'Post-Test dan refleksi', 'pic' => 'Tutor', 'notes' => 'Post-test setelah kuis modul selesai.'],
                    ['start_time' => '14:50', 'end_time' => '15:00', 'activity' => 'Penutupan dan arahan tindak lanjut', 'pic' => 'Admin RTIK Local', 'notes' => 'Bagikan WAG dan reminder sertifikat.'],
                ],
                'evidence_checklist' => $this->evidenceChecklist(),
                'local_admin_notes' => 'Event webinar sudah dilengkapi dengan link Zoom dan rundown online.',
                'central_admin_notes' => 'Publish webinar disetujui. Termin 1 aktif setelah publish.',
                'publish_revision_notes' => null,
                'local_update_summary' => 'Seeder: webinar dibuat oleh Admin RTIK Local dan sudah dipublish pusat.',
                'local_updated_at' => now(),
                'publish_approved_by' => $superAdmin->id,
                'publish_approved_at' => now(),
            ],
        );

        $event->participants()->syncWithoutDetaching([
            $participant->id => [
                'status' => 'registered',
                'registered_at' => now(),
                'admin_approval_status' => 'approved',
                'admin_approved_by' => $admin->id,
                'admin_approved_at' => now(),
                'tutor_approval_status' => 'approved',
                'tutor_approved_by' => $tutor->user_id,
                'tutor_approved_at' => now(),
            ],
        ]);

        $event->tutors()->syncWithoutDetaching([
            $tutor->id => ['status' => 'assigned', 'assigned_at' => now()],
        ]);
        $event->partners()->sync($partners->pluck('id')->all());

        $event->meetings()->delete();
        $event->assessments()->delete();

        $meetings = $this->seedMeetings($event);
        $this->seedAssessments($event, $meetings, collect([$participant]));
        $this->seedPayments($event, $school, $superAdmin);
    }
}
