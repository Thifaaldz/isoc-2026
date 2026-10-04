<?php

namespace App\Support;

use App\Filament\Pages\EventAttendanceCode;
use App\Filament\Pages\FinalReport;
use App\Filament\Pages\ParticipantRecap;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\LearningEventResource;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Services\SystemProofGenerator;
use Illuminate\Support\Facades\DB;

/** Ringkasan event dalam bahasa sederhana untuk tampilan Admin RTIK Daerah (kartu daftar event & halaman detail). */
class EventOverview
{
    private const APPROVED_STATUSES = ['verified_term_1', 'tot_in_progress', 'tot_completed', 'field_training_scheduled', 'field_training_completed', 'evidence_submitted', 'verified_term_2', 'final_report_submitted', 'closed'];

    /** Cache per objek agar query ringkasan tidak diulang. */
    private array $memo = [];

    public function __construct(public readonly LearningEvent $event)
    {
        $event->loadMissing(['school', 'payments', 'tutors.user', 'meetings', 'orderedPartners']);
    }

    /** @return array{label: string, color: string} color: success|warning|danger|info|gray */
    public function status(): array
    {
        $event = $this->event;

        return match (true) {
            in_array($event->workflow_status, ['cancelled', 'rejected'], true) => ['label' => 'Dibatalkan', 'color' => 'danger'],
            $event->workflow_status === 'needs_revision' => ['label' => 'Perlu revisi', 'color' => 'danger'],
            in_array($event->workflow_status, ['draft', 'submitted'], true) => ['label' => $event->workflow_status === 'draft' ? 'Draft' : 'Menunggu persetujuan Pusat', 'color' => 'gray'],
            $this->paymentStatus(2) === 'eligible' || $event->workflow_status === 'closed' => ['label' => 'Selesai · Termin-2 cair', 'color' => 'success'],
            $event->final_report_status === 'submitted' => ['label' => 'Laporan menunggu approval', 'color' => 'warning'],
            $event->final_report_status === 'revision' => ['label' => 'Laporan perlu revisi', 'color' => 'danger'],
            $this->isFinished() => ['label' => 'Selesai · siapkan laporan', 'color' => 'warning'],
            $this->isToday() => ['label' => 'Berlangsung hari ini', 'color' => 'info'],
            (bool) $event->is_published => ['label' => 'Pendaftaran dibuka', 'color' => 'success'],
            default => ['label' => 'Disetujui', 'color' => 'info'],
        };
    }

    public function isToday(): bool
    {
        return (bool) $this->event->starts_at?->isToday();
    }

    public function isFinished(): bool
    {
        return (bool) $this->event->ends_at?->isPast() && ! $this->isToday();
    }

    /** "H-76", "Hari ini", atau "Selesai 3 hari lalu". */
    public function countdown(): ?string
    {
        $start = $this->event->starts_at;

        if (! $start) {
            return null;
        }

        if ($this->isToday()) {
            return 'Hari ini';
        }

        $days = (int) now()->startOfDay()->diffInDays($start->copy()->startOfDay(), false);

        return $days > 0 ? "H-{$days}" : 'Selesai ' . abs($days) . ' hari lalu';
    }

    public function schedule(): string
    {
        $event = $this->event;

        return $event->starts_at
            ? $event->starts_at->translatedFormat('l, d F Y') . ' · ' . $event->starts_at->format('H.i') . ($event->ends_at ? ' - ' . $event->ends_at->format('H.i') : '') . ' WIB'
            : 'Jadwal belum ditentukan';
    }

    public function paymentStatus(int $term): ?string
    {
        return $this->event->payments->firstWhere('term', $term)?->status;
    }

