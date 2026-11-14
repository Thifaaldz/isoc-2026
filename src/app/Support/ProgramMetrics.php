<?php

namespace App\Support;

use App\Models\AssessmentAttempt;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\Tutor;
use App\Models\WagGroup;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka progres & KPI seluruh program untuk dashboard Manajemen (tanpa pembatasan role, read-only).
 * Rumus sama dengan KpiOverview (KPI TOR) dan EventOverview (tahapan & statistik per event).
 * Dihitung sekali per request lalu dipakai bersama oleh semua widget manajemen.
 */
class ProgramMetrics
{
    public const TARGET_LOCATIONS = 20;

    public const TARGET_PARTICIPANTS = 2000;

    public const TARGET_WAG = 1400;

    public const STAGES = ['Disetujui Pusat', 'Termin-1 & pendaftaran', 'Pelaksanaan', 'Laporan final', 'Termin-2'];

    private static ?self $instance = null;

    /** @var Collection<int, array> */
    public readonly Collection $rows;

    /** @var array<int, array{approved: int, pending: int, rejected: int, total: int}> */
    private array $evidenceStatus = [];

    private function __construct()
    {
        $this->evidenceStatus = Evidence::query()
            ->selectRaw('learning_event_id, status, COUNT(*) c')
            ->whereNotIn('type', Evidence::LEGACY_TYPES)
            ->groupBy('learning_event_id', 'status')
            ->get()
            ->groupBy('learning_event_id')
            ->map(function ($items) {
                $by = $items->pluck('c', 'status');

                return ['approved' => (int) ($by['approved'] ?? 0), 'pending' => (int) ($by['pending'] ?? 0), 'rejected' => (int) ($by['rejected'] ?? 0), 'total' => (int) $items->sum('c')];
            })
            ->all();

        $this->rows = LearningEvent::query()
            ->with(['school', 'payments', 'tutors', 'meetings', 'orderedPartners', 'assessments'])
            ->whereNotIn('workflow_status', ['cancelled', 'rejected'])
            ->orderBy('starts_at')
            ->get()
            ->map(function (LearningEvent $event): array {
                $overview = new EventOverview($event);
                $steps = $overview->steps();
                $done = collect($steps)->where('state', 'done')->count();
                $checklist = collect($this->checklist($overview));
                $photos = array_keys(Evidence::requiredPhotos($event->id));

                return [
                    'event' => $event,
                    'overview' => $overview,
                    'status' => $overview->status(),
                    'stats' => $overview->stats(),
                    'steps' => $steps,
                    'steps_done' => $done,
                    // Indeks tahap yang sedang berjalan; 5 = semua tahap selesai.
                    'stage' => $done >= count($steps) ? count($steps) : (int) collect($steps)->search(fn ($s) => $s['state'] === 'current'),
                    'progress' => (int) round($done / max(1, count($steps)) * 100),
                    'proof_done' => $checklist->where('done', true)->count(),
                    'proof_total' => $checklist->count(),
                    'checklist' => $checklist->all(),
                    'photos_done' => $checklist->whereIn('key', $photos)->where('done', true)->count(),
                    'photos_total' => count($photos),
                    'video_done' => (bool) $checklist->firstWhere('key', 'video_slogan')['done'] ?? false,
                    'evidence' => $this->evidenceStatus[$event->id] ?? ['approved' => 0, 'pending' => 0, 'rejected' => 0, 'total' => 0],
                    'avg_pre' => $this->eventAverage($event, 'pre'),
                    'avg_post' => $this->eventAverage($event, 'post'),
                ];
            });
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /** Untuk test / Livewire request berikutnya. */
    public static function flush(): void
    {
        self::$instance = null;
    }

    /** KPI TOR program. */
    public function kpi(): array
    {
        $avg = fn (string $type, string $col) => round((float) AssessmentAttempt::query()
            ->whereNotNull('participant_id')
            ->whereHas('assessment', fn ($q) => $q->where('type', $type))
            ->avg($col), 1);
        $tutors = Tutor::query()->whereHas('learningEvents');

        return [
            'locations' => $this->rows->pluck('event.school_id')->filter()->unique()->count(),
            'events' => $this->rows->count(),
            'participants' => Participant::query()->count(),
            'tutors' => (clone $tutors)->count(),
            'tutors_tot' => (clone $tutors)->where('tot_completed', true)->count(),
            'avg_pre' => $avg('pre', 'score'),
            'avg_post' => $avg('post', 'score'),
            'threat' => $avg('post', 'threat_identification'),
            'efficacy' => $avg('post', 'self_efficacy'),
            'wag' => (int) WagGroup::query()->sum('active_members'),
            'reports_approved' => $this->rows->filter(fn ($r) => $r['event']->final_report_status === 'approved')->count(),
            'term2' => $this->rows->filter(fn ($r) => $r['overview']->paymentStatus(2) === 'eligible')->count(),
        ];
    }

    /** Total corong peserta dari semua event. */
    public function funnel(): array
    {
        $sum = fn (string $key) => (int) $this->rows->sum(fn ($r) => $r['stats'][$key] ?? 0);

        return [
            ['label' => 'Terdaftar', 'value' => $sum('participants')],
            ['label' => 'Hadir', 'value' => $sum('attended')],
            ['label' => 'Pre-Test', 'value' => $sum('pre')],
            ['label' => 'Post-Test', 'value' => $sum('post')],
            ['label' => 'Praktik microsite', 'value' => $sum('microsite')],
            ['label' => 'Peserta lengkap', 'value' => $sum('complete')],
            ['label' => 'Sertifikat terbit', 'value' => $sum('certificates')],
        ];
    }

    /** Jumlah event yang sedang berada di tiap tahap (+ "Selesai" untuk semua tahap tuntas). */
    public function stageCounts(): array
    {
        $labels = [...self::STAGES, 'Selesai'];

        return collect($labels)->map(fn (string $label, int $i) => [
            'label' => $label,
            'count' => $this->rows->where('stage', $i)->count(),
        ])->all();
    }

    /** Rata-rata progres tahapan seluruh event (0-100). */
    public function overallProgress(): int
    {
        return $this->rows->isEmpty() ? 0 : (int) round($this->rows->avg('progress'));
    }

    /**
     * Tren kumulatif harian: peserta terdaftar, hadir, selesai post-test (pertama kali), sertifikat terbit.
     * $days = null berarti sejak data pertama.
     *
     * @return array{labels: array<int, string>, series: array<string, array<int, int>>}
     */
    public function trend(?int $days = 60): array
    {
        $dates = [
            'Terdaftar' => DB::table('learning_event_participant')->selectRaw('COALESCE(registered_at, created_at) d')->pluck('d'),
            'Hadir' => DB::table('attendances')->where('status', 'hadir')->whereNotNull('participant_id')->selectRaw('COALESCE(checked_in_at, created_at) d')->pluck('d'),
            'Selesai Post-Test' => DB::table('assessment_attempts')
                ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
                ->where('assessments.type', 'post')->whereNotNull('assessment_attempts.participant_id')
                ->groupBy('assessment_attempts.participant_id', 'assessments.learning_event_id')
                ->selectRaw('MIN(assessment_attempts.created_at) d')->pluck('d'),
            'Sertifikat terbit' => DB::table('certificates')->where('status', 'issued')->whereNotNull('issued_at')->pluck('issued_at'),
        ];
        $dates = array_map(fn ($c) => $c->filter()->map(fn ($d) => substr((string) $d, 0, 10))->sort()->values(), $dates);

        $first = collect($dates)->flatten()->min();
        $end = now()->startOfDay();
        $start = $days ? $end->copy()->subDays($days - 1) : ($first ? \Illuminate\Support\Carbon::parse($first)->startOfDay() : $end->copy()->subDays(29));
        $labels = [];
        $series = array_fill_keys(array_keys($dates), []);
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->translatedFormat('d M');
            foreach ($dates as $name => $list) {
                $series[$name][] = $list->filter(fn ($d) => $d <= $key)->count();
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /** Satu baris event (untuk halaman Detail Lokasi). */
    public function row(?int $eventId): ?array
    {
        return $this->rows->first(fn (array $r) => $r['event']->id === $eventId);
    }

    /** Semua tutor yang ditugaskan beserta event dan nilai ToT tertinggi per event. */
    public function tutors(): Collection
    {
        return Tutor::query()
            ->with(['user', 'learningEvents', 'totAssessments'])
            ->whereHas('learningEvents')
            ->get()
            ->flatMap(fn (Tutor $tutor) => $tutor->learningEvents->map(function (LearningEvent $event) use ($tutor): array {
                $tot = $tutor->totAssessments->where('learning_event_id', $event->id);

                return [
                    'name' => $tutor->user?->name ?? '-',
                    'email' => $tutor->user?->email,
                    'institution' => $tutor->institution,
                    'event' => $event,
                    'location' => str($event->title)->replace('Digital Safety Champions - ', '')->toString(),
                    'tot_completed' => (bool) $tutor->tot_completed,
                    'tot_score' => $tot->max('score'),
                    'tot_perfect' => (bool) $tot->firstWhere('is_perfect', true),
                    'profile_complete' => filled($tutor->nik) && filled($tutor->bank_account_number),
                ];
            }))
            ->sortBy([['location', 'asc'], ['name', 'asc']])
            ->values();
    }

    /** Profil peserta: jenis kelamin, kategori, kelas, follow IG, join WAG. */
    public function demographics(): array
    {
        $count = fn (string $col) => Participant::query()->selectRaw("COALESCE(NULLIF({$col}, ''), '-') v, COUNT(*) c")->groupBy('v')->orderByDesc('c')->pluck('c', 'v')->all();
        $total = Participant::query()->count();

        return [
            'total' => $total,
            'gender' => collect($count('gender'))->mapWithKeys(fn ($c, $v) => [match ($v) { 'L' => 'Laki-laki', 'P' => 'Perempuan', default => 'Belum diisi' } => $c])->all(),
            'category' => collect($count('participant_category'))->mapWithKeys(fn ($c, $v) => [$v === '-' ? 'Belum diisi' : ucfirst($v) => $c])->all(),
            'grade' => collect($count('grade'))->mapWithKeys(fn ($c, $v) => [$v === '-' ? 'Belum diisi' : $v => $c])->take(8)->all(),
            'engagement' => [
                'Follow Instagram' => Participant::query()->where('followed_instagram', true)->count(),
                'Join WAG' => Participant::query()->where('joined_wag', true)->count(),
                'Persetujuan data (consent)' => Participant::query()->whereNotNull('consent_at')->count(),
            ],
        ];
    }

    /** Hal yang perlu perhatian manajemen, diurutkan dari yang paling mendesak. */
    public function alerts(): Collection
    {
        return $this->rows->flatMap(function (array $r): array {
            $e = $r['event'];
            $s = $r['stats'];
            $o = $r['overview'];
            $name = str($e->title)->replace('Digital Safety Champions - ', '')->toString();
            $alerts = [];
            $daysTo = $e->starts_at ? (int) now()->startOfDay()->diffInDays($e->starts_at->copy()->startOfDay(), false) : null;

            if ($o->isFinished() && in_array($e->final_report_status ?? 'draft', ['draft', 'revision'], true)) {
                $alerts[] = ['level' => 'danger', 'rank' => 1, 'text' => 'Kegiatan sudah selesai, laporan final ' . ($e->final_report_status === 'revision' ? 'perlu revisi' : 'belum dikirim') . " (syarat {$r['proof_done']}/{$r['proof_total']})"];
            }
            if ($e->final_report_status === 'submitted') {
                $alerts[] = ['level' => 'warning', 'rank' => 2, 'text' => 'Laporan final menunggu approval Admin RTIK Pusat'];
            }
            if ($r['evidence']['pending'] > 0) {
                $alerts[] = ['level' => 'warning', 'rank' => 3, 'text' => "{$r['evidence']['pending']} bukti dukung menunggu verifikasi"];
            }
            if ($r['evidence']['rejected'] > 0) {
                $alerts[] = ['level' => 'danger', 'rank' => 3, 'text' => "{$r['evidence']['rejected']} bukti dukung ditolak, perlu diunggah ulang"];
            }
            if ($daysTo !== null && $daysTo >= 0 && $daysTo <= 7 && $s['target'] > 0 && $s['participants'] < $s['target'] * 0.5) {
                $alerts[] = ['level' => 'warning', 'rank' => 4, 'text' => ($daysTo === 0 ? 'Hari ini' : "H-{$daysTo}") . ", peserta baru {$s['participants']}/{$s['target']}"];
            }
            if ($s['tutors_target'] > 0 && $s['tutors'] < $s['tutors_target']) {
                $alerts[] = ['level' => 'warning', 'rank' => 5, 'text' => "Tutor baru {$s['tutors']}/{$s['tutors_target']} dari kuota"];
            }
            if ($s['tutors'] > $s['tutors_tot'] && ! $o->isFinished()) {
                $alerts[] = ['level' => 'warning', 'rank' => 5, 'text' => ($s['tutors'] - $s['tutors_tot']) . ' tutor belum lulus ToT'];
            }

            return array_map(fn (array $a) => [...$a, 'event' => $e, 'name' => $name], $alerts);
        })->sortBy('rank')->values();
    }

    /**
     * Checklist laporan final (13 syarat). EventOverview ikut membuat URL resource untuk panel aktif,
     * sedangkan panel Manajemen sengaja tidak punya resource; URL dibuat sementara dengan panel superadmin
     * (hanya teks URL yang tidak ditampilkan, tidak memberi akses apa pun), lalu panel dikembalikan.
     */
    private function checklist(EventOverview $overview): array
    {
        $current = Filament::getCurrentPanel();
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));

        try {
            return $overview->finalReportChecklist();
        } finally {
            Filament::setCurrentPanel($current);
        }
    }

    private function eventAverage(LearningEvent $event, string $type): ?float
    {
        $value = AssessmentAttempt::query()
            ->whereNotNull('participant_id')
            ->whereHas('assessment', fn ($q) => $q->where('type', $type)->where('learning_event_id', $event->id))
            ->avg('score');

        return $value === null ? null : round((float) $value, 1);
    }
}
