<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'name',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'ISOC Jakarta',
                'content' => static::defaultContent(),
            ],
        );
    }

    public static function contentWithDefaults(?array $content): array
    {
        return static::mergeDefaults(static::defaultContent(), $content ?? []);
    }

    protected static function mergeDefaults(array $defaults, array $content): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $content)) {
                $content[$key] = $value;

                continue;
            }

            if (is_array($value) && is_array($content[$key]) && ! array_is_list($value) && ! array_is_list($content[$key])) {
                $content[$key] = static::mergeDefaults($value, $content[$key]);
            }
        }

        return $content;
    }

    public static function defaultContent(): array
    {
        $logo = 'https://lh3.googleusercontent.com/aida/AP1WRLsHAwlP9n1CzbHX6Nz3zlYHuh-t0k5usS2kNpE8xl2xy73WTf7Bdn114VhKpQ4lpANy2H0_nqeD8ZvZGceo5gYaSF5LGtkJYxSuAwWtwAf0OVWU9AjMf8NdPiVWj4PTbGDOrwr2L_4qvemY6Mo20YDO1J6tt4tzC8GUygdpPHLOnU59KJESyVamox6Mx2E5T2xmY9KJQkEf7bmnWcvraWmW2698K72z8plNcmqT6gE7Vm25KtstaOn1X88';

        return [
            'site' => [
                'title' => 'ISOC Jakarta - Internet Society Indonesia Jakarta Chapter',
                'logo_url' => $logo,
                'logo_path' => '',
                'footer_description' => 'Organisasi nirlaba yang mendedikasikan diri untuk memastikan Internet tetap terbuka, terhubung secara global, aman, dan tepercaya untuk semua orang.',
                'copyright' => '© 2024 Internet Society (ISOC) Chapter Indonesia. Internet Is For Everyone.',
            ],
            'navigation' => [
                ['label' => 'Tentang Kami', 'url' => '#tentang'],
                ['label' => 'Program', 'url' => '#program'],
                ['label' => 'Pengurus', 'url' => '#pengurus'],
                ['label' => 'Mitra', 'url' => '#mitra'],
            ],
            'hero' => [
                'eyebrow' => 'Internet Is For Everyone',
                'title' => 'Kami percaya bahwa internet memiliki kekuatan untuk mengubah hidup masyarakat.',
                'highlight' => 'internet',
                'description' => 'Setiap orang dapat mengakses, mempercayai, dan menggunakannya dengan aman. Internet Society (ISOC) Chapter Indonesia berkomitmen penuh mendukung terwujudnya ekosistem internet yang berkelanjutan, inklusif, dan aman.',
                'background_url' => 'https://lh3.googleusercontent.com/aida/AP1WRLucPoXijwNs_-kgTLE0aKHxFXOT63LAQARTRmZmgz1lrjxyYvdcFjK9XZecD6FJgXmavt7pXzNbD5GdZzt1dHto2KGnDh5iHW5m9plGhPUtYwQL3gn_xXp4yyxD9496SQUC9DU8hfHIKs1uQW5YE_xPIrh5M5z34pw9ilqhXN1lZX54pm06JMtZpRX112pzkSuLHWyroLNNYuZaEtyJfOC-PD_TUckAxX9-Ffw09uJlXofQPDlTCK7f5tY',
                'background_path' => '',
            ],
            'about' => [
                'eyebrow' => 'SIAPA KAMI',
                'title' => 'About ISOC Chapter Indonesia',
                'description' => 'ISOC Chapter Indonesia adalah bagian dari organisasi global Internet Society. Kami hadir untuk mendukung jaringan internet yang terbuka, aman, dan mudah diakses, serta berkomitmen membangun ekosistem digital yang inklusif.',
                'vision' => 'Our Vision: The Internet Is for Everyone.',
                'image_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCP4wN253J7evQVD-4FOOft_ICZ3KUc3IXhkNXcRIbSsNmIOUBPvJydf95AhRkKIa11vD6mIq6gCGeRICI3G9qGDCoQBKjNZSHAOEUcatR4YI_ik5r9OF8fKRfdhgUx35b4PKkTubGoZafYyyohbLD1kGWc4wIG5_QjicYTD3Hv7E1HuOHE-qLS74NAQwv79G2rnydjCdbNm-PYMragVb7N1hVyoM_0mkdYw0BdVu2FwN66q7_rRV-j4w',
                'image_path' => '',
            ],
            'pillars' => [
                'eyebrow' => 'MISI KAMI',
                'title' => 'Pilar Strategis ISOC Jakarta',
                'items' => [
                    ['icon' => 'school', 'title' => 'Pendidikan & Pengajaran', 'description' => 'Fokus pada Digital Literacy dan Community-centered Connectivity melalui workshop dan modul edukasi.'],
                    ['icon' => 'biotech', 'title' => 'Penelitian', 'description' => 'Menghasilkan Research & Whitepapers yang kredibel serta mendorong Collaboration lintas disiplin.'],
                    ['icon' => 'volunteer_activism', 'title' => 'Pengabdian', 'description' => 'Melayani komunitas melalui Webinar Series rutin dan ID-SIG (Indonesia School on Internet Governance).'],
                ],
            ],
            'management' => [
                'eyebrow' => 'PEMIMPIN KAMI',
                'title' => 'Jajaran Pengurus',
            ],
            'programs' => [
                'eyebrow' => 'PROGRAM NASIONAL',
                'title' => 'Inisiatif Strategis & Modul Pembelajaran',
                'description' => 'Kami mengimplementasikan inisiatif global ISOC ke dalam konteks lokal melalui pilar pendidikan, penelitian, dan pengabdian.',
                'items' => [
                    ['icon' => 'auto_stories', 'badge' => '', 'title' => 'Digital Literacy & Community Connectivity', 'description' => 'Penyediaan akses internet di wilayah pelosok dan peningkatan literasi bagi pengguna baru melalui modul Pendidikan & Pengajaran yang terstruktur.', 'style' => 'wide', 'tags' => ['Community Networks', 'Infrastruktur', 'Workshop']],
                    ['icon' => 'science', 'badge' => '', 'title' => 'Research & Whitepapers', 'description' => 'Publikasi riset mendalam serta kolaborasi strategis mengenai perkembangan teknologi dan kebijakan internet di Indonesia.', 'style' => 'primary', 'tags' => []],
                    ['icon' => 'live_tv', 'badge' => '', 'title' => 'Webinar Series', 'description' => 'Diskusi rutin mingguan sebagai wujud pengabdian bersama pakar teknologi mengenai tren kebijakan digital terkini.', 'style' => 'compact', 'tags' => []],
                    ['icon' => 'school', 'badge' => 'PENGABDIAN EKSKLUSIF', 'title' => 'ID-SIG (Indonesia School on Internet Governance)', 'description' => 'Sekolah intensif tahunan bagi profesional dan akademisi untuk mendalami tata kelola internet global.', 'style' => 'wide-icon', 'tags' => []],
                ],
            ],
            'partners' => [
                'title' => 'Ekosistem & Mitra Kami',
            ],
            'socials' => [
                ['label' => 'Website', 'icon' => 'public', 'url' => '#', 'image_url' => '', 'image_path' => ''],
                ['label' => 'Instagram', 'icon' => 'alternate_email', 'url' => 'https://instagram.com/isoc.id.jkt', 'image_url' => '', 'image_path' => ''],
                ['label' => 'LinkedIn', 'icon' => 'business_center', 'url' => 'https://id.linkedin.com/in/isoc-indonesia-chapter-jakarta-1a62b1398', 'image_url' => '', 'image_path' => ''],
            ],
            'contact' => [
                'title' => 'Kontak Sekretariat',
                'email' => 'secretariat@isoc.id',
                'instagram' => '@isoc.id.jkt',
                'instagram_url' => 'https://instagram.com/isoc.id.jkt',
                'address' => 'Jakarta, Indonesia',
            ],
            'legal_links' => [
                ['label' => 'Kebijakan Privasi', 'url' => '#'],
                ['label' => 'Syarat & Ketentuan', 'url' => '#'],
            ],
        ];
    }
}