    /** Tahapan event dari persetujuan sampai Termin-2. @return array<int, array{title: string, hint: string, state: string}> state: done|current|todo */
    public function steps(): array
    {
        $event = $this->event;
        $approved = in_array($event->workflow_status, self::APPROVED_STATUSES, true);
        $term1 = $this->paymentStatus(1) === 'eligible';
        // Laporan yang sudah dikirim berarti kegiatan sudah dilaksanakan.
        $held = $this->isFinished() || in_array($event->final_report_status, ['submitted', 'revision', 'approved'], true);
        $report = $event->final_report_status;
        $term2 = $this->paymentStatus(2) === 'eligible';

        $steps = [
            ['title' => 'Disetujui Pusat', 'hint' => $approved ? 'Event disetujui RTIK Pusat' : 'Menunggu persetujuan', 'done' => $approved],
            ['title' => 'Termin-1 & pendaftaran', 'hint' => $term1 ? 'Termin-1 cair, pendaftaran dibuka' : 'Menunggu publish & Termin-1', 'done' => $term1 && (bool) $event->is_published],
            ['title' => 'Pelaksanaan', 'hint' => $this->isToday() ? 'Berlangsung hari ini' : ($held ? 'Sudah dilaksanakan' : $event->starts_at?->translatedFormat('d M Y') ?? '-'), 'done' => $held],
            ['title' => 'Laporan final', 'hint' => match ($report) { 'approved' => 'Disetujui Pusat', 'submitted' => 'Menunggu approval', 'revision' => 'Perlu revisi', default => 'Belum dikirim' }, 'done' => $report === 'approved'],
            ['title' => 'Termin-2', 'hint' => $term2 ? 'Termin-2 cair' : 'Setelah laporan disetujui', 'done' => $term2],
        ];

        $currentFound = false;

        return array_map(function (array $step) use (&$currentFound): array {
            $state = $step['done'] ? 'done' : ($currentFound ? 'todo' : 'current');
            $currentFound = $currentFound || $state === 'current';

            return ['title' => $step['title'], 'hint' => $step['hint'], 'state' => $state];
        }, $steps);
    }

    /** Angka utama peserta & tutor. */
    public function stats(): array
    {
        return $this->memo['stats'] ??= $this->computeStats();
    }

    private function computeStats(): array
    {
        $event = $this->event;
        $participantIds = DB::table('learning_event_participant')->where('learning_event_id', $event->id)->pluck('participant_id');
        $attempted = fn (string $type) => DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->where('assessments.learning_event_id', $event->id)
            ->where('assessments.type', $type)
            ->distinct()
            ->pluck('assessment_attempts.participant_id');
        $pre = $attempted('pre');
        $post = $attempted('post');
        $complete = DB::table('participants')
            ->whereIn('id', $participantIds->intersect($pre)->intersect($post))
            ->where('joined_wag', true)
            ->where('followed_instagram', true)
            ->count();

        return [
            'participants' => $participantIds->count(),
            'target' => (int) ($event->target_participants ?: 0),
            'attended' => DB::table('attendances')->where('learning_event_id', $event->id)->where('status', 'hadir')->distinct()->count('participant_id'),
            'pre' => $participantIds->intersect($pre)->count(),
            'post' => $participantIds->intersect($post)->count(),
            'complete' => $complete,
            'certificates' => DB::table('certificates')->where('learning_event_id', $event->id)->where('status', 'issued')->count(),
            'tutors' => $event->tutors->count(),
            'tutors_target' => (int) ($event->target_tutors ?: 0),
            'tutors_tot' => $event->tutors->where('tot_completed', true)->count(),
        ];
    }

    public function registrationUrl(): string
    {
        return route('event.register', $this->event->slug);
    }

    /** Saran langkah berikutnya untuk Admin RTIK Daerah. @return array{title: string, text: string, items: array<int, string>, tone: string} */
    public function nextStep(): array
    {
        $event = $this->event;

        if (in_array($event->workflow_status, ['draft', 'needs_revision'], true)) {
            return ['tone' => 'warning', 'title' => 'Lengkapi lalu ajukan event', 'text' => 'Periksa data event, lalu tekan "Submit Pengajuan" agar disetujui RTIK Pusat.', 'items' => []];
        }

        if ($event->workflow_status === 'submitted') {
            return ['tone' => 'info', 'title' => 'Menunggu persetujuan RTIK Pusat', 'text' => 'Event sedang diperiksa. Anda akan melihat statusnya berubah di sini.', 'items' => []];
        }

        if ($this->paymentStatus(2) === 'eligible') {
            return ['tone' => 'success', 'title' => 'Semua tahap selesai', 'text' => 'Laporan disetujui dan Termin-2 sudah cair. Terima kasih!', 'items' => []];
        }

        if ($event->final_report_status === 'submitted') {
            return ['tone' => 'info', 'title' => 'Laporan sedang diperiksa', 'text' => 'Laporan final sudah dikirim. Tunggu approval RTIK Pusat untuk Termin-2.', 'items' => []];
        }

        if ($this->isFinished() || $event->final_report_status === 'revision') {
            $missing = LearningEventResource::missingFinalReportRequirements($event);

            return $missing === []
                ? ['tone' => 'warning', 'title' => 'Kirim laporan final', 'text' => 'Semua syarat laporan sudah lengkap. Tekan "Submit Laporan Final" untuk mengajukan Termin-2.', 'items' => []]
                : ['tone' => 'warning', 'title' => 'Lengkapi syarat laporan final', 'text' => 'Sebelum laporan bisa dikirim, lengkapi:', 'items' => $missing];
        }

        if ($this->isToday()) {
            return ['tone' => 'info', 'title' => 'Hari pelaksanaan', 'text' => 'Buka Kode Absensi agar peserta bisa presensi, lalu pantau kehadiran di Presensi Kehadiran.', 'items' => []];
        }

        return ['tone' => 'info', 'title' => 'Ajak peserta mendaftar', 'text' => 'Bagikan link pendaftaran ke sekolah. Target ' . ($event->target_participants ?: 0) . ' peserta sebelum ' . ($event->starts_at?->translatedFormat('d F Y') ?? 'hari pelaksanaan') . '.', 'items' => []];
    }

