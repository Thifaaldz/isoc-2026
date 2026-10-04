<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Template kegiatan dari TOR Program Literasi Digital "Digital Safety Champions" 2026:
 * susunan acara lapangan (180 menit) dan komponen anggaran per termin.
 */
class TorEventTemplate
{
    public const TITLE_PREFIX = 'Digital Safety Champions - ';

    public const DESCRIPTION = 'Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia.';

    public const DEFAULT_START = '09:00';

    public const DURATION_MINUTES = 180;

    public const DEFAULT_PARTICIPANTS = 100;

    public const DEFAULT_TUTORS = 3;

    /** Mitra kolaborasi pada TOR (bagian 4. Ruang Lingkup Kolaborasi Mitra). */
    /** Peran mitra sesuai TOR (bagian 4), dipakai di laporan kegiatan. */
    public const PARTNER_ROLES = [
        'ISOC Indonesia Jakarta Chapter' => 'Inisiator & pengarah proyek, narasumber utama, penjamin mutu evaluasi, penerbitan e-Certificate',
        'Kementerian Komunikasi dan Digital' => 'BPSDM Komdigi: dukungan kelembagaan',
        '.id Academy' => 'PANDI & jaringan Registrar: materi domain & identitas digital, dukungan ToT, praktik microsite s.id',
        'PANDI' => 'Materi domain & identitas digital, dukungan ToT, praktik microsite s.id',
        'Relawan TIK Indonesia' => 'Pelaksana kegiatan lokal, narahubung sekolah, mentor & fasilitator, logistik lapangan',
        'Universitas Esa Unggul' => 'Kurikulum akademik, instrumen evaluasi/kuis terstandar, fasilitasi ToT',
    ];

    public const PARTNERS = [
        'ISOC Indonesia Jakarta Chapter',
        'Kementerian Komunikasi dan Digital',
        '.id Academy',
        'Relawan TIK Indonesia',
        'Universitas Esa Unggul',
    ];

    /** [durasi menit, sesi, PIC, format & deskripsi]; null pada sesi = slot Materi 1/2 dari modul yang dipilih. */
    private const RUNDOWN = [
        [10, 'Registrasi & Ice Breaking', 'Tim Fasilitator Relawan TIK', 'Presensi kehadiran dan kuis interaktif singkat (Kahoot!/Mentimeter)'],
        [5, 'Pembukaan Resmi', 'Panitia Acara', 'Lagu Indonesia Raya dan doa pembuka'],
        [10, 'Keamanan Siber', 'Kampus Digital Universitas Esa Unggul', 'Video Ajar'],
        [5, 'Sambutan Ketua / Pengurus ISOC Indonesia', 'Ketua / Pengurus ISOC Indonesia', 'Pembukaan resmi program Digital Safety Champions dan komitmen kolaborasi'],
        [5, 'Sambutan Kepala Sekolah', 'Kepala Sekolah / Pihak Sekolah', 'Ucapan selamat datang dan komitmen dukungan pihak sekolah'],
        [5, 'Sambutan Pendiri Masyarakat Internet Indonesia', 'Pendiri ISOC Indonesia', 'Refleksi visi internet terpercaya, aman, dan inklusif bagi generasi muda'],
        [10, 'Pre-test', 'Tim Evaluasi', 'Asesmen pemahaman awal peserta via formulir daring'],
        [30, null, 'Tim Pelatih ToT / ISOC', 'Modul ajar kepada siswa yang dipilih untuk sesi Materi 1'],
        [30, null, 'Tim Pelatih ToT / ISOC', 'Modul ajar kepada siswa yang dipilih untuk sesi Materi 2'],
        [30, 'Praktik Fitur Microsite s.id', 'PANDI', 'Edukasi pemanfaatan tautan aman dan praktik pembuatan microsite s.id untuk profil/kampanye digital'],
        [20, 'Simulasi Kasus, Diskusi & Tanya Jawab (Open Mic)', 'Fasilitator & Siswa', 'Bedah kasus nyata penipuan digital, simulasi peran (role-play), tanya-jawab interaktif, dan refleksi siswa'],
        [10, 'Post-test', 'Tim Evaluasi', 'Asesmen capaian akhir pelatihan via formulir daring'],
        [10, 'Penutupan & Pembentukan Komunitas', 'Panitia & Relawan TIK', 'Sesi foto bersama dan pembuatan WhatsApp Group Mentoring (Digital Safety Champions)'],
    ];

    /** @return array<int, string> ID mitra TOR yang aktif, sesuai urutan di TOR. */
    public static function partnerIds(): array
    {
        $ids = \App\Models\Partner::query()
            ->whereIn('name', self::PARTNERS)
            ->where('status', 'active')
            ->pluck('id', 'name');

        return collect(self::PARTNERS)->map(fn (string $name) => $ids[$name] ?? null)->filter()->map(fn ($id) => (string) $id)->values()->all();
    }

