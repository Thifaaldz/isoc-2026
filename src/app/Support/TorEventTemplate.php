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
        return self::TITLE_PREFIX . trim((string) $location);
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
                $activity = 'Materi ' . ($materi + 1) . ': ' . ($moduleTitles[$materi] ?? 'Pilih modul ajar kepada siswa');
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

    /**
     * Komponen anggaran sesuai TOR. Harga satuan tidak diatur TOR, jadi diisi oleh admin.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function budgetItems(int $participants = self::DEFAULT_PARTICIPANTS, int $tutors = self::DEFAULT_TUTORS): array
    {
        $item = fn (string $key, int $term, string $category, string $description, int $quantity, string $unit, string $notes) => [
            'key' => $key,
            'term' => $term,
            'category' => $category,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => 0,
            'amount' => 0,
            'vendor' => null,
            'receipt_number' => null,
            'notes' => $notes,
        ];

        return [
            $item('banner', 1, 'banner_publikasi', 'Pengadaan banner kegiatan', 2, 'pcs', 'Termin-1, diberikan sebelum kegiatan.'),
            $item('snack', 1, 'konsumsi', 'Pemesanan snack peserta dan tutor', $participants + $tutors, 'paket', 'Termin-1, diberikan sebelum kegiatan.'),
            $item('kebersihan', 1, 'kebersihan', 'Dana kebersihan sekolah', 1, 'paket', 'Termin-1, diberikan sebelum kegiatan.'),
            $item('honor_tutor', 2, 'honor_narasumber', 'Honor tutor', $tutors, 'orang', 'Termin-2, setelah semua bukti dukung lokasi lengkap.'),
            $item('administrasi', 2, 'administrasi_operasional', 'Administrasi dan operasional RTIK Pusat', 1, 'paket', 'Termin-2, setelah semua bukti dukung lokasi lengkap.'),
        ];
    }

    /**
     * Sesuaikan qty item yang bergantung pada jumlah peserta/tutor tanpa mengubah harga yang sudah diisi.
     *
     * @param  array<int|string, array<string, mixed>>  $items
     * @return array<int|string, array<string, mixed>>
     */
    public static function syncBudgetQuantities(array $items, int $participants, int $tutors): array
    {
        return array_map(function (array $item) use ($participants, $tutors): array {
            $quantity = match ($item['key'] ?? null) {
                'snack' => $participants + $tutors,
                'honor_tutor' => $tutors,
                default => null,
            };

            if ($quantity !== null) {
                $item['quantity'] = $quantity;
                $item['amount'] = $quantity * (float) ($item['unit_price'] ?? 0);
            }

            return $item;
        }, $items);
    }
}
