<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\ModuleTemplate;
use App\Models\Partner;
use App\Models\School;
use App\Models\TotAssessment;
use App\Models\User;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use App\Support\IndonesiaRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Dua event contoh untuk demo alur approval:
 *  A. Event baru disubmit Admin RTIK Daerah, menunggu Approve Event + Publish/Termin-1.
 *  B. Event selesai dilaksanakan dan laporan final terkirim, menunggu Approve Laporan + Termin-2.
 *
 * Jalankan: php artisan db:seed --class=DemoWorkflowSeeder (aman diulang, event yang sudah ada dilewati).
 */
class DemoWorkflowSeeder extends Seeder
{
    private const MATERI_PESERTA = 'Materi Peserta DSC - Literasi Keamanan Digital';

    private const MATERI_SUMBER = 'Modul Ajar ToT - Digital Safety Champions';

    public function run(): void
    {
        $this->call(PartnerSeeder::class);

        $admin = User::query()->where('email', 'adm@isoc.id')->firstOrFail();
        $superAdmin = User::query()->where('email', 'su@isoc.id')->firstOrFail();
        $materi = $this->participantMaterial();

        $this->seedAwaitingPublish($admin, $materi);
        $this->seedAwaitingTermTwo($admin, $superAdmin, $materi);
    }

    /** Materi Event tipe Peserta, disalin dari isi Modul Ajar ToT (pertemuan, materi, pre/post-test, kuis). */
    private function participantMaterial(): ModuleTemplate
    {
        $existing = ModuleTemplate::query()->where('name', self::MATERI_PESERTA)->first();

        if ($existing) {
            return $existing;
        }

        $source = ModuleTemplate::query()->where('name', self::MATERI_SUMBER)->firstOrFail();

        $template = $source->replicate(['audience', 'name', 'description']);
        $template->fill([
            'name' => self::MATERI_PESERTA,
            'audience' => ModuleTemplate::AUDIENCE_PESERTA,
            'description' => 'Materi peserta Digital Safety Champions: keamanan digital, data pribadi, perundungan siber, hoaks, dan praktik microsite.',
            'is_active' => true,
        ])->save();

        foreach ($source->assessments()->whereIn('type', ['pre', 'post'])->get() as $assessment) {
            $assessment->replicate()->fill(['module_template_id' => $template->id])->save();
        }

        foreach ($source->learningMeetings()->with(['materials', 'assessments'])->orderBy('order')->get() as $meeting) {
            $newMeeting = $meeting->replicate()->fill(['module_template_id' => $template->id]);
            $newMeeting->save();

            foreach ($meeting->materials as $material) {
                $material->replicate()->fill(['learning_meeting_id' => $newMeeting->id])->save();
            }

            foreach ($meeting->assessments as $assessment) {
                $assessment->replicate()->fill([
                    'learning_meeting_id' => $newMeeting->id,
                    'module_template_id' => $assessment->module_template_id ? $template->id : null,
                ])->save();
            }
        }

        return $template;
    }

    /** Event A: disubmit daerah, menunggu Approve Event lalu Publish + Termin-1 oleh Pusat. */
    private function seedAwaitingPublish(User $admin, ModuleTemplate $materi): void
    {
        $slug = 'pelatihan-dsc-desember-2026-sma-negeri-5-bogor';

        if (LearningEvent::query()->where('slug', $slug)->exists()) {
            return;
        }

        $school = $this->school('SMA Negeri 5 Bogor', 'SMA', '20219005', ['JAWA BARAT', 'KOTA BOGOR', 'BOGOR TENGAH'], 'Jl. Manggis No. 5, Kota Bogor', 'Ibu Sari Wulandari', '081388800501');

        $event = $this->createEvent($admin, $materi, $school, [
            'title' => 'Pelatihan DSC Desember 2026 - SMA Negeri 5 Bogor',
            'slug' => $slug,
            'description' => 'Pelatihan literasi keamanan digital untuk siswa SMA Negeri 5 Bogor bersama Relawan TIK dan ISOC Indonesia Jakarta Chapter.',
            'starts_at' => '2026-12-05 08:00:00',
            'ends_at' => '2026-12-05 11:00:00',
            'participant_rows' => [
                $this->participantRow('Aulia Rahman', '0055500101', 'L', 'aulia.bogor@isoc.id', 'SMA Negeri 5 Bogor'),
                $this->participantRow('Bunga Lestari', '0055500102', 'P', 'bunga.bogor@isoc.id', 'SMA Negeri 5 Bogor'),
                $this->participantRow('Cahyo Pratama', '0055500103', 'L', 'cahyo.bogor@isoc.id', 'SMA Negeri 5 Bogor'),
            ],
            'tutor_rows' => [
                ['name' => 'Tutor Bogor', 'phone' => '081300000501', 'email' => 'tutor.bogor@isoc.id', 'institution' => 'Relawan TIK Kota Bogor', 'notes' => null],
            ],
            'local_admin_notes' => 'Pengajuan pelatihan DSC di SMA Negeri 5 Bogor. Mohon verifikasi data lokasi, peserta, tutor, dan RAB.',
        ]);

        $event->update([
            'workflow_status' => 'submitted',
            'publish_approval_status' => 'draft',
            'status' => 'draft',
            'is_published' => false,
            'registration_open' => false,
            'local_updated_at' => now(),
            'local_update_summary' => 'Pengajuan event dikirim ke Admin RTIK Pusat.',
        ]);
    }

