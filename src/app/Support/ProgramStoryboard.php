<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data untuk halaman Storyboard manajerial: cerita program dari jangkauan, perjalanan peserta,
 * hasil belajar, indikator TOR (phishing/hoaks, self-efficacy), kelengkapan data, sampai rapor per lokasi.
 *
 * Memakai ProgramMetrics untuk angka yang sudah ada di dashboard (agar konsisten), dan menghitung
 * sendiri indikator yang belum ada: kenaikan nilai berpasangan, soal skenario per tema, kelengkapan data.
 * Hanya membaca; tidak ada data pribadi (nama/email/HP) yang dikeluarkan, hanya hitungan.
 */
class ProgramStoryboard
{
    /** Batas penilaian (persen) untuk rapor dan daftar tindak lanjut. */
    public const BATAS_KUOTA = 85;

    public const BATAS_HADIR = 80;

    public const BATAS_SERTIFIKAT = 80;

    public const TEMA_PHISHING = 'Phishing & penipuan';

    public const TEMA_HOAKS = 'Hoaks & misinformasi';

    public const TEMA_LAIN = 'Keamanan akun, perangkat & etika';

    /** Rentang nilai untuk sebaran pre/post-test. */
    public const RENTANG = [[0, 39], [40, 59], [60, 69], [70, 84], [85, 100]];

    private static ?self $instance = null;

    private ProgramMetrics $metrics;

    /** @var array<int, array<string, mixed>> per event id */
    private array $learning = [];

    private array $overall = [];

    private array $themes = [];

    private array $distribution = [];

    private function __construct()
    {
        $this->metrics = ProgramMetrics::get();
        $this->computeLearning();
    }

    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    public static function flush(): void
    {
        self::$instance = null;
    }

    /** "Digital Safety Champions - Kota Bogor" → "Kota Bogor". */
    public static function shortName(string $title): string
    {
        $name = trim((string) preg_replace('/^Digital Safety Champions\s*-\s*/i', '', $title));

        return $name === mb_strtolower($name) ? mb_convert_case($name, MB_CASE_TITLE) : $name;
    }

    /** Tema soal dari isi pertanyaannya; label "(Modul …)" diabaikan karena judul modul bisa menyesatkan. */
    public static function theme(string $question): string
    {
        $q = mb_strtolower((string) preg_replace('/^\(modul[^)]*\)\s*/iu', '', $question));

        return match (true) {
            (bool) preg_match('/hoaks|hoax|berita|misinformasi|disinformasi|informasi palsu|cek fakta|kebenaran|verifikasi informasi|menyesatkan/u', $q) => self::TEMA_HOAKS,
            (bool) preg_match('/sms|hadiah|link|tautan|otp|e-?mail|transfer|diskon|penipu|scam|phishing|rekening|klaim|iklan|undian|pinjol|lowongan|belanja|bertransaksi online/u', $q) => self::TEMA_PHISHING,
            default => self::TEMA_LAIN,
        };
    }

    // ------------------------------------------------------------------ ringkasan

    /** Total semua lokasi + persentasenya. */
    public function totals(): array
    {
        $rows = $this->metrics->rows;
        $sum = fn (string $key) => (int) $rows->sum(fn ($r) => $r['stats'][$key] ?? 0);
        $target = $sum('target');
        $registered = $sum('participants');

        return [
            'lokasi' => $rows->count(),
            'laporan_disetujui' => $rows->filter(fn ($r) => $r['event']->final_report_status === 'approved')->count(),
            'target' => $target,
            'terdaftar' => $registered,
            'hadir' => $sum('attended'),
            'sertifikat' => $sum('certificates'),
            'microsite' => $sum('microsite'),
            'pct_target' => self::pct($registered, $target),
            'pct_hadir' => self::pct($sum('attended'), $registered),
            'pct_sertifikat' => self::pct($sum('certificates'), $registered),
            'rata_pre' => $this->metrics->kpi()['avg_pre'],
            'rata_post' => $this->metrics->kpi()['avg_post'],
            'peserta_unik' => $this->metrics->kpi()['participants'],
        ];
    }

