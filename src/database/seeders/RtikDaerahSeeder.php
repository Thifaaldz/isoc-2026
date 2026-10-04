<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\ModuleTemplate;
use App\Models\School;
use App\Models\TotAssessment;
use App\Models\Tutor;
use App\Models\User;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use App\Support\TorEventTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 20 lokus Digital Safety Champions per kab/kota. Setiap lokus punya:
 *  - Admin RTIK Daerah (Fasilitator): {daerah}@isoc.id, mis. surabaya@isoc.id, Kota Jambi -> jambi@isoc.id.
 *  - 3 tutor: tutor{daerah}1@isoc.id s/d tutor{daerah}3@isoc.id, mis. tutorsurabaya1@isoc.id.
 *  - 1 event dari template TOR milik Admin RTIK Daerah dengan 3 tutor tersebut, langsung disetujui RTIK Pusat tanpa pengajuan:
 *    tutor ditugaskan, event dipublish, Termin-1 eligible dan seluruh checklist Termin-1 tercentang.
 *  - Semua tutor seeder sudah terverifikasi: email terverifikasi dan lulus ToT (nilai 100), sehingga semua menu tutor terbuka.
 * Semua akun memakai password: "password".
 *
 * Jalankan: php artisan db:seed --class=RtikDaerahSeeder (aman diulang).
 */
class RtikDaerahSeeder extends Seeder
{
    public const TUTORS_PER_DAERAH = 3;