    /** Event B: sudah dilaksanakan dan laporan final terkirim, menunggu Approve Laporan + Termin-2. */
    private function seedAwaitingTermTwo(User $admin, User $superAdmin, ModuleTemplate $materi): void
    {
        $slug = 'pelatihan-dsc-september-2026-smk-negeri-4-kota-tangerang';

        if (LearningEvent::query()->where('slug', $slug)->exists()) {
            return;
        }

        $school = $this->school('SMK Negeri 4 Kota Tangerang', 'SMK', '20622004', ['BANTEN', 'KOTA TANGERANG', 'CIPONDOH'], 'Jl. Veteran No. 4, Kota Tangerang', 'Bapak Dedi Kurniawan', '081388800402');

        $event = $this->createEvent($admin, $materi, $school, [
            'title' => 'Pelatihan DSC September 2026 - SMK Negeri 4 Kota Tangerang',
            'slug' => $slug,
            'description' => 'Pelatihan literasi keamanan digital untuk siswa SMK Negeri 4 Kota Tangerang.',
            'starts_at' => '2026-09-26 08:00:00',
            'ends_at' => '2026-09-26 11:00:00',
            'participant_rows' => [
                $this->participantRow('Bima Saputra', '0044400201', 'L', 'bima.tangerang@isoc.id', 'SMK Negeri 4 Kota Tangerang'),
                $this->participantRow('Citra Ayuningtyas', '0044400202', 'P', 'citra.tangerang@isoc.id', 'SMK Negeri 4 Kota Tangerang'),
                $this->participantRow('Dewi Anggraini', '0044400203', 'P', 'dewi.tangerang@isoc.id', 'SMK Negeri 4 Kota Tangerang'),
            ],
            'tutor_rows' => [
                ['name' => 'Tutor Tangerang', 'phone' => '081300000402', 'email' => 'tutor.tangerang@isoc.id', 'institution' => 'Relawan TIK Kota Tangerang', 'notes' => null],
            ],
            'local_admin_notes' => 'Pelatihan DSC SMK Negeri 4 Kota Tangerang.',
        ]);

        // Approve Event (akun peserta & tutor dibuat) lalu Publish + Termin-1.
        app(LearningEventProvisioner::class)->provisionAccounts($event);
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

        // Tutor menyelesaikan ToT.
        foreach ($event->tutors as $tutor) {
            TotAssessment::query()->updateOrCreate(
                ['learning_event_id' => $event->id, 'tutor_id' => $tutor->id],
                ['score' => 100, 'completed_at' => now()->subDays(15), 'assessed_by' => $tutor->user_id, 'notes' => 'Lulus pelatihan awal tutor melalui LMS ToT.'],
            );
        }

        // Peserta menyelesaikan pre-test, semua kuis modul, post-test, dan link s.id.
        foreach ($event->participants as $index => $participant) {
            foreach ($event->assessments as $assessment) {
                $questions = collect(is_array($assessment->questions) ? $assessment->questions : json_decode((string) $assessment->questions, true) ?? [])->values();
                $answers = $questions->map(fn (array $question) => collect($question['options'] ?? [])->values()->search(fn ($option) => (bool) ($option['is_correct'] ?? false)))->all();
                // Variasi skor: peserta kedua salah satu soal di pre-test.
                $wrongOnPre = $index === 1 && $assessment->type === 'pre';
                $correct = max(0, $questions->count() - ($wrongOnPre ? 1 : 0));

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
                ['sid_url' => 'https://s.id/dsc-smkn4tgr-' . $participant->id, 'notes' => 'Microsite kampanye keamanan digital.', 'status' => 'reviewed'],
            );

            Certificate::query()->firstOrCreate(
                ['learning_event_id' => $event->id, 'participant_id' => $participant->id],
                [
                    'certificate_template_id' => $event->certificate_template_id,
                    'number' => 'DSC/' . now()->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $participant->id, 5, '0', STR_PAD_LEFT),
                    'status' => 'pending',
                    'eligibility_status' => 'pending',
                ],
            );
        }

        // Bukti dukung kegiatan sudah diunggah dan disetujui Pusat.
        $tutorUserId = $event->tutors->first()?->user_id ?? $admin->id;
        $evidence = [
            ['type' => 'absensi_basah', 'session_index' => 1, 'label' => 'ABSENSI BASAH SESI 1', 'by' => $admin->id],
            ['type' => 'foto_sesi', 'session_index' => 1, 'label' => 'FOTO KEGIATAN SESI 1', 'by' => $tutorUserId],
            ['type' => 'video_slogan', 'link' => 'https://youtu.be/dQw4w9WgXcQ', 'by' => $tutorUserId],
            ['type' => 'praktik_microsite', 'label' => 'PRAKTIK MICROSITE s.id', 'link' => 'https://s.id/dsc-smkn4tgr', 'by' => $admin->id],
        ];