    /** Satu baris per lokasi, urut tanggal, dengan capaian & indikator. */
    public function locations(): Collection
    {
        return $this->metrics->rows->map(function (array $r): array {
            $s = $r['stats'];
            $e = $r['event'];
            $l = $this->learning[$e->id] ?? [];

            return [
                'id' => $e->id,
                'lokasi' => self::shortName($e->title),
                'tanggal' => $e->starts_at,
                'target' => $s['target'],
                'terdaftar' => $s['participants'],
                'hadir' => $s['attended'],
                'microsite' => $s['microsite'],
                'sertifikat' => $s['certificates'],
                'tutor' => $s['tutors'],
                'tutor_tot' => $s['tutors_tot'],
                'rata_pre' => $r['avg_pre'] !== null ? round($r['avg_pre'], 1) : null,
                'rata_post' => $r['avg_post'] !== null ? round($r['avg_post'], 1) : null,
                'bukti_disetujui' => $r['evidence']['approved'],
                'laporan_final' => $e->final_report_status ?: 'draft',
                'pct_target' => self::pct($s['participants'], $s['target']),
                'pct_hadir' => self::pct($s['attended'], $s['participants']),
                'pct_sertifikat' => self::pct($s['certificates'], $s['participants']),
                'kenaikan' => $l['kenaikan'] ?? null,
                'pasangan' => $l['pasangan'] ?? 0,
                'naik' => $l['naik'] ?? 0,
                'tetap' => $l['tetap'] ?? 0,
                'turun' => $l['turun'] ?? 0,
                'skenario_pre' => $l['skenario_pre'] ?? null,
                'skenario_post' => $l['skenario_post'] ?? null,
            ];
        })->values();
    }

    /** Ringkasan jangkauan: berapa lokasi ≥95% kuota, dan yang terendah. */
    public function reach(): array
    {
        $rows = $this->locations()->filter(fn ($r) => $r['pct_target'] !== null);
        $min = $rows->sortBy('pct_target')->first();

        return ['n95' => $rows->filter(fn ($r) => $r['pct_target'] >= 95)->count(), 'total' => $this->metrics->rows->count(), 'min' => $min];
    }

    /** Corong peserta + persentase dari terdaftar + penurunan per tahap. */
    public function funnel(): array
    {
        $steps = $this->metrics->funnel();
        $first = $steps[0]['value'] ?? 0;
        $out = [];
        foreach ($steps as $i => $step) {
            $out[] = $step + ['pct' => self::pct($step['value'], $first), 'turun' => $i ? $steps[$i - 1]['value'] - $step['value'] : 0];
        }
        $biggest = collect($out)->slice(1)->sortByDesc('turun')->keys()->first();

        return ['steps' => $out, 'bertahan' => end($out)['pct'] ?? null, 'turun_dari' => $biggest ? $out[$biggest - 1]['label'] : null, 'turun_ke' => $biggest ? $out[$biggest]['label'] : null, 'turun' => $biggest ? $out[$biggest]['turun'] : 0];
    }

    // ------------------------------------------------------------------ hasil belajar & indikator TOR

    /** Kenaikan berpasangan, soal skenario, self-efficacy, jumlah soal per tes. */
    public function indicators(): array
    {
        return $this->overall;
    }

    /** Sebaran nilai terbaik per peserta per event. */
    public function distribution(): array
    {
        return $this->distribution;
    }

    /** Persentase jawaban benar per tema soal, pre vs post. */
    public function themes(): array
    {
        return $this->themes;
    }

