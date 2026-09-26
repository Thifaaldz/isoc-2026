<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Evidence;
use App\Models\MicrositePractice;
use App\Models\Module;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\PeerGroup;
use App\Models\School;
use App\Models\Tutor;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\WagGroup;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Semua akun dummy memakai password: "password" (di-hash otomatis oleh cast User). */
    public function run(): void
    {
        // ---- Sekolah / lokasi ----
        $schools = collect([
            ['SMA Negeri 1 Jakarta', '20100001', 'SMA', 'Jakarta Pusat', '2026-10-05'],
            ['SMK Negeri 2 Jakarta', '20100002', 'SMK', 'Jakarta Selatan', '2026-10-12'],
            ['SMA Negeri 3 Jakarta', '20100003', 'SMA', 'Jakarta Timur', '2026-10-19'],
        ])->map(fn ($s) => School::create([
            'name' => $s[0], 'npsn' => $s[1], 'type' => $s[2], 'city' => $s[3],
            'province' => 'DKI Jakarta', 'address' => 'Alamat contoh, ' . $s[3],
            'pic_name' => 'PIC ' . $s[0], 'pic_phone' => '081200000000',
            'participant_target' => 100, 'training_date' => $s[4],
        ]));
        $school = $schools->first();

        // ---- 4 akun inti ----
        User::create(['name' => 'Super Admin ISOC', 'email' => 'su@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin]);
        User::create(['name' => 'Admin RTIK', 'email' => 'adm@isoc.id', 'password' => 'password', 'role' => UserRole::Admin]);

        $tutorUser = User::create([
            'name' => 'Tutor Contoh', 'email' => 'tutor@isoc.id', 'password' => 'password',
            'role' => UserRole::Tutor, 'school_id' => $school->id, 'phone' => '081311110000',
        ]);
        $tutor = Tutor::create(['user_id' => $tutorUser->id, 'school_id' => $school->id, 'institution' => 'RTIK Jakarta', 'tot_completed' => true, 'is_cadre' => true]);

        $pesertaUser = User::create([
            'name' => 'Peserta Contoh', 'email' => 'peserta@isoc.id', 'password' => 'password',
            'role' => UserRole::Peserta, 'school_id' => $school->id, 'phone' => '081322220000',
        ]);
        $peserta = Participant::create([
            'user_id' => $pesertaUser->id, 'school_id' => $school->id, 'nis' => '1001', 'grade' => 'X',
            'gender' => 'L', 'consent_at' => now(), 'followed_instagram' => true, 'joined_wag' => true, 'registered_ecert' => true,
        ]);

        // ---- Tambahan tutor & peserta dummy (total 3 tutor, 10 peserta di sekolah pertama) ----
        foreach ([2, 3] as $i) {
            $u = User::create(['name' => "Tutor $i", 'email' => "tutor$i@isoc.id", 'password' => 'password', 'role' => UserRole::Tutor, 'school_id' => $school->id]);
            Tutor::create(['user_id' => $u->id, 'school_id' => $school->id, 'institution' => 'RTIK Jakarta']);
        }
        $participants = collect([$peserta]);
        foreach (range(2, 10) as $i) {
            $u = User::create(['name' => "Peserta $i", 'email' => "peserta$i@isoc.id", 'password' => 'password', 'role' => UserRole::Peserta, 'school_id' => $school->id]);
            $participants->push(Participant::create([
                'user_id' => $u->id, 'school_id' => $school->id, 'nis' => (string) (1000 + $i),
                'grade' => ['X', 'XI', 'XII'][$i % 3], 'gender' => $i % 2 ? 'L' : 'P', 'consent_at' => now(),
            ]));
        }

        // ---- 6 modul OTS ----
        $modules = [
            [1, 'Online Scam & Penipuan Daring', 'Mengenali modus penipuan daring.'],
            [2, 'Perlindungan Perangkat (Device)', 'Mengamankan perangkat dan akun.'],
            [3, 'Informasi Palsu (Hoaks)', 'Verifikasi informasi dan literasi media.'],
            [4, 'Peretasan Media Sosial', 'Mencegah dan menangani peretasan akun.'],
            [5, 'Internet Aman & Data Pribadi', 'Praktik internet aman, jejak digital, microsite s.id.'],
            [6, 'Penanganan Insiden Digital', 'Cyberbullying, pelaporan, dan mitigasi insiden.'],
        ];
        foreach ($modules as [$n, $t, $d]) {
            Module::create(['number' => $n, 'title' => "Modul $n: $t", 'description' => $d, 'content' => "Materi $t (konten contoh)."]);
        }

        // ---- Pre-test & post-test ----
        $pre = Assessment::create(['type' => 'pre', 'title' => 'Pre-Test Literasi OTS', 'form_url' => 'https://example.com/pretest', 'passing_score' => 70]);
        $post = Assessment::create(['type' => 'post', 'title' => 'Post-Test Literasi OTS', 'form_url' => 'https://example.com/posttest', 'passing_score' => 85]);
        foreach ($participants as $i => $p) {
            AssessmentAttempt::create(['assessment_id' => $pre->id, 'participant_id' => $p->id, 'score' => 60 + $i, 'threat_identification' => 55 + $i, 'self_efficacy' => 55, 'submitted_at' => now()->subDays(3)]);
            AssessmentAttempt::create(['assessment_id' => $post->id, 'participant_id' => $p->id, 'score' => 84 + ($i % 8), 'threat_identification' => 82 + ($i % 10), 'self_efficacy' => 70 + $i, 'submitted_at' => now()->subDay()]);
        }
        MicrositePractice::create(['participant_id' => $peserta->id, 'sid_url' => 'https://s.id/contoh-dsc', 'notes' => 'Microsite praktik contoh']);

        // ---- Sesi pelatihan & kehadiran ----
        $session = TrainingSession::create(['school_id' => $school->id, 'title' => 'Pelatihan OTS 180 menit', 'date' => $school->training_date, 'status' => 'done', 'notes' => 'Role-play, open mic, refleksi siswa.']);
        foreach ($participants as $p) {
            Attendance::create(['training_session_id' => $session->id, 'participant_id' => $p->id, 'status' => 'hadir', 'recorded_by' => $tutorUser->id]);
        }
        Attendance::create(['training_session_id' => $session->id, 'tutor_id' => $tutor->id, 'status' => 'hadir', 'recorded_by' => $tutorUser->id]);
        foreach ($schools->skip(1) as $s) {
            TrainingSession::create(['school_id' => $s->id, 'title' => 'Pelatihan OTS 180 menit', 'date' => $s->training_date]);
        }

        // ---- Komunitas ----
        WagGroup::create(['school_id' => $school->id, 'name' => 'WAG ISOC Champion - ' . $school->name, 'invite_link' => 'https://chat.whatsapp.com/contoh', 'member_count' => 10, 'active_members' => 8]);
        PeerGroup::create(['school_id' => $school->id, 'tutor_id' => $tutor->id, 'name' => 'Kelompok Sebaya ' . $school->name, 'member_count' => 10]);

        // ---- Bukti dukung ----
        foreach (array_keys(Evidence::TYPES) as $i => $type) {
            Evidence::create([
                'school_id' => $school->id, 'type' => $type, 'link' => 'https://example.com/bukti/' . $type,
                'status' => $i < 3 ? 'approved' : 'pending', 'uploaded_by' => 2,
                'verified_by' => $i < 3 ? 1 : null, 'verified_at' => $i < 3 ? now() : null,
            ]);
        }

        // ---- e-Certificate & termin ----
        Certificate::create(['participant_id' => $peserta->id, 'number' => 'DSC/2026/0001', 'status' => 'issued', 'issued_at' => now()]);
        foreach ($schools as $s) {
            Payment::create(['school_id' => $s->id, 'term' => 1, 'amount' => 10000000, 'status' => 'paid', 'paid_at' => '2026-10-01']);
            Payment::create(['school_id' => $s->id, 'term' => 2, 'amount' => 10000000, 'status' => 'pending']);
        }
    }
}