    /** Jumlah bukti dukung per tipe: [type => ['approved' => n, 'pending' => n]]. */
    private function evidenceCounts(): array
    {
        return $this->memo['evidence'] ??= Evidence::query()
            ->where('learning_event_id', $this->event->id)
            ->whereIn('status', ['approved', 'pending'])
            ->selectRaw('type, status, count(*) as total')
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(fn ($rows) => ['approved' => (int) $rows->firstWhere('status', 'approved')?->total, 'pending' => (int) $rows->firstWhere('status', 'pending')?->total])
            ->all();
    }

    /** Item checklist berbasis bukti dukung (bisa diupload dari halaman Laporan Final). */
    private function evidenceItem(string $type, string $label, int $min = 1): array
    {
        $count = $this->evidenceCounts()[$type] ?? ['approved' => 0, 'pending' => 0];
        $unit = isset(Evidence::REQUIRED_PHOTOS[$type]) ? ' foto' : '';

        if ($unit) {
            // Foto bisa berupa link Google Drive yang dianggap memenuhi jumlah minimal.
            $count['approved'] = Evidence::photoCounts($this->event->id, approvedOnly: true)[$type];
        }

        return [
            'key' => $type,
            'label' => $label,
            'done' => $count['approved'] >= $min,
            'detail' => "{$count['approved']}/{$min}{$unit} disetujui" . ($count['pending'] ? " · {$count['pending']} menunggu" : ''),
            'evidence' => $type,
            'link' => null,
        ];
    }

    /**
     * Bukti yang bisa digenerate dari sistem (absensi / microsite) atau diupload manual.
     * Generate sistem langsung memenuhi syarat begitu PDF-nya tersimpan.
     */
    private function systemProofItem(string $kind, string $systemLabel, string $manualLabel, string $systemDetail): array
    {
        $mode = $this->event->proofMode($kind);
        $type = SystemProofGenerator::KINDS[$kind]['type'];

        if ($mode === 'system') {
            $generated = $this->event->proofComplete($kind);

            return [
                'key' => $type, 'label' => $systemLabel, 'mode' => 'system', 'proof_kind' => $kind,
                'done' => $generated,
                'detail' => 'Generate sistem · ' . ($generated ? 'PDF tersimpan' : 'PDF belum dibuat') . " · {$systemDetail}",
                'evidence' => null, 'link' => null,
            ];
        }

        return [...$this->evidenceItem($type, $manualLabel), 'mode' => $mode, 'proof_kind' => $kind];
    }

    private function dataItem(string $key, string $label, bool $done, string $detail, string $link): array
    {
        return ['key' => $key, 'label' => $label, 'done' => $done, 'detail' => $detail, 'evidence' => null, 'link' => $link];
    }

    /**
     * Checklist Termin-2, dicentang otomatis dari data event: foto & video slogan dari bukti dukung yang disetujui,
     * sisanya dari data peserta (absensi, follow IG & WAG, nilai, microsite, ranking, sertifikat).
     *
     * @return array<int, array{key: string, label: string, done: bool, detail: string, evidence: ?string, link: ?string}>
     */
    public function termTwoChecklist(): array
    {
        $stats = $this->stats();
        $id = $this->event->id;
        $microsites = DB::table('microsite_practices')->where('learning_event_id', $id)->whereNotNull('sid_url')->count();
        $social = DB::table('learning_event_participant')
            ->join('participants', 'participants.id', '=', 'learning_event_participant.participant_id')
            ->where('learning_event_participant.learning_event_id', $id)
            ->where('participants.followed_instagram', true)
            ->where('participants.joined_wag', true)
            ->count();
        $recapUrl = ParticipantRecap::getUrl(panel: 'admin');

        return collect(TorEventTemplate::TERM_CHECKLIST[2])
            ->map(fn (string $label, string $key) => match (true) {
                isset(Evidence::REQUIRED_PHOTOS[$key]) => $this->evidenceItem($key, $label, Evidence::REQUIRED_PHOTOS[$key]['min']),
                $key === 'video_slogan' => $this->evidenceItem($key, $label),
                $key === 'daftar_hadir' => $this->dataItem($key, $label, $stats['attended'] > 0, "{$stats['attended']} peserta hadir", AttendanceResource::getUrl()),
                $key === 'follow_ig_wag' => $this->dataItem($key, $label, $social > 0, "{$social}/{$stats['participants']} peserta", $recapUrl),
                $key === 'daftar_nilai' => $this->dataItem($key, $label, $stats['pre'] > 0 && $stats['post'] > 0, "pre {$stats['pre']} · post {$stats['post']} peserta", $recapUrl),
                $key === 'microsite_peserta' => $this->dataItem($key, $label, $microsites > 0, "{$microsites} link microsite", $recapUrl),
                $key === 'ranking_peserta' => $this->dataItem($key, $label, $stats['post'] > 0, $stats['post'] > 0 ? 'dari nilai post-test' : 'menunggu nilai post-test', $recapUrl),
                $key === 'sertifikat_peserta' => $this->dataItem($key, $label, $stats['certificates'] > 0, "{$stats['certificates']} sertifikat terbit", CertificateResource::getUrl()),
                default => $this->dataItem($key, $label, in_array($key, TorEventTemplate::checkedKeys($this->event->budget_items, 2), true), '', $recapUrl),
            })
            ->values()
            ->all();
    }