        foreach ($evidence as $item) {
            Evidence::query()->create([
                'learning_event_id' => $event->id,
                'school_id' => $school->id,
                'type' => $item['type'],
                'session_index' => $item['session_index'] ?? null,
                'file_path' => isset($item['label']) ? $this->placeholderImage($item['type'] . '-' . $event->id, $item['label']) : null,
                'link' => $item['link'] ?? null,
                'status' => 'approved',
                'uploaded_by' => $item['by'],
                'verified_by' => $superAdmin->id,
                'verified_at' => now()->subDays(3),
                'review_notes' => 'Bukti sesuai.',
            ]);
        }

        // Laporan final dikirim daerah; menunggu Approve Laporan + Termin-2.
        $event->update([
            'workflow_status' => 'final_report_submitted',
            'final_report_status' => 'submitted',
            'final_report_submitted_at' => now()->subDay(),
            'final_report_notes' => null,
            'local_updated_at' => now()->subDay(),
            'local_update_summary' => 'Laporan kegiatan final dikirim ke Admin RTIK Pusat.',
        ]);
    }

    private function createEvent(User $admin, ModuleTemplate $materi, School $school, array $attributes): LearningEvent
    {
        $event = LearningEvent::query()->create(LearningEventResource::normalizeEventScheduleData([
            'created_by' => $admin->id,
            'event_type' => 'offline',
            'audience_type' => 'school',
            'school_id' => $school->id,
            'module_template_id' => $materi->id,
            'certificate_template_id' => CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->value('id'),
            'target_participants' => 100,
            'target_tutors' => 3,
            'workflow_status' => 'draft',
            'publish_approval_status' => 'draft',
            'status' => 'draft',
            'registration_open' => false,
            'is_published' => false,
            'rundown_items' => LearningEventResource::defaultRundownItems(),
            'budget_items' => [
                ['category' => 'konsumsi', 'description' => 'Snack dan makan siang peserta', 'quantity' => 100, 'unit' => 'paket', 'unit_price' => 35000, 'amount' => 3500000, 'vendor' => 'Katering lokal', 'receipt_number' => null, 'notes' => null],
                ['category' => 'banner_publikasi', 'description' => 'Banner kegiatan', 'quantity' => 2, 'unit' => 'pcs', 'unit_price' => 250000, 'amount' => 500000, 'vendor' => 'Percetakan lokal', 'receipt_number' => null, 'notes' => null],
            ],
            ...$attributes,
        ]));

        $event->partners()->sync(
            Partner::query()
                ->whereIn('name', ['ISOC Indonesia Jakarta Chapter', 'Kementerian Komunikasi dan Digital', 'Relawan TIK Indonesia', 'APJII'])
                ->pluck('id')
                ->all(),
        );

        app(ModuleTemplateApplier::class)->applyToEvent($event);
        app(LearningEventProvisioner::class)->provisionPaymentTerms($event);

        return $event->refresh();
    }

    private function participantRow(string $name, string $nisn, string $gender, string $email, string $school): array
    {
        return [
            'name' => $name,
            'nis' => $nisn,
            'grade' => 'XI',
            'organization' => $school,
            'position' => 'Siswa',
            'gender' => $gender,
            'birth_date' => null,
            'phone' => '0812' . substr($nisn, -6),
            'email' => $email,
        ];
    }

    /** @param array{0: string, 1: string, 2: string} $region nama provinsi, kota/kabupaten, kecamatan */
    private function school(string $name, string $type, string $npsn, array $region, string $address, string $pic, string $picPhone): School
    {
        [$provinceName, $cityName, $districtName] = $region;
        $provinceCode = $this->codeFor(IndonesiaRegion::provinces(), $provinceName);
        $cityCode = $this->codeFor(IndonesiaRegion::regencies($provinceCode), $cityName);
        $districtCode = $this->codeFor(IndonesiaRegion::districts($cityCode), $districtName);
        $villages = IndonesiaRegion::villages($districtCode);
        $villageCode = array_key_first($villages);

        return School::query()->firstOrCreate(['name' => $name], [
            'npsn' => $npsn,
            'type' => $type,
            'province_code' => $provinceCode,
            'province' => IndonesiaRegion::provinces()[$provinceCode] ?? $provinceName,
            'city_code' => $cityCode,
            'city' => IndonesiaRegion::regencies($provinceCode)[$cityCode] ?? $cityName,
            'district_code' => $districtCode,
            'district' => IndonesiaRegion::districts($cityCode)[$districtCode] ?? $districtName,
            'village_code' => $villageCode,
            'village' => $villages[$villageCode] ?? null,
            'address' => $address,
            'maps_url' => 'https://maps.google.com/?q=' . urlencode($name),
            'pic_name' => $pic,
            'pic_phone' => $picPhone,
            'participant_target' => 100,
            'status' => 'active',
        ]);
    }

    private function codeFor(array $options, string $name): ?string
    {
        $code = array_search(strtoupper($name), array_map('strtoupper', $options), true);

        return $code === false ? array_key_first($options) : (string) $code;
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