    private function computeLearning(): void
    {
        $eventIds = $this->metrics->rows->pluck('event.id')->all();
        $assessments = DB::table('assessments')->whereIn('type', ['pre', 'post'])->whereIn('learning_event_id', $eventIds)
            ->get(['id', 'type', 'learning_event_id', 'questions'])->keyBy('id');

        $questions = [];
        foreach ($assessments as $a) {
            foreach (json_decode((string) $a->questions, true) ?: [] as $i => $q) {
                $correct = collect($q['options'] ?? [])->search(fn ($o) => ! empty($o['is_correct']));
                $questions[$a->id][$i] = ['theme' => self::theme((string) ($q['question'] ?? '')), 'correct' => $correct];
            }
        }

        $themes = [];
        $best = []; // [event][participant][type] = ['score', 'se', 'scen_n', 'scen_ok']
        DB::table('assessment_attempts')->whereNotNull('participant_id')->whereIn('assessment_id', $assessments->keys())
            ->orderBy('id')->select(['id', 'assessment_id', 'participant_id', 'answers', 'score', 'self_efficacy', 'total_questions'])
            ->chunk(1000, function ($attempts) use ($assessments, $questions, &$themes, &$best) {
                foreach ($attempts as $at) {
                    $a = $assessments[$at->assessment_id];
                    $n = 0;
                    $ok = 0;
                    foreach (json_decode((string) $at->answers, true) ?: [] as $i => $option) {
                        $q = $questions[$a->id][$i] ?? null;
                        if (! $q || $q['correct'] === false) {
                            continue;
                        }
                        $right = (int) $option === (int) $q['correct'];
                        $themes[$q['theme']][$a->type]['n'] = ($themes[$q['theme']][$a->type]['n'] ?? 0) + 1;
                        $themes[$q['theme']][$a->type]['ok'] = ($themes[$q['theme']][$a->type]['ok'] ?? 0) + (int) $right;
                        if ($q['theme'] !== self::TEMA_LAIN) {
                            $n++;
                            $ok += (int) $right;
                        }
                    }
                    $prev = $best[$a->learning_event_id][$at->participant_id][$a->type] ?? null;
                    if (! $prev || (float) $at->score > $prev['score']) {
                        $best[$a->learning_event_id][$at->participant_id][$a->type] = [
                            'score' => (float) $at->score, 'se' => $at->self_efficacy !== null ? (float) $at->self_efficacy : null,
                            'scen_n' => $n, 'scen_ok' => $ok,
                        ];
                    }
                }
            });

        $this->themes = collect([self::TEMA_PHISHING, self::TEMA_HOAKS, self::TEMA_LAIN])->map(fn ($t) => [
            'tema' => $t,
            'pre' => isset($themes[$t]['pre']) ? self::pct($themes[$t]['pre']['ok'], $themes[$t]['pre']['n']) : null,
            'post' => isset($themes[$t]['post']) ? self::pct($themes[$t]['post']['ok'], $themes[$t]['post']['n']) : null,
            'jawaban' => ($themes[$t]['pre']['n'] ?? 0) + ($themes[$t]['post']['n'] ?? 0),
        ])->filter(fn ($r) => $r['jawaban'] > 0)->values()->all();

        $all = ['pairs' => 0, 'gain' => 0.0, 'naik' => 0, 'tetap' => 0, 'turun' => 0, 'scen' => ['pre' => [0, 0], 'post' => [0, 0]], 'se' => ['pre' => [], 'post' => []], 'scores' => ['pre' => [], 'post' => []]];
        foreach ($best as $eventId => $participants) {
            $ev = ['pairs' => 0, 'gain' => 0.0, 'naik' => 0, 'tetap' => 0, 'turun' => 0, 'scen' => ['pre' => [0, 0], 'post' => [0, 0]]];
            foreach ($participants as $p) {
                foreach (['pre', 'post'] as $type) {
                    if (! isset($p[$type])) {
                        continue;
                    }
                    $all['scores'][$type][] = $p[$type]['score'];
                    if ($p[$type]['se'] !== null) {
                        $all['se'][$type][] = $p[$type]['se'];
                    }
                    if ($p[$type]['scen_n'] > 0) {
                        $allRight = (int) ($p[$type]['scen_ok'] === $p[$type]['scen_n']);
                        $ev['scen'][$type][0] += $allRight;
                        $ev['scen'][$type][1]++;
                    }
                }
                if (isset($p['pre'], $p['post'])) {
                    $gain = $p['post']['score'] - $p['pre']['score'];
                    $ev['pairs']++;
                    $ev['gain'] += $gain;
                    $ev[$gain > 0 ? 'naik' : ($gain < 0 ? 'turun' : 'tetap')]++;
                }
            }
            $this->learning[$eventId] = [
                'pasangan' => $ev['pairs'],
                'kenaikan' => $ev['pairs'] ? round($ev['gain'] / $ev['pairs'], 1) : null,
                'naik' => $ev['naik'], 'tetap' => $ev['tetap'], 'turun' => $ev['turun'],
                'skenario_pre' => self::pct(...$ev['scen']['pre']),
                'skenario_post' => self::pct(...$ev['scen']['post']),
            ];
            foreach (['pairs', 'gain', 'naik', 'tetap', 'turun'] as $k) {
                $all[$k] += $ev[$k];
            }
            foreach (['pre', 'post'] as $type) {
                $all['scen'][$type][0] += $ev['scen'][$type][0];
                $all['scen'][$type][1] += $ev['scen'][$type][1];
            }
        }

        $avg = fn (array $v) => $v ? round(array_sum($v) / count($v), 1) : null;
        $hi = fn (array $v) => $v ? self::pct(count(array_filter($v, fn ($s) => $s >= 85)), count($v)) : null;
        $this->overall = [
            'pasangan' => $all['pairs'],
            'kenaikan' => $all['pairs'] ? round($all['gain'] / $all['pairs'], 1) : null,
            'naik' => $all['naik'], 'tetap' => $all['tetap'], 'turun' => $all['turun'],
            'skenario_pre' => self::pct(...$all['scen']['pre']),
            'skenario_post' => self::pct(...$all['scen']['post']),
            'pre_85' => $hi($all['scores']['pre']),
            'post_85' => $hi($all['scores']['post']),
            'self_efficacy_pre' => $avg($all['se']['pre']),
            'self_efficacy_post' => $avg($all['se']['post']),
            // Self-efficacy dianggap belum terukur bila semua nilainya disalin dari skor tes.
            'self_efficacy_dari_skor' => $assessments->isNotEmpty() && ! DB::table('assessment_attempts')
                ->whereIn('assessment_id', $assessments->keys())->whereNotNull('self_efficacy')
                ->whereColumn('self_efficacy', '<>', 'score')->exists(),
            'soal_per_tes' => (int) round((float) DB::table('assessment_attempts')->whereIn('assessment_id', $assessments->keys())->avg('total_questions')),
        ];

        $this->distribution = collect(self::RENTANG)->map(fn ($r) => [
            'rentang' => "{$r[0]}–{$r[1]}",
            'pre' => count(array_filter($all['scores']['pre'], fn ($s) => $s >= $r[0] && $s < $r[1] + 1)),
            'post' => count(array_filter($all['scores']['post'], fn ($s) => $s >= $r[0] && $s < $r[1] + 1)),
        ])->all();
    }

