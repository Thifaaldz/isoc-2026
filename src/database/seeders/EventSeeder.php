<?php

namespace Database\Seeders;

use App\Filament\Resources\LearningEventResource;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\ModuleTemplate;
use App\Models\Partner;
use App\Models\School;
use App\Models\User;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 20 lokus Digital Safety Champions per kab/kota. Event baru ditambahkan Admin RTIK Daerah
 * dan sudah disubmit, menunggu "Approve Event" oleh Admin RTIK Pusat (belum approved/publish).
 *
 * Jalankan: php artisan db:seed --class=EventSeeder (aman diulang, event yang sudah ada dilewati).
 */
class EventSeeder extends Seeder
{
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

    /** Urutan mitra mengikuti banner kegiatan. */
    private const MITRA = ['ISOC Indonesia Jakarta Chapter', 'Kementerian Komunikasi dan Digital', '.id Academy', 'Relawan TIK Indonesia', 'APJII', 'Universitas Esa Unggul'];

    public function run(): void
    {
        // Event bawaan migration awal (2026_09_01_000006) tidak dipakai lagi.
        LearningEvent::query()->where('slug', 'digital-safety-champions')->get()->each->delete();

        $admin = User::query()->where('email', 'adm@isoc.id')->firstOrFail();
        $materi = ModuleTemplate::query()->where('name', MateriSeeder::MATERI_SISWA)->firstOrFail();
        $certificateTemplateId = CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->value('id');
        $partnerIds = collect(self::MITRA)
            ->map(fn (string $name) => Partner::query()->where('name', $name)->value('id'))
            ->filter()
            ->values();

        foreach (self::LOKUS as $index => [$kota, $cityCode, $city, $provinceCode, $province]) {
            $slug = 'digital-safety-champions-' . Str::slug($kota);

            if (LearningEvent::query()->where('slug', $slug)->exists()) {
                continue;
            }

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

            // Jadwal sementara: tiap Sabtu mulai 7 November 2026, 2 lokus per minggu.
            $date = now()->setDate(2026, 11, 7)->addWeeks(intdiv($index, 2))->toDateString();

            $event = LearningEvent::query()->create(LearningEventResource::normalizeEventScheduleData([
                'created_by' => $admin->id,
                'title' => "Digital Safety Champions - {$kota}",
                'slug' => $slug,
                'description' => "Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia. Pelatihan 6 modul untuk pelajar di {$kota}, {$provinsi}.",
                'event_type' => 'offline',
                'audience_type' => 'school',
                'school_id' => $school->id,
                'module_template_id' => $materi->id,
                'certificate_template_id' => $certificateTemplateId,
                'starts_at' => "{$date} 08:00:00",
                'ends_at' => "{$date} 12:00:00",
                'target_participants' => 100,
                'target_tutors' => 3,
                'workflow_status' => 'draft',
                'publish_approval_status' => 'draft',
                'status' => 'draft',
                'is_published' => false,
                'registration_open' => false,
                'participant_rows' => [],
                'tutor_rows' => [],
                'budget_items' => [
                    ['category' => 'konsumsi', 'description' => 'Snack dan makan siang peserta', 'quantity' => 100, 'unit' => 'paket', 'unit_price' => 35000, 'amount' => 3500000, 'vendor' => null, 'receipt_number' => null, 'notes' => null],
                    ['category' => 'banner_publikasi', 'description' => 'Banner kegiatan', 'quantity' => 1, 'unit' => 'pcs', 'unit_price' => 300000, 'amount' => 300000, 'vendor' => null, 'receipt_number' => null, 'notes' => null],
                ],
                'local_admin_notes' => "Pengajuan pelatihan Digital Safety Champions di {$kota}, {$provinsi}.",
            ]));

            foreach ($partnerIds as $order => $partnerId) {
                $event->partners()->attach($partnerId, ['sort_order' => $order + 1]);
            }

            app(ModuleTemplateApplier::class)->applyToEvent($event);
            app(LearningEventProvisioner::class)->provisionPaymentTerms($event);

            // Submit Pengajuan: menunggu Approve Event oleh Admin RTIK Pusat.
            $event->update([
                'workflow_status' => 'submitted',
                'local_updated_at' => now(),
                'local_update_summary' => 'Pengajuan event dikirim ke Admin RTIK Pusat.',
            ]);
        }
    }
}
