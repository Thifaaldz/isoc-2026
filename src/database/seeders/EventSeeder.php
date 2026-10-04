<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\ModuleTemplate;
use App\Models\School;
use App\Models\TotAssessment;
use App\Models\Tutor;
use App\Models\User;
use App\Services\CertificateEligibilityService;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use App\Support\TorEventTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 20 lokus Digital Safety Champions per kab/kota dengan template sertifikat Sena.
 *  - Lokus pertama (Pangkalpinang): event sudah selesai seluruhnya (approve, publish, ToT, tes peserta,
 *    bukti dukung, laporan final, Termin-1 & Termin-2 eligible, sertifikat terbit).
 *  - Lokus lainnya: baru ditambahkan Fasilitator dan disubmit, menunggu "Approve Event" oleh Admin ISOC.
 * Tutor tutor.daerah@isoc.id ditugaskan di semua event (lokus lain otomatis saat Approve Event).
 *
 * Jalankan: php artisan db:seed --class=EventSeeder (aman diulang, event yang sudah ada dilewati).
 */
class EventSeeder extends Seeder
{
    public const TUTOR_EMAIL = 'tutor.daerah@isoc.id';

    private const CERTIFICATE_TEMPLATE = 'Template Sertifikat Sena - Landscape';

    /** [kab/kota, kode kab/kota, nama kab/kota (wilayah), kode provinsi, provinsi] */
    private const LOKUS = [
        ['Pangkalpinang', '1971', 'KOTA PANGKAL PINANG', '19', 'KEPULAUAN BANGKA BELITUNG'],
        ['Denpasar', '5171', 'KOTA DENPASAR', '51', 'BALI'],
        ['Tangerang', '3671', 'KOTA TANGERANG', '36', 'BANTEN'],
        ['Bengkulu', '1771', 'KOTA BENGKULU', '17', 'BENGKULU'],
        ['Jogjakarta', '3471', 'KOTA YOGYAKARTA', '34', 'DI YOGYAKARTA'],
        ['Kota Jambi', '1571', 'KOTA JAMBI', '15', 'JAMBI'],
        ['Bandung', '3273', 'KOTA BANDUNG', '32', 'JAWA BARAT'],
        ['Cirebon', '3274', 'KOTA CIREBON', '32', 'JAWA BARAT'],
        ['Garut', '3205', 'KABUPATEN GARUT', '32', 'JAWA BARAT'],
        ['Kota Bogor', '3271', 'KOTA BOGOR', '32', 'JAWA BARAT'],
        ['Karawang', '3215', 'KABUPATEN KARAWANG', '32', 'JAWA BARAT'],
        ['Semarang', '3374', 'KOTA SEMARANG', '33', 'JAWA TENGAH'],
        ['Malang', '3573', 'KOTA MALANG', '35', 'JAWA TIMUR'],
        ['Surabaya', '3578', 'KOTA SURABAYA', '35', 'JAWA TIMUR'],
        ['Pontianak', '6171', 'KOTA PONTIANAK', '61', 'KALIMANTAN BARAT'],
        ['Banjarmasin', '6371', 'KOTA BANJARMASIN', '63', 'KALIMANTAN SELATAN'],
        ['Bandar Lampung', '1871', 'KOTA BANDAR LAMPUNG', '18', 'LAMPUNG'],
        ['Pekanbaru', '1471', 'KOTA PEKANBARU', '14', 'RIAU'],
        ['Palopo', '7373', 'KOTA PALOPO', '73', 'SULAWESI SELATAN'],
        ['Padang', '1371', 'KOTA PADANG', '13', 'SUMATERA BARAT'],
    ];

    /** Peserta event selesai (lokus pertama): [nama, NIS, jenis kelamin, email]. */
    private const PESERTA_SELESAI = [
        ['Aditya Pratama', '0071900101', 'L', 'aditya.pangkalpinang@isoc.id'],
        ['Bunga Maharani', '0071900102', 'P', 'bunga.pangkalpinang@isoc.id'],
        ['Citra Lestari', '0071900103', 'P', 'citra.pangkalpinang@isoc.id'],
        ['Dimas Saputra', '0071900104', 'L', 'dimas.pangkalpinang@isoc.id'],
        ['Eka Rahmawati', '0071900105', 'P', 'eka.pangkalpinang@isoc.id'],
    ];