    // ------------------------------------------------------------------ waktu, profil, kelengkapan

    public function trend(): array
    {
        return $this->metrics->trend(null);
    }

    public function demographics(): array
    {
        return $this->metrics->demographics();
    }

    /** Kelengkapan data peserta (per pendaftaran) dan tutor (per penugasan). Hanya hitungan. */
    public function completeness(): array
    {
        $eventIds = $this->metrics->rows->pluck('event.id')->all();
        $filled = fn (string $col) => "NULLIF(TRIM({$col}), '') IS NOT NULL";

        $p = DB::table('learning_event_participant as lep')
            ->join('participants as p', 'p.id', '=', 'lep.participant_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->whereIn('lep.learning_event_id', $eventIds)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('SUM(CASE WHEN p.school_id IS NOT NULL THEN 1 ELSE 0 END) sekolah')
            ->selectRaw("SUM(CASE WHEN u.email LIKE '%@%.%' THEN 1 ELSE 0 END) email")
            ->selectRaw('SUM(CASE WHEN ' . $filled('u.phone') . ' THEN 1 ELSE 0 END) hp')
            ->selectRaw('SUM(CASE WHEN ' . $filled('p.grade') . ' THEN 1 ELSE 0 END) kelas')
            ->first();

        $t = DB::table('learning_event_tutor as let')
            ->join('tutors as t', 't.id', '=', 'let.tutor_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->whereIn('let.learning_event_id', $eventIds)
            ->selectRaw('COUNT(*) total')
            ->selectRaw('SUM(CASE WHEN t.tot_completed THEN 1 ELSE 0 END) tot')
            ->selectRaw("SUM(CASE WHEN u.email LIKE '%@%.%' THEN 1 ELSE 0 END) email")
            ->selectRaw('SUM(CASE WHEN ' . $filled('t.institution') . ' THEN 1 ELSE 0 END) lembaga')
            ->selectRaw('SUM(CASE WHEN ' . $filled('u.phone') . ' THEN 1 ELSE 0 END) hp')
            ->selectRaw('SUM(CASE WHEN ' . $filled('t.nik') . ' AND ' . $filled('t.npwp') . ' AND ' . $filled('t.bank_account_number') . ' THEN 1 ELSE 0 END) honor')
            ->first();

        $pt = (int) ($p->total ?? 0);
        $tt = (int) ($t->total ?? 0);

        return [
            'peserta' => $pt,
            'peserta_items' => [
                ['Asal sekolah', self::pct((int) $p->sekolah, $pt)],
                ['Email', self::pct((int) $p->email, $pt)],
                ['Nomor HP', self::pct((int) $p->hp, $pt)],
                ['Kelas', self::pct((int) $p->kelas, $pt)],
            ],
            'tutor' => $tt,
            'honor' => (int) ($t->honor ?? 0),
            'tutor_items' => [
                ['Lulus ToT', self::pct((int) $t->tot, $tt)],
                ['Email', self::pct((int) $t->email, $tt)],
                ['Lembaga', self::pct((int) $t->lembaga, $tt)],
                ['Nomor HP', self::pct((int) $t->hp, $tt)],
                ['Data honor lengkap', self::pct((int) $t->honor, $tt)],
            ],
        ];
    }

    // ------------------------------------------------------------------ daftar detail per lokus

    /** Pilihan lokasi untuk filter daftar: [event_id => nama lokasi]. */
    public function locationOptions(): array
    {
        return $this->metrics->rows
            ->mapWithKeys(fn (array $r) => [$r['event']->id => self::shortName($r['event']->title)])
            ->sort()
            ->all();
    }

    /**
     * Daftar peserta per pendaftaran, dengan status kegiatan. Tanpa NIK/NIS.
     * Hanya untuk halaman yang dibatasi Manajemen / Admin Pusat (berisi kontak pribadi).
     */
    public const STATUS_PESERTA = [
        'hadir' => 'Hadir',
        'tidak-hadir' => 'Tidak hadir',
        'sertifikat' => 'Sertifikat terbit',
        'belum-sertifikat' => 'Sertifikat belum terbit',
        'tanpa-microsite' => 'Belum membuat microsite',
    ];

    public const STATUS_TUTOR = [
        'tot' => 'Lulus ToT',
        'belum-tot' => 'Belum lulus ToT',
        'honor' => 'Data honor lengkap',
        'belum-honor' => 'Data honor belum lengkap',
    ];

    public function participantsQuery(?int $eventId = null, ?string $search = null, ?string $status = null): \Illuminate\Database\Query\Builder
    {
        $same = 'x.learning_event_id = lep.learning_event_id AND x.participant_id = lep.participant_id';
        $hadir = "EXISTS(SELECT 1 FROM attendances x WHERE {$same} AND x.status = 'hadir')";
        $micro = "EXISTS(SELECT 1 FROM microsite_practices x WHERE {$same} AND x.sid_url IS NOT NULL AND x.sid_url <> '')";
        $cert = "EXISTS(SELECT 1 FROM certificates x WHERE {$same} AND x.status = 'issued')";
        $score = fn (string $type) => "(SELECT MAX(aa.score) FROM assessment_attempts aa JOIN assessments asm ON asm.id = aa.assessment_id WHERE asm.learning_event_id = lep.learning_event_id AND asm.type = '{$type}' AND aa.participant_id = lep.participant_id)";

        return DB::table('learning_event_participant as lep')
            ->join('learning_events as e', 'e.id', '=', 'lep.learning_event_id')
            ->join('participants as p', 'p.id', '=', 'lep.participant_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('schools as s', 's.id', '=', 'p.school_id')
            ->whereIn('lep.learning_event_id', $this->metrics->rows->pluck('event.id'))
            ->when($eventId, fn ($q) => $q->where('lep.learning_event_id', $eventId))
            ->when(filled($search), function ($q) use ($search) {
                $like = '%' . trim((string) $search) . '%';
                $q->where(fn ($w) => $w->where('u.name', 'like', $like)->orWhere('s.name', 'like', $like)->orWhere('p.organization', 'like', $like)
                    ->orWhere('u.email', 'like', $like)->orWhere('u.phone', 'like', $like));
            })
            ->when($status, fn ($q) => match ($status) {
                'hadir' => $q->whereRaw($hadir),
                'tidak-hadir' => $q->whereRaw("NOT {$hadir}"),
                'sertifikat' => $q->whereRaw($cert),
                'belum-sertifikat' => $q->whereRaw("NOT {$cert}"),
                'tanpa-microsite' => $q->whereRaw("NOT {$micro}"),
                default => $q,
            })
            ->select(['lep.id', 'e.title as event_title', 'u.name', 'p.gender', 'p.grade', 'u.email', 'u.phone'])
            ->selectRaw('COALESCE(s.name, p.organization) as sekolah')
            ->selectRaw("{$hadir} as hadir")
            ->selectRaw($score('pre') . ' as nilai_pre')
            ->selectRaw($score('post') . ' as nilai_post')
            ->selectRaw("{$micro} as microsite")
            ->selectRaw("{$cert} as sertifikat")
            ->orderBy('e.starts_at')->orderBy('u.name');
    }

    /** Daftar tutor per penugasan. Tanpa NIK/NPWP/rekening; data honor hanya statusnya. */
    public function tutorsQuery(?int $eventId = null, ?string $search = null, ?string $status = null): \Illuminate\Database\Query\Builder
    {
        $filled = fn (string $col) => "NULLIF(TRIM({$col}), '') IS NOT NULL";
        $honor = $filled('t.nik') . ' AND ' . $filled('t.npwp') . ' AND ' . $filled('t.bank_account_number');

        return DB::table('learning_event_tutor as let')
            ->join('learning_events as e', 'e.id', '=', 'let.learning_event_id')
            ->join('tutors as t', 't.id', '=', 'let.tutor_id')
            ->join('users as u', 'u.id', '=', 't.user_id')
            ->whereIn('let.learning_event_id', $this->metrics->rows->pluck('event.id'))
            ->when($eventId, fn ($q) => $q->where('let.learning_event_id', $eventId))
            ->when(filled($search), function ($q) use ($search) {
                $like = '%' . trim((string) $search) . '%';
                $q->where(fn ($w) => $w->where('u.name', 'like', $like)->orWhere('t.institution', 'like', $like)
                    ->orWhere('u.email', 'like', $like)->orWhere('u.phone', 'like', $like));
            })
            ->when($status, fn ($q) => match ($status) {
                'tot' => $q->where('t.tot_completed', true),
                'belum-tot' => $q->where(fn ($w) => $w->where('t.tot_completed', false)->orWhereNull('t.tot_completed')),
                'honor' => $q->whereRaw("({$honor})"),
                'belum-honor' => $q->whereRaw("NOT ({$honor})"),
                default => $q,
            })
            ->select(['let.id', 'e.title as event_title', 'u.name', 't.institution', 'u.email', 'u.phone', 't.tot_completed'])
            ->selectRaw('(SELECT MAX(ta.score) FROM tot_assessments ta WHERE ta.learning_event_id = let.learning_event_id AND ta.tutor_id = let.tutor_id) as nilai_tot')
            ->selectRaw("CASE WHEN {$honor} THEN 1 ELSE 0 END as honor_lengkap")
            ->orderBy('e.starts_at')->orderBy('u.name');
    }

    /** Ringkasan untuk daftar peserta yang sedang difilter. */
    public function participantSummary(?int $eventId = null, ?string $search = null, ?string $status = null): array
    {
        $rows = DB::query()->fromSub($this->participantsQuery($eventId, $search, $status)->reorder(), 'd')
            ->selectRaw('COUNT(*) total, SUM(hadir) hadir, SUM(microsite) microsite, SUM(sertifikat) sertifikat, AVG(nilai_pre) pre, AVG(nilai_post) post')
            ->first();

        return [
            'total' => (int) $rows->total, 'hadir' => (int) $rows->hadir, 'microsite' => (int) $rows->microsite, 'sertifikat' => (int) $rows->sertifikat,
            'pre' => $rows->pre !== null ? round((float) $rows->pre, 1) : null, 'post' => $rows->post !== null ? round((float) $rows->post, 1) : null,
        ];
    }

    /** Ringkasan untuk daftar tutor yang sedang difilter. */
    public function tutorSummary(?int $eventId = null, ?string $search = null, ?string $status = null): array
    {
        $rows = DB::query()->fromSub($this->tutorsQuery($eventId, $search, $status)->reorder(), 'd')
            ->selectRaw('COUNT(*) total, COUNT(DISTINCT name) unik, SUM(CASE WHEN tot_completed THEN 1 ELSE 0 END) tot, SUM(honor_lengkap) honor')
            ->first();

        return ['total' => (int) $rows->total, 'unik' => (int) $rows->unik, 'tot' => (int) $rows->tot, 'honor' => (int) $rows->honor];
    }

    /** Hal yang perlu ditindaklanjuti, dari batas penilaian yang sama dengan rapor. */
    public function attention(): Collection
    {
        $fmt = fn ($v) => number_format((float) $v, 1, ',', '.');
        $items = $this->locations()->flatMap(function (array $r) use ($fmt): array {
            $out = [];
            if ($r['pct_target'] !== null && $r['pct_target'] < self::BATAS_KUOTA) {
                $out[] = ['lokasi' => $r['lokasi'], 'isu' => 'Kuota peserta', 'level' => 'warning', 'urut' => 1, 'detail' => "Terdaftar {$fmt($r['pct_target'])}% dari target."];
            }
            if ($r['pct_hadir'] !== null && $r['pct_hadir'] < self::BATAS_HADIR) {
                $out[] = ['lokasi' => $r['lokasi'], 'isu' => 'Kehadiran', 'level' => 'warning', 'urut' => 2, 'detail' => "Hanya {$fmt($r['pct_hadir'])}% peserta terdaftar yang hadir."];
            }
            if ($r['pct_sertifikat'] !== null && $r['pct_sertifikat'] < self::BATAS_SERTIFIKAT) {
                $out[] = ['lokasi' => $r['lokasi'], 'isu' => 'E-sertifikat', 'level' => 'warning', 'urut' => 3, 'detail' => "Baru {$fmt($r['pct_sertifikat'])}% peserta terdaftar yang menerima sertifikat."];
            }
            if ($r['kenaikan'] !== null && $r['kenaikan'] < 0) {
                $out[] = ['lokasi' => $r['lokasi'], 'isu' => 'Hasil belajar', 'level' => 'danger', 'urut' => 4, 'detail' => 'Nilai rata-rata per peserta turun ' . $fmt(abs($r['kenaikan'])) . ' poin setelah kegiatan.'];
            }

            return $out;
        });

        $c = $this->completeness();
        if ($c['tutor'] > $c['honor']) {
            $items->push(['lokasi' => 'Semua lokasi', 'isu' => 'Data honor tutor', 'level' => 'warning', 'urut' => 5, 'detail' => ($c['tutor'] - $c['honor']) . " dari {$c['tutor']} penugasan tutor belum punya NIK, NPWP, dan rekening lengkap."]);
        }
        if ($this->overall['self_efficacy_dari_skor'] ?? false) {
            $items->push(['lokasi' => 'Program', 'isu' => 'Self-efficacy', 'level' => 'danger', 'urut' => 6, 'detail' => 'Belum ada survei self-efficacy terpisah; nilainya masih disalin dari skor tes.']);
        }

        return $items->sortBy([['urut', 'asc'], ['lokasi', 'asc']])->values();
    }

    public static function pct(int | float $part, int | float $whole): ?float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : null;
    }
}
