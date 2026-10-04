<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Konten halaman utama (mengikuti https://isoc.id/) yang bisa diubah Admin RTIK Pusat
 * lewat menu Pengaturan > Halaman Utama. Nilai yang belum diisi memakai default di bawah.
 */
class HomePageContent
{
    public const KEY = 'home_page';

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'Internet Is For Everyone',
                'headline_before' => 'Kami percaya bahwa',
                'headline_highlight' => 'internet',
                'headline_after' => 'memiliki kekuatan untuk mengubah hidup masyarakat.',
                'description' => 'Setiap orang dapat mengakses, mempercayai, dan menggunakannya dengan aman. Internet Society (ISOC) Chapter Indonesia berkomitmen penuh mendukung terwujudnya ekosistem internet yang berkelanjutan, inklusif, dan aman.',
                'image' => null,
                'primary_button_text' => 'Lihat Event',
                'primary_button_url' => '/events',
                'secondary_button_text' => 'Portal Peserta',
                'secondary_button_url' => '/login',
            ],
            'about' => [
                'eyebrow' => 'Siapa Kami',
                'title' => 'About ISOC Chapter Indonesia',
                'description' => 'ISOC Chapter Indonesia adalah bagian dari organisasi global Internet Society. Kami hadir untuk mendukung jaringan internet yang terbuka, aman, dan mudah diakses, serta berkomitmen membangun ekosistem digital yang inklusif.',
                'vision' => 'Our Vision: The Internet Is for Everyone.',
                'image' => null,
            ],
            'mission' => [
                'eyebrow' => 'Misi Kami',
                'title' => 'Pilar Strategis ISOC Jakarta',
                'pillars' => [
                    ['icon' => 'school', 'title' => 'Pendidikan & Pengajaran', 'description' => 'Fokus pada Digital Literacy dan Community-centered Connectivity melalui workshop dan modul edukasi.'],
                    ['icon' => 'biotech', 'title' => 'Penelitian', 'description' => 'Menghasilkan Research & Whitepapers yang kredibel serta mendorong Collaboration lintas disiplin.'],
                    ['icon' => 'volunteer_activism', 'title' => 'Pengabdian', 'description' => 'Melayani komunitas melalui Webinar Series rutin dan ID-SIG (Indonesia School on Internet Governance).'],
                ],
            ],
            'board' => [
                'eyebrow' => 'Pemimpin Kami',
                'title' => 'Jajaran Pengurus',
                'groups' => [
                    ['name' => 'Dewan Pengurus Harian', 'members' => [
                        ['name' => 'Tinuk Andriyanti Asianto', 'role' => 'Ketua Umum', 'photo' => null],
                        ['name' => 'Diah Aryani', 'role' => 'Bendahara', 'photo' => null],
                        ['name' => 'Bayu Sulistiyanto Ipung Sutejo', 'role' => 'Program Director', 'photo' => null],
                        ['name' => 'Agnes Pujianti', 'role' => 'Sekretariat', 'photo' => null],
                        ['name' => 'Wahyu Nugroho', 'role' => 'Sekretariat', 'photo' => null],
                    ]],
                    ['name' => 'Dewan Pengawas', 'members' => [
                        ['name' => 'John Sihar Simanjuntak', 'role' => 'Ketua', 'photo' => null],
                        ['name' => 'Basuki Suhardiman', 'role' => 'Anggota', 'photo' => null],
                        ['name' => 'Intan Rahayu', 'role' => 'Anggota', 'photo' => null],
                    ]],
                    ['name' => 'Dewan Riset', 'members' => [
                        ['name' => 'Gerry Firmansyah', 'role' => 'Ketua', 'photo' => null],
                        ['name' => 'Bambang Jokonowo', 'role' => 'Anggota', 'photo' => null],
                        ['name' => 'Agung Mulyo Widodo', 'role' => 'Anggota', 'photo' => null],
                    ]],
                ],
            ],
            'programs' => [
                'eyebrow' => 'Program Nasional',
                'title' => 'Inisiatif Strategis & Modul Pembelajaran',
                'description' => 'Kami mengimplementasikan inisiatif global ISOC ke dalam konteks lokal melalui pilar pendidikan, penelitian, dan pengabdian.',
                'items' => [
                    ['icon' => 'auto_stories', 'title' => 'Digital Literacy & Community Connectivity', 'description' => 'Penyediaan akses internet di wilayah pelosok dan peningkatan literasi bagi pengguna baru melalui modul Pendidikan & Pengajaran yang terstruktur.', 'tags' => ['Community Networks', 'Infrastruktur', 'Workshop'], 'featured' => false, 'label' => null],
                    ['icon' => 'science', 'title' => 'Research & Whitepapers', 'description' => 'Publikasi riset mendalam serta kolaborasi strategis mengenai perkembangan teknologi dan kebijakan internet di Indonesia.', 'tags' => [], 'featured' => false, 'label' => null],
                    ['icon' => 'live_tv', 'title' => 'Webinar Series', 'description' => 'Diskusi rutin mingguan sebagai wujud pengabdian bersama pakar teknologi mengenai tren kebijakan digital terkini.', 'tags' => [], 'featured' => false, 'label' => null],
                    ['icon' => 'school', 'title' => 'ID-SIG (Indonesia School on Internet Governance)', 'description' => 'Sekolah intensif tahunan bagi profesional dan akademisi untuk mendalami tata kelola internet global.', 'tags' => [], 'featured' => true, 'label' => 'Pengabdian Eksklusif'],
                ],
            ],
            'events' => [
                'enabled' => true,
                'eyebrow' => 'Event',
                'title' => 'Event Mendatang',
                'description' => 'Ikuti pelatihan Digital Safety Champions di lokasi terdekat dan daftar langsung secara online.',
                'limit' => 6,
            ],
            'partners' => [
                'title' => 'Ekosistem & Mitra Kami',
                'global_label' => 'Global Partner',
                'global' => [['name' => 'ISOC Foundation', 'logo' => 'partners/isoc-foundation.png', 'url' => 'https://www.isocfoundation.org']],
                'national_label' => 'National Partners',
                // Logo memakai berkas mitra yang sama dengan menu Mitra Event (storage/app/public/partners).
                'national' => [
                    ['name' => 'BPSDM Komdigi', 'logo' => 'partners/komdigi.png', 'url' => 'https://www.komdigi.go.id'],
                    ['name' => 'Ditjen Akselerasi ID', 'logo' => 'partners/ditjen-infrastruktur-digital.png', 'url' => null],
                    ['name' => 'PANDI', 'logo' => 'partners/pandi.png', 'url' => 'https://pandi.id'],
                    ['name' => 'APJII', 'logo' => 'partners/apjii.png', 'url' => 'https://apjii.or.id'],
                    ['name' => 'RTIK', 'logo' => 'partners/relawan-tik.png', 'url' => 'https://relawantik.or.id'],
                ],
            ],
            'footer' => [
                'description' => 'Organisasi nirlaba yang mendedikasikan diri untuk memastikan Internet tetap terbuka, terhubung secara global, aman, dan tepercaya untuk semua orang.',
                'email' => 'secretariat@isoc.id',
                'instagram_handle' => '@isoc.id.jkt',
                'instagram_url' => 'https://www.instagram.com/isoc.id.jkt/',
                'linkedin_url' => 'https://www.linkedin.com/company/internet-society-chapter-jakarta-ina/',
                'location' => 'Jakarta, Indonesia',
                'copyright' => 'Internet Society (ISOC) Chapter Indonesia. Internet Is For Everyone.',
            ],
        ];
    }

    /** Konten tersimpan digabung dengan default (bagian yang belum diatur memakai default). */
    public static function get(): array
    {
        $stored = Schema::hasTable('site_settings') ? (SiteSetting::getValue(self::KEY) ?? []) : [];

        return collect(self::defaults())
            ->map(fn (array $section, string $key) => array_replace($section, array_filter((array) ($stored[$key] ?? []), fn ($value) => $value !== null)))
            ->all();
    }

    public static function save(array $content): void
    {
        SiteSetting::putValue(self::KEY, $content);
    }

    public static function reset(): void
    {
        SiteSetting::query()->where('key', self::KEY)->delete();
    }

    /** URL gambar yang diupload (disk public), fallback ke public/images (logo bawaan) bila berkas belum ada di storage. */
    public static function image(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return file_exists(public_path('images/' . $path)) ? asset('images/' . $path) : null;
    }
}