    public static function title(?string $location): string
    {
        $location = trim((string) $location);

        // Nama wilayah dari API berhuruf kapital semua ("KOTA BEKASI") -> "Kota Bekasi".
        if ($location !== '' && mb_strtoupper($location) === $location) {
            $location = mb_convert_case(mb_strtolower($location), MB_CASE_TITLE);
        }

        return self::TITLE_PREFIX . $location;
    }

    /**
     * Rundown TOR mulai dari jam yang dipilih; slot Materi 1/2 memakai judul modul yang dibawakan.
     *
     * @param  array<int, string>  $moduleTitles
     * @return array<int, array<string, string>>
     */
    public static function rundown(?string $startTime = null, array $moduleTitles = []): array
    {
        $current = Carbon::createFromFormat('H:i', $startTime ?: self::DEFAULT_START);
        $moduleTitles = array_values($moduleTitles);
        $materi = 0;

        return array_map(function (array $row) use (&$current, &$materi, $moduleTitles): array {
            [$minutes, $activity, $pic, $notes] = $row;

            if ($activity === null) {
                // Judul modul "Modul 1: Kenali Online Scam" -> "Materi 1: Kenali Online Scam" (tanpa awalan ganda).
                $moduleTitle = preg_replace('/^Modul\s+\d+\s*[:\-]\s*/i', '', (string) ($moduleTitles[$materi] ?? ''));
                $activity = 'Materi ' . ($materi + 1) . ': ' . ($moduleTitle !== '' ? $moduleTitle : 'Pilih modul ajar kepada siswa');
                $materi++;
            }

            $start = $current->format('H:i');
            $current = $current->copy()->addMinutes($minutes);

            return [
                'start_time' => $start,
                'end_time' => $current->format('H:i'),
                'activity' => $activity,
                'pic' => $pic,
                'notes' => $notes,
            ];
        }, self::RUNDOWN);
    }

    /** Keperluan anggaran per termin sesuai TOR bagian 8 (Anggaran); hanya checklist, tanpa nominal. */
    public const TERM_CHECKLIST = [
        1 => [
            'banner' => 'Pengadaan 2 buah banner kegiatan',
            'snack' => 'Pemesanan 125 snack',
            'kebersihan' => 'Dana kebersihan sekolah',
        ],
        // Termin-2: kelengkapan laporan; key foto & video = tipe Evidence, sisanya dihitung dari data peserta.
        2 => [
            'foto_tutor_guru' => 'Foto a. Tutor bersama guru / manajemen sekolah',
            'foto_pembukaan_banner' => 'Foto b. Tutor & 100 peserta dengan banner',
            'foto_tutor_mengajar' => 'Foto c. Tutor memberikan materi',
            'foto_siswa_menyimak' => 'Foto d. Siswa menyimak materi',
            'foto_siswa_bertanya' => 'Foto e. Siswa bertanya / beropini',
            'foto_pemberian_hadiah' => 'Foto f. Pemberian hadiah peserta aktif',
            'foto_tutor_peserta_aktif' => 'Foto g. Tutor bersama 10 peserta teraktif',
            'video_slogan' => 'Video slogan',
            'daftar_hadir' => 'Daftar hadir peserta',
            'follow_ig_wag' => 'List peserta yang sudah follow IG ISOC dan join grup WhatsApp',
            'daftar_nilai' => 'Daftar nilai pre-test dan post-test',
            'microsite_peserta' => 'Link microsite peserta',
            'ranking_peserta' => 'Ranking peserta',
            'sertifikat_peserta' => 'List peserta yang sudah di-generate sertifikatnya',
        ],
    ];

    public const TERM_NOTES = [
        1 => 'Diberikan sebelum kegiatan.',
        2 => 'Diberikan setelah semua Bukti Dukung setiap lokasi dipenuhi dan lokasi dinyatakan lengkap administrasi.',
    ];

    /**
     * Item checklist termin yang disimpan di kolom budget_items.
     *
     * @param  array<int, string>  $checkedKeys
     * @return array<int, array{key: string, term: int, description: string, done: bool}>
     */
    public static function termChecklist(array $checkedKeys = []): array
    {
        $items = [];

        foreach (self::TERM_CHECKLIST as $term => $list) {
            foreach ($list as $key => $description) {
                $items[] = ['key' => $key, 'term' => $term, 'description' => $description, 'done' => in_array($key, $checkedKeys, true)];
            }
        }

        return $items;
    }

    /**
     * Key item yang sudah dicentang untuk satu termin (data RAB lama tanpa "done" dianggap belum).
     *
     * @return array<int, string>
     */
    public static function checkedKeys(?array $items, int $term): array
    {
        return collect($items ?? [])
            ->filter(fn ($item) => is_array($item) && (int) ($item['term'] ?? 0) === $term && ! empty($item['done']))
            ->pluck('key')
            ->filter(fn ($key) => isset(self::TERM_CHECKLIST[$term][$key]))
            ->values()
            ->all();
    }
}
