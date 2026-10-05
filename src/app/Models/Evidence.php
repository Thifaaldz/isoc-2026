<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $guarded = [];

    public const TYPES = [
        'absensi_basah' => 'Absensi basah',
        'foto_sesi' => 'Foto kegiatan per sesi',
        'follow_ig' => 'Follow IG @isoc.id.jkt',
        'join_wag' => 'Join WAG Mentoring',
        'foto_wajib' => 'Foto wajib',
        'foto_tutor_guru' => 'Foto wajib a - Tutor bersama guru/manajemen sekolah',
        'foto_pembukaan_banner' => 'Foto wajib b - Tutor & 100 peserta dengan banner',
        'foto_tutor_mengajar' => 'Foto wajib c - Tutor memberikan materi',
        'foto_siswa_menyimak' => 'Foto wajib d - Siswa menyimak materi',
        'foto_siswa_bertanya' => 'Foto wajib e - Siswa bertanya / beropini',
        'foto_pemberian_hadiah' => 'Foto wajib f - Pemberian hadiah 10 peserta aktif',
        'foto_tutor_peserta_aktif' => 'Foto wajib g - Tutor bersama 10 peserta teraktif',
        'video_slogan' => 'Video slogan ISOC',
        'praktik_microsite' => 'Praktik microsite s.id',
        'modul_tersampaikan' => 'Bukti 6 modul tersampaikan',
        'rab_final' => 'RAB final',
        'kuitansi_invoice' => 'Kuitansi / invoice',
        'bukti_logistik' => 'Bukti pembelian logistik',
        'laporan_akhir' => 'Laporan akhir',
    ];

    /** Jenis lama yang tidak lagi dipakai untuk upload baru (diganti checklist foto wajib a-g). */
    public const LEGACY_TYPES = ['foto_sesi', 'foto_wajib'];

    /** Foto wajib per lokasi kegiatan sesuai TOR, beserta jumlah minimal foto yang harus dikumpulkan. */
    public const REQUIRED_PHOTOS = [
        'foto_tutor_guru' => [
            'step' => 'a',
            'title' => 'Tutor bersama guru / manajemen sekolah',
            'min' => 1,
            'instruction' => '3 orang Tutor bersama minimal 3 guru/manajemen sekolah dengan atribut lokasi (misalnya papan nama sekolah).',
        ],
        'foto_pembukaan_banner' => [
            'step' => 'b',
            'title' => 'Tutor & 100 peserta dengan banner',
            'min' => 1,
            'instruction' => '3 orang Tutor di awal sesi bersama 100 peserta, diambil dari angle depan sambil memegang banner kegiatan.',
        ],
        'foto_tutor_mengajar' => [
            'step' => 'c',
            'title' => 'Tutor memberikan materi',
            'min' => 3,
            'instruction' => 'Tutor sedang memberikan materi kepada siswa; setiap Tutor difoto (3 foto). Posisi Tutor membelakangi backdrop.',
        ],
        'foto_siswa_menyimak' => [
            'step' => 'd',
            'title' => 'Siswa menyimak materi',
            'min' => 3,
            'instruction' => 'Siswa sedang menyimak materi (3 foto).',
        ],
        'foto_siswa_bertanya' => [
            'step' => 'e',
            'title' => 'Siswa bertanya / beropini',
            'min' => 3,
            'instruction' => 'Siswa sedang menyampaikan pertanyaan atau memberikan opini (3 foto).',
        ],
        'foto_pemberian_hadiah' => [
            'step' => 'f',
            'title' => 'Pemberian hadiah peserta aktif',
            'min' => 1,
            'instruction' => 'Pemberian hadiah kepada 10 peserta aktif.',
        ],
        'foto_tutor_peserta_aktif' => [
            'step' => 'g',
            'title' => 'Tutor bersama 10 peserta teraktif',
            'min' => 2,
            'instruction' => '3 orang Tutor bersama dengan 10 peserta teraktif (2 foto).',
        ],
    ];

    /** @return array<string, string> */
    public static function uploadTypes(): array
    {
        return array_diff_key(self::TYPES, array_flip(self::LEGACY_TYPES));
    }

    /**
     * Jumlah foto wajib per jenis untuk satu event.
     *
     * @return array<string, int>
     */
    public static function photoCounts(?int $eventId, bool $approvedOnly = false): array
    {
        $counts = $eventId
            ? self::query()
                ->where('learning_event_id', $eventId)
                ->whereIn('type', array_keys(self::REQUIRED_PHOTOS))
                ->when($approvedOnly, fn ($query) => $query->where('status', 'approved'))
                ->when(! $approvedOnly, fn ($query) => $query->whereNotIn('status', ['rejected']))
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->all()
            : [];

        // Link Google Drive (tanpa berkas) dianggap berisi seluruh foto minimal jenis tersebut.
        $driveTypes = $eventId
            ? self::query()
                ->where('learning_event_id', $eventId)
                ->whereIn('type', array_keys(self::REQUIRED_PHOTOS))
                ->whereNull('file_path')
                ->whereNotNull('link')
                ->when($approvedOnly, fn ($query) => $query->where('status', 'approved'))
                ->when(! $approvedOnly, fn ($query) => $query->whereNotIn('status', ['rejected']))
                ->distinct()
                ->pluck('type')
                ->flip()
            : collect();

        return collect(self::REQUIRED_PHOTOS)->map(fn (array $photo, string $type) => $driveTypes->has($type)
            ? max((int) ($counts[$type] ?? 0), $photo['min'])
            : (int) ($counts[$type] ?? 0))->all();
    }

    /**
     * Foto wajib yang belum memenuhi jumlah minimal (hanya foto yang sudah disetujui RTIK Pusat).
     * Event lama yang sudah punya "Foto kegiatan per sesi" disetujui tetap dianggap lengkap.
     *
     * @return array<int, string>
     */
    public static function missingRequiredPhotos(int $eventId): array
    {
        $hasLegacyPhoto = self::query()
            ->where('learning_event_id', $eventId)
            ->where('type', 'foto_sesi')
            ->where('status', 'approved')
            ->exists();

        if ($hasLegacyPhoto) {
            return [];
        }

        $counts = self::photoCounts($eventId, approvedOnly: true);

        return collect(self::REQUIRED_PHOTOS)
            ->filter(fn (array $photo, string $type) => $counts[$type] < $photo['min'])
            ->map(fn (array $photo, string $type) => "foto {$photo['step']}. {$photo['title']} ({$counts[$type]}/{$photo['min']})")
            ->values()
            ->all();
    }

    protected function casts(): array { return ['verified_at' => 'datetime']; }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogg', 'ogv', 'mov'];

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /** Jenis pratinjau bukti: image, video, pdf, file (dokumen lain), youtube, link, atau none. */
    public function mediaKind(): string
    {
        if ($this->file_path) {
            $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));

            return match (true) {
                in_array($extension, self::IMAGE_EXTENSIONS, true) => 'image',
                in_array($extension, self::VIDEO_EXTENSIONS, true) => 'video',
                $extension === 'pdf' => 'pdf',
                default => 'file',
            };
        }

        if ($this->link) {
            return $this->youtubeId() ? 'youtube' : 'link';
        }

        return 'none';
    }

    public function youtubeId(): ?string
    {
        return $this->link && preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $this->link, $match)
            ? $match[1]
            : null;
    }
}