    /** Urutan mitra mengikuti banner kegiatan. */
    public function run(): void
    {
        // Event bawaan migration awal (2026_09_01_000006) tidak dipakai lagi.
        LearningEvent::query()->where('slug', 'digital-safety-champions')->get()->each->delete();

        $admin = User::query()->where('email', 'adm@isoc.id')->firstOrFail();
        $superAdmin = User::query()->where('email', 'su@isoc.id')->firstOrFail();
        $materi = ModuleTemplate::query()->where('name', MateriSeeder::MATERI_SISWA)->firstOrFail();
        $materiModuleIds = $materi->learningMeetings()->whereNull('learning_event_id')->orderBy('order')
            ->limit(LearningEvent::MODULES_PER_EVENT)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $certificateTemplateId = CertificateTemplate::query()->where('name', self::CERTIFICATE_TEMPLATE)->value('id');
        $partnerIds = collect(TorEventTemplate::partnerIds());
        $tutor = null;

        foreach (self::LOKUS as $index => [$kota, $cityCode, $city, $provinceCode, $province]) {
            $provinsi = Str::title($province);

            $school = School::query()->firstOrCreate(['name' => "Lokus DSC {$kota}"], [
                'type' => 'SMA',
                'province_code' => $provinceCode,
                'province' => $province,
                'city_code' => $cityCode,
                'city' => $city,
                'address' => "{$kota}, {$provinsi}",
                'maps_url' => 'https://maps.google.com/?q=' . urlencode("{$kota}, {$provinsi}"),
                'participant_target' => 100,
                'status' => 'active',
            ]);

            $tutor ??= $this->tutor($school);
            $slug = 'digital-safety-champions-' . Str::slug($kota);

            if (LearningEvent::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $selesai = $index === 0;
            // Event selesai sudah dilaksanakan; lokus lain jadwal sementara tiap Sabtu mulai 7 November 2026, 2 lokus per minggu.
            $date = $selesai ? '2026-09-26' : now()->setDate(2026, 11, 7)->addWeeks(intdiv($index - 1, 2))->toDateString();

            $event = LearningEvent::query()->create(LearningEventResource::normalizeEventScheduleData([
                'created_by' => $admin->id,
                'title' => "Digital Safety Champions - {$kota}",
                'slug' => $slug,
                'description' => TorEventTemplate::DESCRIPTION . " Lokus {$kota}, {$provinsi}.",
                'event_type' => 'offline',
                'audience_type' => 'school',
                'school_id' => $school->id,
                'module_template_id' => $materi->id,
                // TOR: 2 modul ajar dibawakan per lokasi.
                'selected_meeting_ids' => $materiModuleIds,
                'certificate_template_id' => $certificateTemplateId,
                'starts_at' => "{$date} 09:00:00",
                'ends_at' => "{$date} 12:00:00",
                'target_participants' => 100,
                'target_tutors' => 3,
                'workflow_status' => 'draft',
                'publish_approval_status' => 'draft',
                'status' => 'draft',
                'is_published' => false,
                'registration_open' => false,
                'participant_rows' => $selesai ? $this->participantRows($school) : [],
                'tutor_rows' => [],
                'selected_tutor_ids' => [$tutor->id],
                'rundown_items' => TorEventTemplate::rundown(),
                'budget_items' => TorEventTemplate::termChecklist(),
                'local_admin_notes' => "Pengajuan pelatihan Digital Safety Champions di {$kota}, {$provinsi}.",
            ]));

            foreach ($partnerIds as $order => $partnerId) {
                $event->partners()->attach($partnerId, ['sort_order' => $order + 1]);
            }

            app(ModuleTemplateApplier::class)->applyToEvent($event);
            app(LearningEventProvisioner::class)->provisionPaymentTerms($event);

            // Submit Pengajuan: menunggu Approve Event oleh Admin ISOC.
            $event->update([
                'workflow_status' => 'submitted',
                'local_updated_at' => now(),
                'local_update_summary' => 'Pengajuan event dikirim ke Admin ISOC.',
            ]);

            if ($selesai) {
                $this->complete($event->refresh(), $school, $admin, $superAdmin);
            }
        }
    }

    private function tutor(School $school): Tutor
    {
        $user = User::query()->firstOrCreate(['email' => self::TUTOR_EMAIL], [
            'name' => 'Tutor Daerah',
            'password' => 'password',
            'role' => UserRole::Tutor,
            'school_id' => $school->id,
            'phone' => '081300001900',
        ]);

        return Tutor::query()->firstOrCreate(['user_id' => $user->id], [
            'school_id' => $school->id,
            'institution' => 'Relawan TIK Indonesia',
            'tot_completed' => false,
        ]);
    }

    private function participantRows(School $school): array
    {
        return collect(self::PESERTA_SELESAI)
            ->map(fn (array $row) => [
                'name' => $row[0],
                'nis' => $row[1],
                'grade' => 'XI',
                'organization' => $school->name,
                'position' => 'Siswa',
                'gender' => $row[2],
                'birth_date' => null,
                'phone' => '0812' . substr($row[1], -6),
                'email' => $row[3],
            ])
            ->all();
    }

    /** Menjalankan seluruh alur sampai selesai, seperti tombol-tombol di panel Admin ISOC, Fasilitator, tutor, dan peserta. */
    private function complete(LearningEvent $event, School $school, User $admin, User $superAdmin): void
    {
        $provisioner = app(LearningEventProvisioner::class);

        // Approve Event (akun peserta & tutor) lalu Publish + Termin-1.
        $provisioner->provisionAccounts($event);
        $event->update([
            'workflow_status' => 'verified_term_1',
            'publish_approval_status' => 'published',
            'status' => 'active',
            'is_published' => true,
            'registration_open' => true,
            'central_admin_notes' => 'Data lengkap. Event dipublish dan Termin-1 eligible.',
            'publish_approved_by' => $superAdmin->id,
            'publish_approved_at' => now()->subDays(20),
        ]);
        $event->payments()->where('term', 1)->update([
            'status' => 'eligible',
            'notes' => 'Event dipublish. Termin-1 eligible.',
            'approved_by' => $superAdmin->id,
            'approved_at' => now()->subDays(20),
        ]);

        $event->refresh()->load(['tutors.user', 'participants.user', 'assessments']);

        // Tutor lulus ToT (nilai 100).
        foreach ($event->tutors as $tutor) {
            TotAssessment::query()->updateOrCreate(
                ['learning_event_id' => $event->id, 'tutor_id' => $tutor->id],
                ['score' => 100, 'completed_at' => now()->subDays(15), 'assessed_by' => $tutor->user_id, 'notes' => 'Lulus pelatihan awal tutor melalui LMS ToT.'],
            );
            $tutor->update(['tot_completed' => true]);
        }

        // Peserta menyelesaikan pre-test, semua kuis modul, post-test, dan link s.id.
        foreach ($event->participants as $index => $participant) {
            foreach ($event->assessments as $assessment) {
                $questions = collect(is_array($assessment->questions) ? $assessment->questions : json_decode((string) $assessment->questions, true) ?? [])->values();
                $answers = $questions->map(fn (array $question) => collect($question['options'] ?? [])->values()->search(fn ($option) => (bool) ($option['is_correct'] ?? false)))->all();
                // Variasi skor pre-test: peserta salah 0-2 soal.
                $wrong = $assessment->type === 'pre' ? $index % 3 : 0;
                $correct = max(0, $questions->count() - $wrong);

                AssessmentAttempt::query()->updateOrCreate(
                    ['assessment_id' => $assessment->id, 'participant_id' => $participant->id],
                    [
                        'answers' => $answers,
                        'correct_count' => $correct,
                        'total_questions' => $questions->count(),
                        'score' => round($correct / max(1, $questions->count()) * 100, 2),
                        'submitted_at' => now()->subDays(7),
                    ],
                );
            }

            MicrositePractice::query()->updateOrCreate(
                ['participant_id' => $participant->id, 'learning_event_id' => $event->id],
                ['sid_url' => 'https://s.id/dsc-pangkalpinang-' . $participant->id, 'notes' => 'Microsite kampanye keamanan digital.', 'status' => 'reviewed'],
            );
        }

        // Bukti dukung diunggah Fasilitator/tutor dan disetujui Admin ISOC.
        $tutorUserId = $event->tutors->first()?->user_id ?? $admin->id;
        $evidence = [
            ['type' => 'absensi_basah', 'session_index' => 1, 'label' => 'ABSENSI BASAH SESI 1', 'by' => $admin->id],
            ['type' => 'video_slogan', 'link' => 'https://youtu.be/pLxS9dVhGGU', 'by' => $tutorUserId],
            ['type' => 'praktik_microsite', 'label' => 'PRAKTIK MICROSITE s.id', 'link' => 'https://s.id/dsc-pangkalpinang', 'by' => $admin->id],
        ];

        foreach (Evidence::REQUIRED_PHOTOS as $type => $photo) {
            foreach (range(1, $photo['min']) as $number) {
                $evidence[] = ['type' => $type, 'label' => strtoupper("FOTO {$photo['step']} - {$photo['title']} {$number}"), 'by' => $tutorUserId, 'key' => "{$type}-{$number}"];
            }
        }

        foreach ($evidence as $item) {
            Evidence::query()->create([
                'learning_event_id' => $event->id,
                'school_id' => $school->id,
                'type' => $item['type'],
                'session_index' => $item['session_index'] ?? null,
                'file_path' => isset($item['label']) ? $this->placeholderImage(($item['key'] ?? $item['type']) . '-' . $event->id, $item['label']) : null,
                'link' => $item['link'] ?? null,
                'status' => 'approved',
                'uploaded_by' => $item['by'],
                'verified_by' => $superAdmin->id,
                'verified_at' => now()->subDays(3),
                'review_notes' => 'Bukti sesuai.',
            ]);
        }

        // Laporan final dikirim lalu di-approve Admin ISOC: Termin-2 eligible.
        $event->update([
            'workflow_status' => 'verified_term_2',
            'final_report_status' => 'approved',
            'final_report_submitted_at' => now()->subDays(2),
            'final_report_approved_by' => $superAdmin->id,
            'final_report_approved_at' => now()->subDay(),
            'final_report_notes' => 'Laporan lengkap.',
            'local_updated_at' => now()->subDays(2),
            'local_update_summary' => 'Laporan kegiatan final dikirim ke Admin ISOC.',
        ]);
        $event->payments()->where('term', 2)->update([
            'status' => 'eligible',
            'notes' => 'Laporan final approved. Termin-2 eligible.',
            'approved_by' => $superAdmin->id,
            'approved_at' => now()->subDay(),
        ]);

        // Cek Eligibility lalu Terbitkan sertifikat peserta.
        $eligibility = app(CertificateEligibilityService::class);

        foreach ($event->participants as $participant) {
            $certificate = Certificate::query()->firstOrCreate(
                ['learning_event_id' => $event->id, 'participant_id' => $participant->id],
                [
                    'certificate_template_id' => $event->certificate_template_id,
                    'number' => 'DSC/' . now()->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                    'eligibility_status' => 'pending',
                ],
            );

            if ($eligibility->updateCertificate($certificate)->eligibility_status === 'eligible') {
                $certificate->update(['status' => 'issued', 'issued_at' => now()]);
            }
        }
    }

    /** Gambar placeholder sederhana untuk bukti dukung contoh. */
    private function placeholderImage(string $name, string $label): string
    {
        $path = 'evidences/demo-' . $name . '.png';
        $image = imagecreatetruecolor(1200, 800);
        imagefill($image, 0, 0, imagecolorallocate($image, 14, 116, 144));
        $white = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, 5, 60, 360, $label, $white);
        imagestring($image, 3, 60, 400, 'Bukti dukung contoh - sena', $white);
        ob_start();
        imagepng($image);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($image);

        return $path;
    }
}