    /**
     * Syarat laporan final (sama dengan pengecekan "Submit Laporan Final"), lengkap dengan statusnya.
     *
     * @return array<int, array{key: string, label: string, done: bool, detail: string, evidence: ?string, link: ?string}>
     */
    public function finalReportChecklist(): array
    {
        $event = $this->event;
        $stats = $this->stats();
        $hasTest = $event->assessments()->where('type', 'pre')->exists() && $event->assessments()->where('type', 'post')->exists();

        return [
            $this->systemProofItem('attendance', 'Absensi / daftar hadir', 'Absensi basah / daftar hadir', $this->stats()['attended'] . ' peserta absen dengan kode'),
            $this->evidenceItem('video_slogan', 'Video slogan'),
            $this->systemProofItem('microsite', 'Bukti hasil microsite', 'Bukti hasil microsite', DB::table('microsite_practices')->where('learning_event_id', $event->id)->whereNotNull('sid_url')->distinct()->count('participant_id') . ' link microsite peserta'),
            ...collect(Evidence::REQUIRED_PHOTOS)->map(fn (array $photo, string $type) => $this->evidenceItem($type, "Foto {$photo['step']}. {$photo['title']}", $photo['min']))->values()->all(),
            $this->dataItem('registrasi', 'Registrasi peserta', $stats['participants'] > 0, "{$stats['participants']} peserta", ParticipantRecap::getUrl(panel: 'admin')),
            $this->dataItem('tes', 'Pre-test & post-test', $hasTest, "pre {$stats['pre']} · post {$stats['post']} peserta mengerjakan", ParticipantRecap::getUrl(panel: 'admin')),
            $this->dataItem('materi', 'Materi / pertemuan', $event->meetings->isNotEmpty(), $event->meetings->count() . ' modul', LearningEventResource::getUrl('view', ['record' => $event])),
        ];
    }

    /** @return array{label: string, color: string} */
    public function finalReportStatus(): array
    {
        return match ($this->event->final_report_status ?? 'draft') {
            'submitted' => ['label' => 'Menunggu approval Pusat', 'color' => 'warning'],
            'revision' => ['label' => 'Perlu revisi', 'color' => 'danger'],
            'approved' => ['label' => 'Disetujui · Termin-2', 'color' => 'success'],
            default => ['label' => 'Belum dikirim', 'color' => 'gray'],
        };
    }

    public function registrationQr(): ?string
    {
        return QrImage::png($this->registrationUrl(), 8);
    }

    /** Tautan cepat menu Admin RTIK Daerah. @return array<int, array{label: string, icon: string, url: string}> */
    public function quickLinks(): array
    {
        return [
            ['label' => 'Kode Absensi', 'icon' => 'heroicon-o-qr-code', 'url' => EventAttendanceCode::getUrl(['event' => $this->event->id], panel: 'admin')],
            ['label' => 'Presensi Kehadiran', 'icon' => 'heroicon-o-clipboard-document-list', 'url' => AttendanceResource::getUrl()],
            ['label' => 'Peserta Lengkap', 'icon' => 'heroicon-o-user-group', 'url' => ParticipantRecap::getUrl(panel: 'admin')],
            ['label' => 'Laporan Final', 'icon' => 'heroicon-o-document-check', 'url' => FinalReport::getUrl(['event' => $this->event->id], panel: 'admin')],
        ];
    }
}
