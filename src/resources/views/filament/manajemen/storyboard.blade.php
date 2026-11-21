<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $SB = \App\Support\ProgramStoryboard::class;
        $n = fn ($v, int $d = 0) => $v === null ? '–' : number_format((float) $v, $d, ',', '.');
        $p = fn ($v) => $v === null ? '–' : number_format((float) $v, 1, ',', '.') . '%';
        $signed = fn ($v) => $v === null ? '–' : ($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '.');
        $w = fn ($v, $max) => $max > 0 ? max(0, min(100, round((float) $v / $max * 100, 2))) : 0;
        $kel = fn ($v) => $v === null ? 'var(--eo-muted)' : ($v >= 95 ? 'var(--eo-success)' : ($v >= 70 ? 'var(--eo-warning)' : 'var(--eo-danger)'));
        $pre = 'var(--sb-pre)';
        $post = 'var(--sb-post)';
        $locs = $locations;
        $short = fn ($t) => $SB::shortName((string) $t);
        $gender = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        $tutorItems = collect($comp['tutor_items'])->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
        $pesertaItems = collect($comp['peserta_items'])->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
    @endphp
    <style>
        .sb { --sb-pre: rgb(148, 163, 184); --sb-post: rgb(79, 70, 229); gap: 28px; }
        .dark .sb { --sb-pre: rgb(100, 116, 139); --sb-post: rgb(129, 140, 248); }
        .sb-eyebrow { color: var(--eo-muted); font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .sb-part { display: grid; gap: 22px; }
        .sb-part + .sb-part { border-top: 2px solid var(--eo-border); padding-top: 28px; }
        .sb-part-title { color: var(--eo-text); font-size: 24px; font-weight: 800; line-height: 1.25; margin: 2px 0 0; }
        .sb-toc { display: flex; flex-wrap: wrap; gap: 6px; }
        .sb-toc a { border: 1px solid var(--eo-border); border-radius: 999px; color: var(--eo-text); font-size: 12px; font-weight: 600; padding: 4px 10px; text-decoration: none; }
        .sb-toc a:hover { border-color: var(--sb-post); }
        .sb-chapter { display: grid; gap: 14px; scroll-margin-top: 80px; }
        .sb-chapter + .sb-chapter { border-top: 1px solid var(--eo-border); padding-top: 22px; }
        .sb-num { border: 1px solid var(--eo-border); border-radius: 6px; color: var(--eo-muted); display: inline-block; font-size: 12px; font-weight: 800; margin-right: 8px; padding: 1px 7px; vertical-align: 2px; }
        .sb-q { color: var(--eo-text); font-size: 18px; font-weight: 800; line-height: 1.35; margin: 0; }
        .sb-criteria { color: var(--eo-muted); font-size: 12.5px; font-style: italic; margin: 0; }
        .sb-text { display: grid; gap: 10px; max-width: 80ch; }
        .sb-text p { color: var(--eo-text); font-size: 15px; line-height: 1.75; margin: 0; }
        .sb-text strong { font-variant-numeric: tabular-nums; }
        .sb-grid2 { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }
        .sb-grid2 > * { min-width: 0; }
        .sb-legend { color: var(--eo-muted); display: flex; flex-wrap: wrap; font-size: 12px; gap: 6px 14px; margin-bottom: 10px; }
        .sb-legend i { border-radius: 3px; display: inline-block; height: 10px; margin-right: 5px; vertical-align: -1px; width: 10px; }
        .sb-rows { display: grid; gap: 6px; }
        .sb-row { align-items: center; display: grid; font-size: 13px; gap: 10px; grid-template-columns: minmax(90px, 150px) 1fr 64px; }
        .sb-row > span:first-child { color: var(--eo-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sb-row .sb-val { color: var(--eo-text); font-variant-numeric: tabular-nums; font-weight: 700; text-align: right; }
        .sb-track { background: var(--eo-track); border-radius: 999px; height: 12px; position: relative; }
        .sb-fill { border-radius: 999px; display: block; height: 100%; }
        .sb-ref { border-left: 2px dashed var(--eo-muted); bottom: -3px; position: absolute; top: -3px; }
        .sb-dot { border: 2px solid var(--eo-card); border-radius: 999px; height: 12px; position: absolute; top: 0; transform: translateX(-50%); width: 12px; }
        .sb-seg { height: 2px; position: absolute; top: 5px; }
        .sb-zero { background: var(--eo-muted); bottom: -3px; position: absolute; top: -3px; width: 1px; }
        .sb-cols { align-items: end; display: grid; gap: 10px; grid-template-columns: repeat(5, 1fr); height: 200px; }
        .sb-col { align-items: end; display: flex; gap: 3px; height: 100%; justify-content: center; position: relative; }
        .sb-col > div { border-radius: 4px 4px 0 0; position: relative; width: 42%; }
        .sb-col > div span { color: var(--eo-muted); font-size: 11px; left: 50%; position: absolute; top: -16px; transform: translateX(-50%); white-space: nowrap; }
        .sb-col-labels { color: var(--eo-muted); display: grid; font-size: 12px; gap: 10px; grid-template-columns: repeat(5, 1fr); margin-top: 6px; text-align: center; }
        .sb-flag { border: 1px solid var(--eo-danger); border-radius: 14px; display: grid; gap: 8px; padding: 14px 16px; }
        .sb-pill { border: 1px solid currentColor; border-radius: 999px; display: inline-block; font-size: 11px; font-weight: 800; letter-spacing: .03em; padding: 2px 9px; white-space: nowrap; }
        .sb-table { border-collapse: collapse; font-size: 13px; font-variant-numeric: tabular-nums; width: 100%; }
        .sb-table th, .sb-table td { border-bottom: 1px solid var(--eo-border); padding: 8px 10px; text-align: right; white-space: nowrap; }
        .sb-table th:first-child, .sb-table td:first-child { background: var(--eo-card); left: 0; position: sticky; text-align: left; }
        .sb-table th { color: var(--eo-muted); font-size: 11px; font-weight: 700; vertical-align: bottom; white-space: normal; }
        .sb-table td { color: var(--eo-text); }
        .sb-table tfoot td { border-top: 2px solid var(--eo-border); font-weight: 700; }
        .sb-table .sb-left { text-align: left; }
        .sb-low { color: var(--eo-danger) !important; font-weight: 700; }
        .sb-yes { color: var(--eo-success); }
        .sb-no { color: var(--eo-muted); }
        .sb-warn { color: var(--eo-warning); }
        .sb-list { display: grid; gap: 8px; list-style: none; margin: 0; padding: 0; }
        .sb-list li { align-items: start; background: var(--eo-card); border: 1px solid var(--eo-border); border-radius: 12px; display: grid; font-size: 14px; gap: 10px; grid-template-columns: minmax(130px, 190px) 1fr; padding: 10px 14px; }
        .sb-trend text { fill: var(--eo-muted); font-size: 11px; }
        .sb-trend .sb-end { fill: var(--eo-text); font-weight: 700; }
        .sb-trend line.grid { stroke: var(--eo-track); }
        .sb-controls { align-items: end; display: flex; flex-wrap: wrap; gap: 10px 14px; }
        .sb-controls label { color: var(--eo-muted); display: grid; font-size: 12px; font-weight: 600; gap: 4px; }
        .sb-input { background: var(--eo-card); border: 1px solid var(--eo-border); border-radius: 10px; color: var(--eo-text); font-size: 14px; min-width: 200px; padding: 7px 10px; }
        .sb-input:focus { border-color: var(--sb-post); outline: none; }
        .sb-btn { align-items: center; background: var(--eo-card); border: 1px solid var(--eo-border); border-radius: 10px; color: var(--eo-text); cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 700; gap: 6px; padding: 7px 12px; }
        .sb-btn:hover { border-color: var(--sb-post); }
        .sb-btn svg { height: 16px; width: 16px; }
        .sb-link { background: none; border: 0; color: var(--eo-muted); cursor: pointer; font-size: 12px; padding: 0; text-decoration: underline; }
        .sb-sum { color: var(--eo-muted); font-size: 13px; margin: 0; }
        .sb-sum strong { color: var(--eo-text); }
        .sb-tall { border: 1px solid var(--eo-border); border-radius: 12px; overflow-x: auto; }
        details.sb-details summary { color: var(--eo-muted); cursor: pointer; font-size: 13px; }
        @media (max-width: 640px) { .sb-row { grid-template-columns: 1fr 56px; } .sb-row .sb-track { grid-column: 1 / -1; grid-row: 2; } .sb-list li { grid-template-columns: 1fr; } .sb-input { min-width: 0; width: 100%; } .sb-controls label { width: 100%; } }
    </style>

    <div class="eo sb">
        {{-- Ringkasan eksekutif --}}
        <div class="eo-card eo-pad" style="display: grid; gap: 14px;" id="ringkasan-eksekutif">
            <div class="sb-eyebrow">Ringkasan eksekutif</div>
            <div class="sb-text">
                <p>Program Digital Safety Champions telah dilaksanakan di <strong>{{ $n($totals['lokasi']) }}</strong> lokasi, dan <strong>{{ $n($totals['laporan_disetujui']) }}</strong> laporan final telah disetujui. Program menjangkau <strong>{{ $n($totals['terdaftar']) }}</strong> peserta terdaftar atau <strong>{{ $p($totals['pct_target']) }}</strong> dari target <strong>{{ $n($totals['target']) }}</strong> peserta, dengan tingkat kehadiran <strong>{{ $p($totals['pct_hadir']) }}</strong>. Hingga saat ini, <strong>{{ $n($totals['sertifikat']) }}</strong> peserta (<strong>{{ $p($totals['pct_sertifikat']) }}</strong>) telah menerima e-sertifikat dan <strong>{{ $n($totals['microsite']) }}</strong> microsite berhasil dibuat oleh siswa.</p>
                <p>Dari sisi hasil belajar, rata-rata nilai post-test mencapai <strong>{{ $n($totals['rata_post'], 1) }}</strong> dibandingkan <strong>{{ $n($totals['rata_pre'], 1) }}</strong> pada pre-test, dengan kenaikan rata-rata per peserta sebesar <strong>{{ $signed($ind['kenaikan']) }}</strong> poin{{ ($ind['pre_85'] ?? 0) >= 60 ? '. Kenaikan yang terbatas ini disebabkan tingginya pengetahuan awal peserta' : '' }}. Pada post-test, <strong>{{ $p($ind['skenario_post']) }}</strong> peserta menjawab benar seluruh soal skenario phishing dan hoaks. Hal yang masih memerlukan perhatian adalah {{ $ind['self_efficacy_dari_skor'] ? 'indikator self-efficacy yang belum terukur secara terpisah dan ' : '' }}data honor tutor yang baru lengkap pada <strong>{{ $n($comp['honor']) }}</strong> dari <strong>{{ $n($comp['tutor']) }}</strong> penugasan.</p>
            </div>
            <div class="eo-stats">
                <div class="eo-stat"><div class="eo-stat-label">Laporan final disetujui</div><div class="eo-stat-value">{{ $n($totals['laporan_disetujui']) }}<small> / {{ $n($totals['lokasi']) }}</small></div></div>
                <div class="eo-stat"><div class="eo-stat-label">Terdaftar vs target</div><div class="eo-stat-value">{{ $p($totals['pct_target']) }}</div><div class="eo-muted" style="font-size: 12px;">{{ $n($totals['terdaftar']) }} dari {{ $n($totals['target']) }}</div></div>
                <div class="eo-stat"><div class="eo-stat-label">Kehadiran</div><div class="eo-stat-value">{{ $p($totals['pct_hadir']) }}</div><div class="eo-muted" style="font-size: 12px;">{{ $n($totals['hadir']) }} peserta hadir</div></div>
                <div class="eo-stat"><div class="eo-stat-label">Rata-rata Post-Test</div><div class="eo-stat-value">{{ $n($totals['rata_post'], 1) }}</div><div class="eo-muted" style="font-size: 12px;">Pre-Test {{ $n($totals['rata_pre'], 1) }}</div></div>
                <div class="eo-stat"><div class="eo-stat-label">E-sertifikat terbit</div><div class="eo-stat-value">{{ $n($totals['sertifikat']) }}</div><div class="eo-muted" style="font-size: 12px;">{{ $p($totals['pct_sertifikat']) }} dari terdaftar</div></div>
            </div>
            <nav class="sb-toc" aria-label="Daftar isi">
                <a href="#bab-1">1 · Gambaran pelaksanaan</a>
                <a href="#k1">2.1 Data peserta</a>
                <a href="#k2">2.2 Data tutor</a>
                <a href="#k3">2.3 Laporan per lokus</a>
                <a href="#k4">2.4 Self-efficacy</a>
                <a href="#k5">2.5 Kenaikan nilai</a>
                <a href="#k6">2.6 Phishing &amp; hoaks</a>
                <a href="#bab-3">3 · Kesimpulan</a>
            </nav>
        </div>

        {{-- ============================== BAB 1 ============================== --}}
        <div class="sb-part" id="bab-1">
            <div>
                <div class="sb-eyebrow">Bab 1</div>
                <h2 class="sb-part-title">Gambaran pelaksanaan</h2>
                <p class="eo-muted" style="margin-top: 6px;">Bagian ini menggambarkan jangkauan, tingkat penyelesaian, dan perkembangan pelaksanaan program di seluruh lokasi.</p>
            </div>

            <section class="sb-chapter" id="jangkauan">
                <h3 class="sb-q"><span class="sb-num">1.1</span>Pencapaian target peserta</h3>
                <div class="sb-text">
                    <p>Sebanyak <strong>{{ $reach['n95'] }}</strong> dari <strong>{{ $reach['total'] }}</strong> lokasi berhasil memenuhi sekurang-kurangnya 95% kuota peserta{{ $reach['n95'] >= $reach['total'] / 2 ? ', sehingga target jangkauan secara umum tercapai' : '' }}.@if ($reach['min']) Capaian terendah tercatat di <strong>{{ $reach['min']['lokasi'] }}</strong> sebesar <strong>{{ $p($reach['min']['pct_target']) }}</strong> dari target.@endif Lokasi dengan capaian di bawah {{ $SB::BATAS_KUOTA }}% perlu dievaluasi strategi rekrutmennya untuk pelaksanaan berikutnya.</p>
                </div>
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Peserta terdaftar terhadap target, per lokasi</p>
                    <div class="sb-legend"><span><i style="border: 1px dashed var(--eo-muted);"></i>Garis = 100% target</span><span><i style="background: var(--eo-primary);"></i>Memenuhi ≥ {{ $SB::BATAS_KUOTA }}%</span><span><i style="background: var(--eo-warning);"></i>Di bawah {{ $SB::BATAS_KUOTA }}%</span></div>
                    @php $maxT = max(110, (float) $locs->max('pct_target')); @endphp
                    <div class="sb-rows">
                        @foreach ($locs->sortByDesc('pct_target') as $r)
                            <div class="sb-row" title="{{ $r['lokasi'] }}: {{ $n($r['terdaftar']) }} dari target {{ $n($r['target']) }}">
                                <span>{{ $r['lokasi'] }}</span>
                                <div class="sb-track">
                                    <span class="sb-fill" style="width: {{ $w($r['pct_target'], $maxT) }}%; background: {{ $r['pct_target'] < $SB::BATAS_KUOTA ? 'var(--eo-warning)' : 'var(--eo-primary)' }};"></span>
                                    <span class="sb-ref" style="left: {{ $w(100, $maxT) }}%;"></span>
                                </div>
                                <span class="sb-val">{{ $p($r['pct_target']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="sb-chapter" id="perjalanan">
                <h3 class="sb-q"><span class="sb-num">1.2</span>Tingkat penyelesaian rangkaian kegiatan</h3>
                <div class="sb-text">
                    <p>Dari seluruh peserta terdaftar, <strong>{{ $p($funnel['bertahan']) }}</strong> menyelesaikan rangkaian kegiatan hingga menerima e-sertifikat.@if ($funnel['turun_ke']) Penurunan jumlah peserta terbesar terjadi pada tahap <strong>{{ $funnel['turun_dari'] }}</strong> ke <strong>{{ $funnel['turun_ke'] }}</strong>, yaitu sebanyak <strong>{{ $n($funnel['turun']) }}</strong> peserta. Upaya peningkatan penyelesaian akan paling efektif bila difokuskan pada tahap tersebut.@endif</p>
                </div>
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Corong peserta, semua lokasi</p>
                    @php $maxF = max(1, $funnel['steps'][0]['value'] ?? 1); @endphp
                    <div class="sb-rows" style="gap: 10px;">
                        @foreach ($funnel['steps'] as $step)
                            <div class="sb-row" style="grid-template-columns: minmax(110px, 170px) 1fr 150px;">
                                <span>{{ $step['label'] }}</span>
                                <div class="sb-track" style="height: 16px;"><span class="sb-fill" style="width: {{ $w($step['value'], $maxF) }}%; background: var(--sb-post); opacity: {{ 1 - $loop->index * 0.07 }};"></span></div>
                                <span class="sb-val">{{ $n($step['value']) }} <span class="eo-muted" style="font-size: 12px; font-weight: 400;">· {{ $p($step['pct']) }}{{ $loop->first ? '' : ' · −' . $n($step['turun']) }}</span></span>
                            </div>
                        @endforeach
                    </div>
                    <p class="eo-muted" style="font-size: 12px; margin-top: 12px;">Peserta lengkap adalah peserta yang telah mengerjakan pre-test dan post-test, bergabung di WhatsApp Group, dan mengikuti Instagram program.</p>
                </div>
            </section>

            <section class="sb-chapter" id="waktu">
                <h3 class="sb-q"><span class="sb-num">1.3</span>Perkembangan pelaksanaan</h3>
                <div class="sb-text">
                    <p>Jumlah pendaftar, kehadiran, penyelesaian post-test, dan penerbitan e-sertifikat meningkat secara bertahap seiring jadwal pelaksanaan di setiap lokasi. Selisih antara garis "Terdaftar" dan "Sertifikat terbit" pada akhir periode menggambarkan jumlah peserta yang belum menuntaskan seluruh rangkaian kegiatan.</p>
                </div>
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Jumlah kumulatif peserta</p>
                    @php
                        $colors = ['Terdaftar' => 'rgb(79, 70, 229)', 'Hadir' => 'rgb(14, 165, 233)', 'Selesai Post-Test' => 'rgb(20, 184, 166)', 'Sertifikat terbit' => 'rgb(245, 158, 11)'];
                        $labels = $trend['labels'];
                        $count = count($labels);
                        $W = 900; $H = 280; $L = 52; $R = 70; $T = 12; $B = 28;
                        $maxY = max(1, collect($trend['series'])->flatten()->max());
                        $step = max(1, pow(10, max(0, floor(log10($maxY)))) / 2);
                        $maxY = ceil($maxY / $step) * $step;
                        $x = fn ($i) => $L + ($count > 1 ? $i / ($count - 1) : 0) * ($W - $L - $R);
                        $y = fn ($v) => $T + (1 - $v / $maxY) * ($H - $T - $B);
                        $ends = [];
                    @endphp
                    <div class="sb-legend">@foreach ($colors as $name => $c)<span><i style="background: {{ $c }};"></i>{{ $name }}</span>@endforeach</div>
                    @if ($count > 1)
                        <svg class="sb-trend" viewBox="0 0 {{ $W }} {{ $H }}" width="100%" role="img" aria-label="Jumlah kumulatif peserta terdaftar, hadir, selesai post-test dan sertifikat terbit dari waktu ke waktu">
                            @foreach (range(0, 4) as $g)
                                @php $v = $maxY / 4 * $g; @endphp
                                <line class="grid" x1="{{ $L }}" x2="{{ $W - $R }}" y1="{{ $y($v) }}" y2="{{ $y($v) }}" />
                                <text x="{{ $L - 8 }}" y="{{ $y($v) }}" dy="0.35em" text-anchor="end">{{ $n($v) }}</text>
                            @endforeach
                            @foreach (array_unique([0, intdiv($count - 1, 4), intdiv($count - 1, 2), intdiv(3 * ($count - 1), 4), $count - 1]) as $i)
                                <text x="{{ $x($i) }}" y="{{ $H - 8 }}" text-anchor="middle">{{ $labels[$i] }}</text>
                            @endforeach
                            @foreach ($trend['series'] as $name => $values)
                                @php
                                    $pts = collect($values)->map(fn ($v, $i) => round($x($i), 1) . ',' . round($y($v), 1))->implode(' ');
                                    $ends[] = ['y' => $y(end($values)), 'v' => end($values)];
                                @endphp
                                <polyline points="{{ $pts }}" fill="none" stroke="{{ $colors[$name] ?? 'gray' }}" stroke-width="2.5" stroke-linejoin="round" />
                                <circle cx="{{ $x($count - 1) }}" cy="{{ $y(end($values)) }}" r="4" fill="{{ $colors[$name] ?? 'gray' }}" />
                            @endforeach
                            @php
                                usort($ends, fn ($a, $b) => $a['y'] <=> $b['y']);
                                foreach ($ends as $i => $e) { if ($i && $e['y'] - $ends[$i - 1]['y'] < 14) { $ends[$i]['y'] = $ends[$i - 1]['y'] + 14; } }
                            @endphp
                            @foreach ($ends as $e)
                                <text class="sb-end" x="{{ $W - $R + 10 }}" y="{{ $e['y'] }}" dy="0.35em">{{ $n($e['v']) }}</text>
                            @endforeach
                        </svg>
                    @else
                        <p class="eo-muted">Belum cukup data untuk grafik tren.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- ============================== BAB 2 ============================== --}}
        <div class="sb-part" id="bab-2">
            <div>
                <div class="sb-eyebrow">Bab 2</div>
                <h2 class="sb-part-title">Capaian kriteria pelaporan</h2>
                <p class="eo-muted" style="margin-top: 6px;">Bagian ini memaparkan capaian terhadap enam kriteria pelaporan program. Daftar peserta dan tutor dapat difilter, dan berkas CSV yang diunduh hanya memuat data sesuai filter yang aktif.</p>
            </div>

            {{-- 2.1 --}}
            <section class="sb-chapter" id="k1">
                <h3 class="sb-q"><span class="sb-num">2.1</span>Data peserta terdata</h3>
                <p class="sb-criteria">Kriteria: data peserta terdata, termasuk informasi asal sekolah, email, dan nomor HP.</p>
                <div class="sb-text">
                    <p>Dari <strong>{{ $n($comp['peserta']) }}</strong> data pendaftaran, kelengkapan informasi asal sekolah mencapai <strong>{{ $p($pesertaItems['Asal sekolah'] ?? null) }}</strong>, email <strong>{{ $p($pesertaItems['Email'] ?? null) }}</strong>, dan nomor HP <strong>{{ $p($pesertaItems['Nomor HP'] ?? null) }}</strong>. Dengan tingkat kelengkapan tersebut, data peserta telah memenuhi kriteria dan siap dilaporkan. Rincian per peserta dapat dilihat dan diunduh pada daftar di bawah sesuai filter yang dipilih.</p>
                </div>
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Kelengkapan data peserta</p>
                    <div class="sb-rows" style="gap: 10px;">
                        @foreach ($comp['peserta_items'] as [$label, $v])
                            <div class="sb-row"><span>{{ $label }}</span><div class="sb-track"><span class="sb-fill" style="width: {{ $w($v, 100) }}%; background: {{ $kel($v) }};"></span></div><span class="sb-val">{{ $p($v) }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="eo-card eo-pad" style="display: grid; gap: 12px;" id="daftar-peserta">
                    <p class="eo-h3" style="margin: 0;">Daftar peserta per lokus</p>
                    <div class="sb-controls">
                        <label for="sb-p-lokasi">Lokasi
                            <select id="sb-p-lokasi" class="sb-input" wire:model.live="pLokasi">
                                <option value="">Semua lokasi</option>
                                @foreach ($lokasiOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </label>
                        <label for="sb-p-status">Status
                            <select id="sb-p-status" class="sb-input" wire:model.live="pStatus">
                                <option value="">Semua peserta</option>
                                @foreach ($statusPeserta as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label for="sb-p-cari">Cari
                            <input id="sb-p-cari" class="sb-input" type="search" placeholder="Nama, sekolah, email, atau HP" wire:model.live.debounce.400ms="pCari" autocomplete="off">
                        </label>
                        <button type="button" class="sb-btn" wire:click="downloadPeserta" wire:loading.attr="disabled" wire:target="downloadPeserta">
                            <x-heroicon-o-arrow-down-tray /> Unduh CSV ({{ $n($pesertaSummary['total']) }} baris)
                        </button>
                        @if ($pesertaFiltered)<button type="button" class="sb-link" wire:click="resetPesertaFilter">Hapus filter</button>@endif
                    </div>
                    <p class="sb-sum">
                        <strong>{{ $n($pesertaSummary['total']) }}</strong> peserta · <strong>{{ $n($pesertaSummary['hadir']) }}</strong> hadir ·
                        rata-rata pre <strong>{{ $n($pesertaSummary['pre'], 1) }}</strong>, post <strong>{{ $n($pesertaSummary['post'], 1) }}</strong> ·
                        <strong>{{ $n($pesertaSummary['microsite']) }}</strong> microsite · <strong>{{ $n($pesertaSummary['sertifikat']) }}</strong> sertifikat terbit
                    </p>
                    <div class="sb-tall">
                        <table class="sb-table">
                            <thead><tr>
                                <th class="sb-left">Nama</th><th class="sb-left">Lokasi</th><th class="sb-left">Sekolah</th><th class="sb-left">Kelas</th><th class="sb-left">Jenis kelamin</th>
                                <th class="sb-left">Email</th><th class="sb-left">No HP</th><th>Hadir</th><th>Pre-Test</th><th>Post-Test</th><th>Microsite</th><th>Sertifikat</th>
                            </tr></thead>
                            <tbody>
                                @forelse ($peserta as $r)
                                    <tr wire:key="peserta-{{ $r->id }}">
                                        <td><strong>{{ $r->name }}</strong></td>
                                        <td class="sb-left">{{ $short($r->event_title) }}</td>
                                        <td class="sb-left">{{ $r->sekolah ?: '–' }}</td>
                                        <td class="sb-left">{{ $r->grade ?: '–' }}</td>
                                        <td class="sb-left">{{ $gender[$r->gender] ?? '–' }}</td>
                                        <td class="sb-left">{{ $r->email ?: '–' }}</td>
                                        <td class="sb-left">{{ $r->phone ?: '–' }}</td>
                                        <td><span @class(['sb-pill', 'sb-yes' => $r->hadir, 'sb-no' => ! $r->hadir])>{{ $r->hadir ? 'ya' : 'tidak' }}</span></td>
                                        <td>{{ $n($r->nilai_pre) }}</td>
                                        <td>{{ $n($r->nilai_post) }}</td>
                                        <td><span @class(['sb-pill', 'sb-yes' => $r->microsite, 'sb-no' => ! $r->microsite])>{{ $r->microsite ? 'ya' : 'tidak' }}</span></td>
                                        <td><span @class(['sb-pill', 'sb-yes' => $r->sertifikat, 'sb-no' => ! $r->sertifikat])>{{ $r->sertifikat ? 'terbit' : 'belum' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="12" class="sb-left">Tidak ada peserta yang cocok dengan filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-filament::pagination :paginator="$peserta" />
                    <p class="eo-muted" style="font-size: 12px;">NIK dan NIS tidak ditampilkan. Nilai yang ditampilkan adalah nilai tertinggi peserta di lokasi tersebut.</p>
                </div>
            </section>

            {{-- 2.2 --}}
            <section class="sb-chapter" id="k2">
                <h3 class="sb-q"><span class="sb-num">2.2</span>Data tutor terdata</h3>
                <p class="sb-criteria">Kriteria: data tutor terdata, termasuk informasi detail tutor.</p>
                <div class="sb-text">
                    <p>Tutor yang bertugas telah dibekali melalui ToT dengan tingkat kelulusan <strong>{{ $p($tutorItems['Lulus ToT'] ?? null) }}</strong>. Kelengkapan data honor yang meliputi NIK, NPWP, dan nomor rekening baru tercapai pada <strong>{{ $n($comp['honor']) }}</strong> dari <strong>{{ $n($comp['tutor']) }}</strong> penugasan. Data tersebut perlu dilengkapi sebelum proses pembayaran honor dilakukan.</p>
                </div>
                <div class="eo-card eo-pad">
                    <p class="eo-h3">Kelengkapan data tutor (per penugasan)</p>
                    <div class="sb-rows" style="gap: 10px;">
                        @foreach ($comp['tutor_items'] as [$label, $v])
                            <div class="sb-row"><span>{{ $label }}</span><div class="sb-track"><span class="sb-fill" style="width: {{ $w($v, 100) }}%; background: {{ $kel($v) }};"></span></div><span class="sb-val">{{ $p($v) }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="eo-card eo-pad" style="display: grid; gap: 12px;" id="daftar-tutor">
                    <p class="eo-h3" style="margin: 0;">Daftar tutor per lokus</p>
                    <div class="sb-controls">
                        <label for="sb-t-lokasi">Lokasi
                            <select id="sb-t-lokasi" class="sb-input" wire:model.live="tLokasi">
                                <option value="">Semua lokasi</option>
                                @foreach ($lokasiOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                            </select>
                        </label>
                        <label for="sb-t-status">Status
                            <select id="sb-t-status" class="sb-input" wire:model.live="tStatus">
                                <option value="">Semua tutor</option>
                                @foreach ($statusTutor as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label for="sb-t-cari">Cari
                            <input id="sb-t-cari" class="sb-input" type="search" placeholder="Nama, lembaga, email, atau HP" wire:model.live.debounce.400ms="tCari" autocomplete="off">
                        </label>
                        <button type="button" class="sb-btn" wire:click="downloadTutor" wire:loading.attr="disabled" wire:target="downloadTutor">
                            <x-heroicon-o-arrow-down-tray /> Unduh CSV ({{ $n($tutorSummary['total']) }} baris)
                        </button>
                        @if ($tutorFiltered)<button type="button" class="sb-link" wire:click="resetTutorFilter">Hapus filter</button>@endif
                    </div>
                    <p class="sb-sum"><strong>{{ $n($tutorSummary['total']) }}</strong> penugasan · <strong>{{ $n($tutorSummary['unik']) }}</strong> tutor · <strong>{{ $n($tutorSummary['tot']) }}</strong> lulus ToT · <strong>{{ $n($tutorSummary['honor']) }}</strong> data honor lengkap</p>
                    <div class="sb-tall">
                        <table class="sb-table">
                            <thead><tr>
                                <th class="sb-left">Nama</th><th class="sb-left">Lokasi</th><th class="sb-left">Lembaga</th><th class="sb-left">Email</th><th class="sb-left">No HP</th><th>Lulus ToT</th><th>Nilai ToT</th><th>Data honor</th>
                            </tr></thead>
                            <tbody>
                                @forelse ($tutor as $r)
                                    <tr wire:key="tutor-{{ $r->id }}">
                                        <td><strong>{{ $r->name }}</strong></td>
                                        <td class="sb-left">{{ $short($r->event_title) }}</td>
                                        <td class="sb-left">{{ $r->institution ?: '–' }}</td>
                                        <td class="sb-left">{{ $r->email ?: '–' }}</td>
                                        <td class="sb-left">{{ $r->phone ?: '–' }}</td>
                                        <td><span @class(['sb-pill', 'sb-yes' => $r->tot_completed, 'sb-no' => ! $r->tot_completed])>{{ $r->tot_completed ? 'ya' : 'tidak' }}</span></td>
                                        <td>{{ $n($r->nilai_tot) }}</td>
                                        <td><span @class(['sb-pill', 'sb-yes' => $r->honor_lengkap, 'sb-warn' => ! $r->honor_lengkap])>{{ $r->honor_lengkap ? 'lengkap' : 'belum lengkap' }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="sb-left">Tidak ada tutor yang cocok dengan filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-filament::pagination :paginator="$tutor" />
                    <p class="eo-muted" style="font-size: 12px;">NIK, NPWP, dan nomor rekening tidak ditampilkan. Kolom "Data honor" hanya menunjukkan apakah ketiganya sudah terisi.</p>
                </div>
            </section>

            {{-- 2.3 --}}
            <section class="sb-chapter" id="k3">
                <h3 class="sb-q"><span class="sb-num">2.3</span>Laporan kegiatan di semua lokus</h3>
                <p class="sb-criteria">Kriteria: laporan kegiatan di semua lokus dengan rata-rata nilai pre-test, rata-rata nilai post-test, dan jumlah microsite yang dibuat siswa.</p>
                <div class="sb-text">
                    <p>Seluruh lokasi telah melaporkan hasil kegiatan. Secara keseluruhan, rata-rata nilai pre-test peserta sebesar <strong>{{ $n($totals['rata_pre'], 1) }}</strong> dan post-test sebesar <strong>{{ $n($totals['rata_post'], 1) }}</strong>, dengan total <strong>{{ $n($totals['microsite']) }}</strong> microsite yang dibuat siswa di <strong>{{ $n($totals['lokasi']) }}</strong> lokasi. Tabel berikut menyajikan rincian per lokasi; angka berwarna merah menandai capaian yang berada di bawah batas penilaian.</p>
                </div>
                <div class="sb-controls">
                    <button type="button" class="sb-btn" wire:click="downloadLaporan" wire:loading.attr="disabled" wire:target="downloadLaporan"><x-heroicon-o-arrow-down-tray /> Unduh CSV ({{ $n($locs->count()) }} lokasi)</button>
                </div>
                <div class="eo-card" style="overflow-x: auto;">
                    <table class="sb-table">
                        <thead>
                            <tr><th>Lokasi</th><th>Tanggal</th><th>Terdaftar</th><th>Hadir %</th><th>Rata-rata Pre-Test</th><th>Rata-rata Post-Test</th><th>Kenaikan per peserta</th><th>Microsite dibuat</th><th>Sertifikat %</th><th>Tutor lulus ToT</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($locs as $r)
                                <tr>
                                    <td><strong>{{ $r['lokasi'] }}</strong></td>
                                    <td>{{ $r['tanggal']?->translatedFormat('d M') ?? '–' }}</td>
                                    <td>{{ $n($r['terdaftar']) }}</td>
                                    <td @class(['sb-low' => $r['pct_hadir'] !== null && $r['pct_hadir'] < $SB::BATAS_HADIR])>{{ $p($r['pct_hadir']) }}</td>
                                    <td>{{ $n($r['rata_pre'], 1) }}</td>
                                    <td>{{ $n($r['rata_post'], 1) }}</td>
                                    <td @class(['sb-low' => $r['kenaikan'] !== null && $r['kenaikan'] < 0])>{{ $signed($r['kenaikan']) }}</td>
                                    <td><strong>{{ $n($r['microsite']) }}</strong></td>
                                    <td @class(['sb-low' => $r['pct_sertifikat'] !== null && $r['pct_sertifikat'] < $SB::BATAS_SERTIFIKAT])>{{ $p($r['pct_sertifikat']) }}</td>
                                    <td>{{ $r['tutor_tot'] }}/{{ $r['tutor'] }}</td>
                                    <td><a href="{{ \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $r['id']]) }}" class="eo-muted" style="font-size: 12px; text-decoration: underline;">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total</td><td></td>
                                <td>{{ $n($totals['terdaftar']) }}</td>
                                <td>{{ $p($totals['pct_hadir']) }}</td>
                                <td>{{ $n($totals['rata_pre'], 1) }}</td>
                                <td>{{ $n($totals['rata_post'], 1) }}</td>
                                <td>{{ $signed($ind['kenaikan']) }}</td>
                                <td>{{ $n($totals['microsite']) }}</td>
                                <td>{{ $p($totals['pct_sertifikat']) }}</td>
                                <td></td><td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="eo-muted" style="font-size: 12px;">Merah: kehadiran di bawah {{ $SB::BATAS_HADIR }}%, sertifikat di bawah {{ $SB::BATAS_SERTIFIKAT }}%, atau nilai turun setelah kegiatan. Rata-rata pre/post-test memakai semua percobaan tes. Laporan kegiatan PDF per lokasi tersedia melalui Detail Lokasi.</p>
            </section>

            {{-- 2.4 --}}
            <section class="sb-chapter" id="k4">
                <h3 class="sb-q"><span class="sb-num">2.4</span>Survei self-efficacy</h3>
                <p class="sb-criteria">Kriteria: pre- and post-workshop self-efficacy survey scores, measuring participants' confidence in using online tools.</p>
                @if ($ind['self_efficacy_dari_skor'])
                    <div class="sb-flag">
                        <span><span class="sb-pill" style="color: var(--eo-danger);">Belum terukur</span></span>
                        <div class="sb-text">
                            <p>Indikator ini belum dapat dilaporkan. Nilai self-efficacy yang tercatat di sistem, yaitu <strong>{{ $n($ind['self_efficacy_pre'], 1) }}</strong> sebelum dan <strong>{{ $n($ind['self_efficacy_post'], 1) }}</strong> sesudah workshop, merupakan salinan nilai kuis dan bukan hasil survei kepercayaan diri peserta. Untuk memenuhi kriteria ini, diperlukan survei self-efficacy berskala 1–5 yang diisi peserta sebelum dan sesudah workshop.</p>
                        </div>
                    </div>
                @else
                    <div class="sb-text">
                        <p>Rata-rata skor self-efficacy peserta tercatat <strong>{{ $n($ind['self_efficacy_pre'], 1) }}</strong> sebelum workshop dan <strong>{{ $n($ind['self_efficacy_post'], 1) }}</strong> sesudah workshop.</p>
                    </div>
                @endif
            </section>

            {{-- 2.5 --}}
            <section class="sb-chapter" id="k5">
                <h3 class="sb-q"><span class="sb-num">2.5</span>Kenaikan nilai kuis literasi digital</h3>
                <p class="sb-criteria">Kriteria: average score improvement on a standardized digital-literacy quiz (security, marketing, content-creation).</p>
                <div class="sb-text">
                    <p>Nilai rata-rata peserta berubah sebesar <strong>{{ $signed($ind['kenaikan']) }}</strong> poin, dihitung dari <strong>{{ $n($ind['pasangan']) }}</strong> peserta yang mengikuti pre-test dan post-test. Sebanyak <strong>{{ $n($ind['tetap']) }}</strong> peserta memperoleh nilai yang sama pada kedua tes, sementara <strong>{{ $p($ind['pre_85']) }}</strong> peserta telah meraih nilai 85 atau lebih sejak pre-test. Kondisi ini menunjukkan bahwa kuis relatif mudah, dengan rata-rata hanya <strong>{{ $ind['soal_per_tes'] }}</strong> soal per tes, dan baru mencakup aspek keamanan digital. Aspek marketing dan content creation belum diukur, sehingga kuis perlu diperluas dengan jumlah soal yang lebih banyak dan tingkat kesulitan yang lebih beragam.</p>
                </div>
                <div class="sb-grid2">
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Sebaran nilai peserta</p>
                        <div class="sb-legend"><span><i style="background: {{ $pre }};"></i>Pre-Test</span><span><i style="background: {{ $post }};"></i>Post-Test</span></div>
                        @php $maxD = max(1, collect($dist)->max(fn ($d) => max($d['pre'], $d['post']))); @endphp
                        <div class="sb-cols" style="margin-top: 22px;">
                            @foreach ($dist as $d)
                                <div class="sb-col" title="Nilai {{ $d['rentang'] }}: pre {{ $n($d['pre']) }}, post {{ $n($d['post']) }} peserta">
                                    <div style="height: {{ $w($d['pre'], $maxD) }}%; background: {{ $pre }};"><span>{{ $n($d['pre']) }}</span></div>
                                    <div style="height: {{ $w($d['post'], $maxD) }}%; background: {{ $post }};"><span style="top: -30px;">{{ $n($d['post']) }}</span></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="sb-col-labels">@foreach ($dist as $d)<span>{{ $d['rentang'] }}</span>@endforeach</div>
                    </div>
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Kenaikan rata-rata per peserta, per lokasi (poin)</p>
                        @php
                            $gains = $locs->whereNotNull('kenaikan');
                            $lo = min(0, (float) $gains->min('kenaikan'));
                            $hi = max(1, (float) $gains->max('kenaikan'));
                            $span = $hi - $lo;
                            $zero = $span > 0 ? (-$lo / $span * 100) : 0;
                        @endphp
                        <div class="sb-rows">
                            @foreach ($gains->sortByDesc('kenaikan') as $r)
                                @php $pos = ($r['kenaikan'] - $lo) / $span * 100; @endphp
                                <div class="sb-row" title="{{ $r['lokasi'] }}: {{ $n($r['pasangan']) }} peserta · naik {{ $r['naik'] }}, tetap {{ $r['tetap'] }}, turun {{ $r['turun'] }}">
                                    <span>{{ $r['lokasi'] }}</span>
                                    <div class="sb-track" style="background: transparent;">
                                        <span class="sb-fill" style="position: absolute; left: {{ min($zero, $pos) }}%; width: {{ abs($pos - $zero) }}%; background: {{ $r['kenaikan'] < 0 ? 'var(--eo-danger)' : $post }}; border-radius: 3px;"></span>
                                        <span class="sb-zero" style="left: {{ $zero }}%;"></span>
                                    </div>
                                    <span @class(['sb-val', 'sb-low' => $r['kenaikan'] < 0])>{{ $signed($r['kenaikan']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- 2.6 --}}
            <section class="sb-chapter" id="k6">
                <h3 class="sb-q"><span class="sb-num">2.6</span>Mengenali phishing dan misinformasi</h3>
                <p class="sb-criteria">Kriteria: percentage of participants correctly identifying and avoiding simulated phishing or misinformation scenarios in a hands-on exercise.</p>
                <div class="sb-text">
                    <p>Pada post-test, <strong>{{ $p($ind['skenario_post']) }}</strong> peserta menjawab benar seluruh soal skenario phishing dan hoaks yang diberikan, dibandingkan <strong>{{ $p($ind['skenario_pre']) }}</strong> pada pre-test. Perlu dicatat bahwa pengukuran ini berbasis soal skenario dalam kuis, seperti SMS hadiah, permintaan kode OTP, email yang mengatasnamakan bank, dan berita viral, serta belum melalui simulasi langsung (hands-on).</p>
                </div>
                <div class="sb-grid2">
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Jawaban benar per tema soal</p>
                        <div class="sb-legend"><span><i style="background: {{ $pre }};"></i>Pre-Test</span><span><i style="background: {{ $post }};"></i>Post-Test</span></div>
                        <div style="display: grid; gap: 14px;">
                            @foreach ($themes as $t)
                                <div style="display: grid; gap: 4px;">
                                    <span style="font-size: 13px; font-weight: 700; color: var(--eo-text);">{{ $t['tema'] }}</span>
                                    @foreach (['pre' => $pre, 'post' => $post] as $k => $color)
                                        <div class="sb-row" style="grid-template-columns: 70px 1fr 64px;">
                                            <span class="eo-muted" style="font-size: 12px;">{{ $k === 'pre' ? 'Pre-Test' : 'Post-Test' }}</span>
                                            <div class="sb-track"><span class="sb-fill" style="width: {{ $w($t[$k], 100) }}%; background: {{ $color }};"></span></div>
                                            <span class="sb-val">{{ $p($t[$k]) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Peserta benar di semua soal skenario, per lokasi</p>
                        <div class="sb-legend"><span><i style="background: {{ $pre }};"></i>Pre-Test</span><span><i style="background: {{ $post }};"></i>Post-Test</span><span><i style="background: var(--eo-danger);"></i>Turun</span></div>
                        @php
                            $sc = $locs->whereNotNull('skenario_post');
                            $low = max(0, floor(min((float) $sc->min('skenario_pre'), (float) $sc->min('skenario_post')) / 5) * 5 - 5);
                            $pos = fn ($v) => (100 - $low) > 0 ? (($v ?? $low) - $low) / (100 - $low) * 100 : 0;
                        @endphp
                        <div class="sb-rows">
                            @foreach ($sc->sortByDesc('skenario_post') as $r)
                                @php $a = $pos($r['skenario_pre']); $b = $pos($r['skenario_post']); @endphp
                                <div class="sb-row" title="{{ $r['lokasi'] }}: pre {{ $p($r['skenario_pre']) }} → post {{ $p($r['skenario_post']) }}">
                                    <span>{{ $r['lokasi'] }}</span>
                                    <div class="sb-track" style="background: transparent;">
                                        <span class="sb-seg" style="left: {{ min($a, $b) }}%; width: {{ abs($b - $a) }}%; background: {{ $r['skenario_post'] < $r['skenario_pre'] ? 'var(--eo-danger)' : 'var(--eo-muted)' }};"></span>
                                        <span class="sb-dot" style="left: {{ $a }}%; background: {{ $pre }};"></span>
                                        <span class="sb-dot" style="left: {{ $b }}%; background: {{ $post }};"></span>
                                    </div>
                                    <span class="sb-val">{{ $p($r['skenario_post']) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="eo-muted" style="font-size: 12px; margin-top: 8px;">Skala {{ $low }}–100%.</p>
                    </div>
                </div>
            </section>
        </div>

        {{-- ============================== BAB 3 ============================== --}}
        <div class="sb-part" id="bab-3">
            <div>
                <div class="sb-eyebrow">Bab 3</div>
                <h2 class="sb-part-title">Kesimpulan dan tindak lanjut</h2>
            </div>
            <section class="sb-chapter">
                <div class="sb-text">
                    <p>Secara umum, Program Digital Safety Champions telah terlaksana dengan capaian jangkauan, kehadiran, dan penyelesaian yang baik. Peserta menunjukkan pemahaman keamanan digital yang tinggi, baik sebelum maupun sesudah kegiatan.</p>
                    <p>Meski demikian, terdapat beberapa hal yang memerlukan tindak lanjut manajemen. @if ($ind['self_efficacy_dari_skor'])Pertama, survei self-efficacy perlu disusun secara terpisah agar kriteria 2.4 dapat dilaporkan. Kedua, data honor tutor perlu dilengkapi sebelum pembayaran. Ketiga, @else Pertama, data honor tutor perlu dilengkapi sebelum pembayaran. Kedua, @endif instrumen kuis perlu diperluas agar peningkatan pengetahuan serta aspek marketing dan content creation dapat terukur. Selain itu, lokasi dengan capaian di bawah batas penilaian perlu ditindaklanjuti sesuai rincian berikut.</p>
                </div>
                <ul class="sb-list">
                    @forelse ($attention as $a)
                        <li>
                            <div style="display: grid; gap: 4px;"><strong style="color: var(--eo-text);">{{ $a['lokasi'] }}</strong><span><span class="sb-pill" style="color: var(--eo-{{ $a['level'] }});">{{ $a['isu'] }}</span></span></div>
                            <div style="color: var(--eo-text);">{{ $a['detail'] }}</div>
                        </li>
                    @empty
                        <li><div>Tidak ada lokasi di bawah batas penilaian.</div></li>
                    @endforelse
                </ul>
                <details class="sb-details">
                    <summary>Cara membaca angka di halaman ini</summary>
                    <div class="eo-muted" style="display: grid; gap: 6px; margin-top: 8px;">
                        <p><strong>Kehadiran</strong> dihitung dari peserta yang tercatat "hadir" dibagi peserta terdaftar, dan dapat melebihi 100% bila ada peserta hadir yang terdaftar di event lain.</p>
                        <p><strong>Kenaikan per peserta</strong> adalah selisih nilai post-test tertinggi dan pre-test tertinggi milik peserta yang sama di lokasi yang sama.</p>
                        <p><strong>Soal skenario</strong> adalah soal tentang phishing, penipuan online, dan hoaks. Tema ditentukan dari isi soal, bukan dari judul modul.</p>
                    </div>
                </details>
            </section>
        </div>
    </div>
</x-filament-panels::page>