    /** [kab/kota, kode kab/kota, nama kab/kota (wilayah), kode provinsi, provinsi] */
    public const LOKUS = [
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

    private const CERTIFICATE_TEMPLATE = 'Template Sertifikat Sena - Landscape';

    public function run(): void
    {
        // Event bawaan migration awal (2026_09_01_000006) tidak dipakai lagi.
        LearningEvent::query()->where('slug', 'digital-safety-champions')->get()->each->delete();

        $materi = ModuleTemplate::query()->where('name', MateriSeeder::MATERI_SISWA)->firstOrFail();
        $materiModuleIds = $materi->learningMeetings()->whereNull('learning_event_id')->orderBy('order')
            ->limit(LearningEvent::MODULES_PER_EVENT)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $certificateTemplateId = CertificateTemplate::query()->where('name', self::CERTIFICATE_TEMPLATE)->value('id');
        $partnerIds = collect(TorEventTemplate::partnerIds());
        $superAdmin = User::query()->where('email', 'su@isoc.id')->firstOrFail();

        foreach (self::LOKUS as $index => [$kota, $cityCode, $city, $provinceCode, $province]) {
            $provinsi = Str::title($province);
            $daerah = Str::slug(preg_replace('/^Kota\s+/i', '', $kota), '');

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

            $admin = User::query()->firstOrCreate(['email' => "{$daerah}@isoc.id"], [
                'name' => "Admin RTIK {$kota}",
                'password' => 'password',
                'role' => UserRole::Admin,
                'school_id' => $school->id,
            ]);

            $tutorIds = [];

            foreach (range(1, self::TUTORS_PER_DAERAH) as $number) {
                $user = User::query()->firstOrCreate(['email' => "tutor{$daerah}{$number}@isoc.id"], [
                    'name' => "Tutor {$kota} {$number}",
                    'password' => 'password',
                    'role' => UserRole::Tutor,
                    'school_id' => $school->id,
                    'phone' => '0813' . str_pad((string) (($index + 1) * 10 + $number), 8, '0', STR_PAD_LEFT),
                ]);

                $tutorIds[] = (string) Tutor::query()->firstOrCreate(['user_id' => $user->id], [
                    'school_id' => $school->id,
                    'institution' => "Relawan TIK {$kota}",
                    'tot_completed' => false,
                ])->id;
            }

            $title = TorEventTemplate::title($kota);
            $slug = Str::slug($title);

            if ($event = LearningEvent::query()->where('slug', $slug)->first()) {
                // Event lama yang masih draft ikut disetujui Termin-1.
                if ($event->workflow_status === 'draft') {
                    $this->approveTerm1($event, $superAdmin);
                }

                continue;
            }

            // Jadwal sementara tiap Sabtu mulai 7 November 2026, 2 lokus per minggu; bisa diubah Admin RTIK Daerah.
            $date = now()->setDate(2026, 11, 7)->addWeeks(intdiv($index, 2))->toDateString();

            $event = LearningEvent::query()->create(LearningEventResource::normalizeEventScheduleData([
                'created_by' => $admin->id,
                'title' => $title,
                'slug' => $slug,
                'description' => TorEventTemplate::DESCRIPTION,
                'event_type' => 'offline',
                'audience_type' => 'school',
                'school_id' => $school->id,
                'module_template_id' => $materi->id,
                'selected_meeting_ids' => $materiModuleIds,
                'certificate_template_id' => $certificateTemplateId,
                'starts_at' => "{$date} " . TorEventTemplate::DEFAULT_START,
                'ends_at' => now()->parse("{$date} " . TorEventTemplate::DEFAULT_START)->addMinutes(TorEventTemplate::DURATION_MINUTES)->toDateTimeString(),
                'target_participants' => TorEventTemplate::DEFAULT_PARTICIPANTS,
                'target_tutors' => TorEventTemplate::DEFAULT_TUTORS,
                'workflow_status' => 'draft',
                'publish_approval_status' => 'draft',
                'status' => 'draft',
                'is_published' => false,
                'registration_open' => false,
                'participant_rows' => [],
                'tutor_rows' => [],
                'selected_tutor_ids' => $tutorIds,
                'rundown_items' => TorEventTemplate::rundown(),
                'budget_items' => TorEventTemplate::termChecklist(),
                'local_updated_at' => now(),
                'local_update_summary' => 'Event baru dibuat oleh Admin RTIK Daerah.',
            ]));

            foreach ($partnerIds as $order => $partnerId) {
                $event->partners()->attach($partnerId, ['sort_order' => $order + 1]);
            }

            app(ModuleTemplateApplier::class)->applyToEvent($event);
            $this->approveTerm1($event, $superAdmin);
        }

        $this->verifyTutors($superAdmin);
    }

    /** Tutor seeder dianggap sudah lulus ToT (seperti menyelesaikan halaman ToT Awal dengan nilai 100). */
    private function verifyTutors(User $superAdmin): void
    {
        $emails = collect(self::LOKUS)
            ->flatMap(fn (array $lokus) => collect(range(1, self::TUTORS_PER_DAERAH))
                ->map(fn (int $number) => 'tutor' . Str::slug(preg_replace('/^Kota\s+/i', '', $lokus[0]), '') . "{$number}@isoc.id"));

        User::query()->whereIn('email', $emails)->whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        Tutor::query()
            ->whereHas('user', fn ($query) => $query->whereIn('email', $emails))
            ->with('learningEvents')
            ->get()
            ->each(function (Tutor $tutor) use ($superAdmin): void {
                $tutor->update(['tot_completed' => true, 'is_cadre' => true]);

                foreach ($tutor->learningEvents as $event) {
                    TotAssessment::query()->firstOrCreate(
                        ['learning_event_id' => $event->id, 'tutor_id' => $tutor->id],
                        ['score' => 100, 'is_perfect' => true, 'completed_at' => now(), 'assessed_by' => $superAdmin->id, 'notes' => 'Lulus ToT (data awal RTIK Pusat).'],
                    );
                }
            });
    }

    /** Setara "Approve Event" lalu "Publish + T1" oleh Admin RTIK Pusat, dengan seluruh checklist Termin-1 tercentang. */
    private function approveTerm1(LearningEvent $event, User $superAdmin): void
    {
        $provisioner = app(LearningEventProvisioner::class);
        $term1Keys = array_keys(TorEventTemplate::TERM_CHECKLIST[1]);

        $event->update([
            'budget_items' => TorEventTemplate::termChecklist(array_merge(
                $term1Keys,
                TorEventTemplate::checkedKeys($event->budget_items, 2),
            )),
        ]);
        $provisioner->provisionAccounts($event);
        $provisioner->provisionPaymentTerms($event);

        $event->update([
            'workflow_status' => 'verified_term_1',
            'status' => 'active',
            'publish_approval_status' => 'published',
            'is_published' => true,
            'registration_open' => true,
            'central_admin_notes' => 'Disetujui RTIK Pusat tanpa pengajuan. Termin-1 eligible.',
            'publish_approved_by' => $superAdmin->id,
            'publish_approved_at' => now(),
            'local_updated_at' => now(),
            'local_update_summary' => 'Event disiapkan dan disetujui RTIK Pusat.',
        ]);

        $payment = $event->payments()->where('term', 1)->first();
        $payment?->update([
            'status' => 'eligible',
            'checklist' => collect($payment->checklist ?? [])->map(fn (array $item) => [...$item, 'done' => true])->all(),
            'notes' => 'Termin-1 disetujui RTIK Pusat.',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);
    }
}
