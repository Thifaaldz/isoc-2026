@php
    $SB = \App\Support\ProgramStoryboard::class;
    $n = fn ($v, int $d = 0) => $v === null ? '–' : number_format((float) $v, $d, ',', '.');
    $p = fn ($v) => $v === null ? '–' : number_format((float) $v, 1, ',', '.') . '%';
    $signed = fn ($v) => $v === null ? '–' : ($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '.');
    $w = fn ($v, $max) => $max > 0 ? max(0, min(100, round((float) $v / $max * 100, 1))) : 0;
    $tgl = fn ($d) => $d ? $d->locale('id')->translatedFormat('d F Y') : '–';
    $locs = $locations->sortBy(fn ($r) => $r['tanggal']?->timestamp ?? 0)->values();
    $first = $locs->first()['tanggal'] ?? null;
    $last = $locs->last()['tanggal'] ?? null;
    $peserta = collect($comp['peserta_items'])->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
    $tutorItem = collect($comp['tutor_items'])->mapWithKeys(fn ($i) => [$i[0] => $i[1]]);
    $gender = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    $short = fn ($t) => $SB::shortName((string) $t);
    $logo = is_file(public_path('images/isoc-logo.png')) ? 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('images/isoc-logo.png'))) : null;
    // Titik tren mingguan (tiap 7 hari + hari terakhir) agar tabel ringkas.
    $trendIdx = collect(range(0, max(0, count($trend['labels']) - 1)))->filter(fn ($i) => $i % 7 === 0 || $i === count($trend['labels']) - 1)->values();
    $lampiran = collect([
        ['Metodologi dan definisi', true],
        ['Daftar tutor per lokus', $tutorList !== null],
        ['Daftar peserta per lokus', $participantList !== null],
    ])->filter(fn ($l) => $l[1])->values();
    $huruf = range('A', 'Z');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Akhir Program Digital Safety Champions</title>
    <style>
        @page { margin: 24mm 18mm 20mm 18mm; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9.6pt; line-height: 1.55; margin: 0; }
        .header { border-bottom: 0.4pt solid #c7d2fe; color: #6366f1; font-size: 7.5pt; left: 0; letter-spacing: .5pt; padding-bottom: 1.5mm; position: fixed; right: 0; text-transform: uppercase; top: -15mm; }
        .header span { color: #64748b; float: right; letter-spacing: 0; text-transform: none; }
        .footer { border-top: 0.4pt solid #e2e8f0; bottom: -12mm; color: #64748b; font-size: 7.5pt; left: 0; padding-top: 1.5mm; position: fixed; right: 0; }
        h1, h2, h3, h4 { color: #0f172a; margin: 0; }
        h2.bab { border-bottom: 1.2pt solid #4338ca; color: #1e1b4b; font-size: 15pt; margin: 0 0 4mm; padding-bottom: 2mm; }
        h2.bab small { color: #6366f1; display: block; font-size: 8pt; font-weight: 700; letter-spacing: 1.5pt; margin-bottom: 1mm; text-transform: uppercase; }
        h3.sub { color: #1e1b4b; font-size: 11.5pt; margin: 7mm 0 2mm; }
        h3.sub .no { background: #4338ca; border-radius: 2mm; color: #fff; font-size: 8.5pt; margin-right: 2mm; padding: 0.6mm 2mm; }
        p { margin: 0 0 2.6mm; text-align: justify; }
        p.lead { color: #334155; font-size: 9pt; }
        .criteria { background: #f1f5f9; border-left: 2pt solid #94a3b8; color: #475569; font-size: 8.4pt; font-style: italic; margin: 0 0 3mm; padding: 2mm 3mm; }
        .criteria b { color: #0f172a; font-style: normal; letter-spacing: .5pt; margin-right: 1.5mm; text-transform: uppercase; }
        .flag { background: #fef2f2; border: 0.6pt solid #fca5a5; border-radius: 2mm; margin: 2mm 0 3mm; padding: 2.5mm 3.5mm; }
        .flag .label { color: #b91c1c; font-size: 7.5pt; font-weight: 700; letter-spacing: .6pt; text-transform: uppercase; }
        table { border-collapse: collapse; margin: 2mm 0 4mm; width: 100%; }
        th, td { border-bottom: 0.4pt solid #e2e8f0; line-height: 1.3; padding: 1.2mm 2mm; text-align: left; vertical-align: middle; }
        thead th { background: #eef2ff; border-bottom: 0.8pt solid #a5b4fc; color: #312e81; font-size: 7.6pt; font-weight: 700; letter-spacing: .3pt; text-transform: uppercase; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        tfoot td { background: #eef2ff; border-top: 0.8pt solid #a5b4fc; font-weight: 700; }
        .num { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .small, .small td, .small th { font-size: 7.8pt; }
        .tiny td, .tiny th { font-size: 7pt; padding: 0.9mm 1.4mm; }
        .low { color: #b91c1c; font-weight: 700; }
        .muted { color: #64748b; }
        .note { color: #64748b; font-size: 7.8pt; margin-top: -2mm; }
        .bar { background: #e2e8f0; border-radius: 1mm; height: 2.2mm; width: 100%; }
        .bar div { background: #4f46e5; border-radius: 1mm; height: 2.2mm; }
        .bar div.warn { background: #d97706; }
        .bar div.bad { background: #dc2626; }
        .bar div.pre { background: #94a3b8; }
        .bar div.ok { background: #16a34a; }
        .kpi { border-collapse: separate; border-spacing: 2.4mm 0; margin: 3mm -2.4mm 5mm; width: auto; }
        .kpi td { background: #f8fafc; border: 0.6pt solid #e2e8f0; border-radius: 2mm; padding: 2.6mm 3mm; vertical-align: top; width: 20%; }
        .kpi .v { color: #1e1b4b; font-size: 15pt; font-weight: 700; line-height: 1.15; }
        .kpi .l { color: #64748b; font-size: 7.4pt; margin-top: 1mm; }
        .page-break { page-break-before: always; }
        .avoid { page-break-inside: avoid; }
        /* Sampul */
        .cover { height: 230mm; position: relative; }
        .cover .band { background: #1e1b4b; height: 4mm; margin-bottom: 30mm; }
        .cover .logo { height: 16mm; }
        .cover .kind { color: #6366f1; font-size: 10pt; font-weight: 700; letter-spacing: 2pt; margin-top: 26mm; text-transform: uppercase; }
        .cover h1 { color: #1e1b4b; font-size: 26pt; line-height: 1.15; margin-top: 4mm; }
        .cover .program { color: #334155; font-size: 11.5pt; line-height: 1.5; margin-top: 6mm; width: 78%; }
        .cover .meta { border-top: 0.8pt solid #c7d2fe; bottom: 0; color: #475569; font-size: 9pt; left: 0; padding-top: 4mm; position: absolute; right: 0; }
        .cover .meta table { margin: 0; }
        .cover .meta td { border: 0; padding: 0.8mm 0; }
        .cover .meta td:first-child { color: #64748b; width: 42mm; }
        .toc { margin-top: 4mm; }
        .toc td { background: none !important; border-bottom: 0.4pt dotted #cbd5e1; font-size: 10pt; padding: 2.2mm 0; }
        .toc td.lvl2 { color: #475569; font-size: 9.5pt; padding-left: 7mm; }
        .toc .grp td { border-bottom: 0.6pt solid #c7d2fe; color: #1e1b4b; font-weight: 700; padding-top: 4.5mm; }
    </style>
</head>
<body>
    <div class="header">Laporan Akhir · Digital Safety Champions<span>{{ $generatedAt->locale('id')->translatedFormat('d F Y') }}</span></div>
    <div class="footer">Disusun otomatis dari sistem ISOC pada {{ $generatedAt->locale('id')->translatedFormat('d F Y, H.i') }} WIB</div>

    {{-- ============================== SAMPUL ============================== --}}
    <div class="cover">
        <div class="band"></div>
        @if ($logo)<img class="logo" src="{{ $logo }}" alt="ISOC">@endif
        <div class="kind">Laporan Akhir Program</div>
        <h1>Digital Safety Champions</h1>
        <div class="program">Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia</div>
        <div class="meta">
            <table>
                <tr><td>Periode pelaksanaan</td><td>{{ $tgl($first) }} – {{ $tgl($last) }}</td></tr>
                <tr><td>Cakupan</td><td>{{ $n($totals['lokasi']) }} lokasi · {{ $n($totals['terdaftar']) }} peserta terdaftar · {{ $n($comp['tutor']) }} penugasan tutor</td></tr>
                <tr><td>Sumber data</td><td>Sistem ISOC, per {{ $generatedAt->locale('id')->translatedFormat('d F Y') }}</td></tr>
                @if ($filterNote)<tr><td>Lampiran</td><td>{{ $filterNote }}</td></tr>@endif
            </table>
        </div>
    </div>

    {{-- ============================== DAFTAR ISI ============================== --}}
    <div class="page-break"></div>
    <h2 class="bab">Daftar isi</h2>
    <table class="toc">
        <tr class="grp"><td colspan="2">Ringkasan eksekutif</td></tr>
        <tr class="grp"><td colspan="2">Bab 1 · Gambaran pelaksanaan</td></tr>
        <tr><td class="lvl2">1.1 Pencapaian target peserta</td></tr>
        <tr><td class="lvl2">1.2 Tingkat penyelesaian rangkaian kegiatan</td></tr>
        <tr><td class="lvl2">1.3 Perkembangan pelaksanaan</td></tr>
        <tr class="grp"><td colspan="2">Bab 2 · Capaian kriteria pelaporan</td></tr>
        <tr><td class="lvl2">2.1 Data peserta terdata</td></tr>
        <tr><td class="lvl2">2.2 Data tutor terdata</td></tr>
        <tr><td class="lvl2">2.3 Laporan kegiatan di semua lokus</td></tr>
        <tr><td class="lvl2">2.4 Survei self-efficacy</td></tr>
        <tr><td class="lvl2">2.5 Kenaikan nilai kuis literasi digital</td></tr>
        <tr><td class="lvl2">2.6 Mengenali phishing dan misinformasi</td></tr>
        <tr class="grp"><td colspan="2">Bab 3 · Kesimpulan dan tindak lanjut</td></tr>
        <tr class="grp"><td colspan="2">Lampiran</td></tr>
        @foreach ($lampiran as $i => [$judul])
            <tr><td class="lvl2">Lampiran {{ $huruf[$i] }} · {{ $judul }}</td></tr>
        @endforeach
    </table>

    {{-- ============================== RINGKASAN EKSEKUTIF ============================== --}}
    <div class="page-break"></div>
    <h2 class="bab"><small>Ringkasan</small>Ringkasan eksekutif</h2>
    <p>Program Digital Safety Champions telah dilaksanakan di {{ $n($totals['lokasi']) }} lokasi pada periode {{ $tgl($first) }} hingga {{ $tgl($last) }}, dan {{ $n($totals['laporan_disetujui']) }} laporan final telah disetujui. Program menjangkau {{ $n($totals['terdaftar']) }} peserta terdaftar atau {{ $p($totals['pct_target']) }} dari target {{ $n($totals['target']) }} peserta, dengan tingkat kehadiran {{ $p($totals['pct_hadir']) }}. Hingga laporan ini disusun, {{ $n($totals['sertifikat']) }} peserta ({{ $p($totals['pct_sertifikat']) }}) telah menerima e-sertifikat dan {{ $n($totals['microsite']) }} microsite berhasil dibuat oleh siswa.</p>
    <p>Dari sisi hasil belajar, rata-rata nilai post-test mencapai {{ $n($totals['rata_post'], 1) }} dibandingkan {{ $n($totals['rata_pre'], 1) }} pada pre-test, dengan kenaikan rata-rata per peserta sebesar {{ $signed($ind['kenaikan']) }} poin{{ ($ind['pre_85'] ?? 0) >= 60 ? '. Kenaikan yang terbatas ini disebabkan tingginya pengetahuan awal peserta' : '' }}. Pada post-test, {{ $p($ind['skenario_post']) }} peserta menjawab benar seluruh soal skenario phishing dan hoaks. Hal yang masih memerlukan perhatian adalah {{ $ind['self_efficacy_dari_skor'] ? 'indikator self-efficacy yang belum terukur secara terpisah dan ' : '' }}data honor tutor yang baru lengkap pada {{ $n($comp['honor']) }} dari {{ $n($comp['tutor']) }} penugasan.</p>
    <table class="kpi">
        <tr>
            <td><div class="v">{{ $p($totals['pct_target']) }}</div><div class="l">Terdaftar dari target<br>{{ $n($totals['terdaftar']) }} / {{ $n($totals['target']) }}</div></td>
            <td><div class="v">{{ $p($totals['pct_hadir']) }}</div><div class="l">Kehadiran<br>{{ $n($totals['hadir']) }} peserta</div></td>
            <td><div class="v">{{ $n($totals['sertifikat']) }}</div><div class="l">E-sertifikat terbit<br>{{ $p($totals['pct_sertifikat']) }} dari terdaftar</div></td>
            <td><div class="v">{{ $n($totals['rata_post'], 1) }}</div><div class="l">Rata-rata post-test<br>pre-test {{ $n($totals['rata_pre'], 1) }}</div></td>
            <td><div class="v">{{ $p($ind['skenario_post']) }}</div><div class="l">Benar semua soal<br>phishing &amp; hoaks</div></td>
        </tr>
    </table>
    <table class="small avoid">
        <thead><tr><th style="width: 44%;">Kriteria pelaporan</th><th style="width: 28%;">Capaian</th><th>Status</th></tr></thead>
        <tbody>
            <tr><td>2.1 Data peserta terdata</td><td>Sekolah {{ $p($peserta['Asal sekolah'] ?? null) }}, email {{ $p($peserta['Email'] ?? null) }}, HP {{ $p($peserta['Nomor HP'] ?? null) }}</td><td>{{ min($peserta['Asal sekolah'] ?? 0, $peserta['Email'] ?? 0, $peserta['Nomor HP'] ?? 0) >= 95 ? 'Terpenuhi' : 'Perlu dilengkapi' }}</td></tr>
            <tr><td>2.2 Data tutor terdata</td><td>Lulus ToT {{ $p($tutorItem['Lulus ToT'] ?? null) }}; data honor {{ $n($comp['honor']) }}/{{ $n($comp['tutor']) }}</td><td>{{ $comp['honor'] >= $comp['tutor'] ? 'Terpenuhi' : 'Data honor perlu dilengkapi' }}</td></tr>
            <tr><td>2.3 Laporan kegiatan di semua lokus</td><td>Pre {{ $n($totals['rata_pre'], 1) }} · post {{ $n($totals['rata_post'], 1) }} · {{ $n($totals['microsite']) }} microsite</td><td>{{ $totals['laporan_disetujui'] >= $totals['lokasi'] ? 'Terpenuhi' : 'Sebagian laporan belum disetujui' }}</td></tr>
            <tr><td>2.4 Survei self-efficacy</td><td>{{ $n($ind['self_efficacy_pre'], 1) }} → {{ $n($ind['self_efficacy_post'], 1) }}</td><td class="{{ $ind['self_efficacy_dari_skor'] ? 'low' : '' }}">{{ $ind['self_efficacy_dari_skor'] ? 'Belum terukur' : 'Terpenuhi' }}</td></tr>
            <tr><td>2.5 Kenaikan nilai kuis</td><td>{{ $signed($ind['kenaikan']) }} poin ({{ $n($ind['pasangan']) }} peserta)</td><td>Terukur; cakupan kuis perlu diperluas</td></tr>
            <tr><td>2.6 Mengenali phishing dan misinformasi</td><td>{{ $p($ind['skenario_post']) }} benar semua (pre {{ $p($ind['skenario_pre']) }})</td><td>Terukur melalui soal skenario</td></tr>
        </tbody>
    </table>

    {{-- ============================== BAB 1 ============================== --}}
    <div class="page-break"></div>
    <h2 class="bab"><small>Bab 1</small>Gambaran pelaksanaan</h2>
    <p class="lead">Bab ini menggambarkan jangkauan, tingkat penyelesaian, dan perkembangan pelaksanaan program di seluruh lokasi.</p>

    <h3 class="sub"><span class="no">1.1</span>Pencapaian target peserta</h3>
    <p>Sebanyak {{ $reach['n95'] }} dari {{ $reach['total'] }} lokasi berhasil memenuhi sekurang-kurangnya 95% kuota peserta{{ $reach['n95'] >= $reach['total'] / 2 ? ', sehingga target jangkauan secara umum tercapai' : '' }}.@if ($reach['min']) Capaian terendah tercatat di {{ $reach['min']['lokasi'] }} sebesar {{ $p($reach['min']['pct_target']) }} dari target.@endif Lokasi dengan capaian di bawah {{ $SB::BATAS_KUOTA }}% perlu dievaluasi strategi rekrutmennya untuk pelaksanaan berikutnya.</p>
    @php $maxT = max(100, (float) $locs->max('pct_target')); @endphp
    <table class="small">
        <thead><tr><th style="width: 24%;">Lokasi</th><th class="num" style="width: 11%;">Target</th><th class="num" style="width: 11%;">Terdaftar</th><th style="width: 40%;">Capaian terhadap target</th><th class="num">%</th></tr></thead>
        <tbody>
            @foreach ($locs->sortByDesc('pct_target') as $r)
                <tr>
                    <td>{{ $r['lokasi'] }}</td>
                    <td class="num">{{ $n($r['target']) }}</td>
                    <td class="num">{{ $n($r['terdaftar']) }}</td>
                    <td><div class="bar"><div class="{{ $r['pct_target'] < $SB::BATAS_KUOTA ? 'warn' : '' }}" style="width: {{ $w($r['pct_target'], $maxT) }}%;"></div></div></td>
                    <td class="num {{ $r['pct_target'] < $SB::BATAS_KUOTA ? 'low' : '' }}">{{ $p($r['pct_target']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total</td><td class="num">{{ $n($totals['target']) }}</td><td class="num">{{ $n($totals['terdaftar']) }}</td><td></td><td class="num">{{ $p($totals['pct_target']) }}</td></tr></tfoot>
    </table>

    <div class="avoid">
        <h3 class="sub"><span class="no">1.2</span>Tingkat penyelesaian rangkaian kegiatan</h3>
        <p>Dari seluruh peserta terdaftar, {{ $p($funnel['bertahan']) }} menyelesaikan rangkaian kegiatan hingga menerima e-sertifikat.@if ($funnel['turun_ke']) Penurunan jumlah peserta terbesar terjadi pada tahap {{ $funnel['turun_dari'] }} ke {{ $funnel['turun_ke'] }}, yaitu sebanyak {{ $n($funnel['turun']) }} peserta. Upaya peningkatan penyelesaian akan paling efektif bila difokuskan pada tahap tersebut.@endif</p>
        @php $maxF = max(1, $funnel['steps'][0]['value'] ?? 1); @endphp
        <table class="small">
            <thead><tr><th style="width: 26%;">Tahap</th><th class="num" style="width: 12%;">Peserta</th><th style="width: 38%;"></th><th class="num">Dari terdaftar</th><th class="num">Turun</th></tr></thead>
            <tbody>
                @foreach ($funnel['steps'] as $step)
                    <tr>
                        <td>{{ $step['label'] }}</td>
                        <td class="num">{{ $n($step['value']) }}</td>
                        <td><div class="bar"><div style="width: {{ $w($step['value'], $maxF) }}%;"></div></div></td>
                        <td class="num">{{ $p($step['pct']) }}</td>
                        <td class="num">{{ $loop->first || ! $step['turun'] ? '–' : '−' . $n($step['turun']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="note">Peserta lengkap adalah peserta yang telah mengerjakan pre-test dan post-test, bergabung di WhatsApp Group, dan mengikuti Instagram program.</p>
    </div>

    <div class="avoid">
        <h3 class="sub"><span class="no">1.3</span>Perkembangan pelaksanaan</h3>
        <p>Jumlah pendaftar, kehadiran, penyelesaian post-test, dan penerbitan e-sertifikat meningkat secara bertahap seiring jadwal pelaksanaan di setiap lokasi. Tabel berikut menyajikan jumlah kumulatif per minggu; selisih antara jumlah terdaftar dan sertifikat terbit pada akhir periode menggambarkan peserta yang belum menuntaskan seluruh rangkaian kegiatan.</p>
        <table class="small">
            <thead><tr><th>Tanggal</th>@foreach (array_keys($trend['series']) as $name)<th class="num">{{ $name }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($trendIdx as $i)
                    <tr><td>{{ $trend['labels'][$i] }}</td>@foreach ($trend['series'] as $values)<td class="num">{{ $n($values[$i] ?? null) }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ============================== BAB 2 ============================== --}}
    <div class="page-break"></div>
    <h2 class="bab"><small>Bab 2</small>Capaian kriteria pelaporan</h2>
    <p class="lead">Bab ini memaparkan capaian program terhadap enam kriteria pelaporan.</p>

    <div class="avoid">
    <h3 class="sub"><span class="no">2.1</span>Data peserta terdata</h3>
    <div class="criteria"><b>Kriteria</b>Data peserta terdata, termasuk informasi asal sekolah, email, dan nomor HP.</div>
    <p>Dari {{ $n($comp['peserta']) }} data pendaftaran, kelengkapan informasi asal sekolah mencapai {{ $p($peserta['Asal sekolah'] ?? null) }}, email {{ $p($peserta['Email'] ?? null) }}, dan nomor HP {{ $p($peserta['Nomor HP'] ?? null) }}. Dengan tingkat kelengkapan tersebut, data peserta telah memenuhi kriteria dan siap dilaporkan.{{ $participantList !== null ? ' Daftar peserta per lokus disajikan pada lampiran.' : '' }}</p>
    </div>
    <table class="small avoid">
        <thead><tr><th style="width: 30%;">Data</th><th style="width: 55%;">Kelengkapan</th><th class="num">%</th></tr></thead>
        <tbody>
            @foreach ($comp['peserta_items'] as [$label, $v])
                <tr><td>{{ $label }}</td><td><div class="bar"><div class="{{ $v >= 95 ? 'ok' : ($v >= 70 ? 'warn' : 'bad') }}" style="width: {{ $w($v, 100) }}%;"></div></div></td><td class="num">{{ $p($v) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="avoid">
    <h3 class="sub"><span class="no">2.2</span>Data tutor terdata</h3>
    <div class="criteria"><b>Kriteria</b>Data tutor terdata, termasuk informasi detail tutor.</div>
    <p>Tutor yang bertugas telah dibekali melalui ToT dengan tingkat kelulusan {{ $p($tutorItem['Lulus ToT'] ?? null) }}. Kelengkapan data honor yang meliputi NIK, NPWP, dan nomor rekening baru tercapai pada {{ $n($comp['honor']) }} dari {{ $n($comp['tutor']) }} penugasan. Data tersebut perlu dilengkapi sebelum proses pembayaran honor dilakukan.{{ $tutorList !== null ? ' Daftar tutor per lokus disajikan pada lampiran.' : '' }}</p>
    </div>
    <table class="small avoid">
        <thead><tr><th style="width: 30%;">Data</th><th style="width: 55%;">Kelengkapan (per penugasan)</th><th class="num">%</th></tr></thead>
        <tbody>
            @foreach ($comp['tutor_items'] as [$label, $v])
                <tr><td>{{ $label }}</td><td><div class="bar"><div class="{{ $v >= 95 ? 'ok' : ($v >= 70 ? 'warn' : 'bad') }}" style="width: {{ $w($v, 100) }}%;"></div></div></td><td class="num">{{ $p($v) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="avoid">
    <h3 class="sub"><span class="no">2.3</span>Laporan kegiatan di semua lokus</h3>
    <div class="criteria"><b>Kriteria</b>Laporan kegiatan di semua lokus dengan rata-rata nilai pre-test, rata-rata nilai post-test, dan jumlah microsite yang dibuat siswa.</div>
    <p>Seluruh lokasi telah melaporkan hasil kegiatan. Secara keseluruhan, rata-rata nilai pre-test peserta sebesar {{ $n($totals['rata_pre'], 1) }} dan post-test sebesar {{ $n($totals['rata_post'], 1) }}, dengan total {{ $n($totals['microsite']) }} microsite yang dibuat siswa di {{ $n($totals['lokasi']) }} lokasi. Tabel berikut menyajikan rincian per lokasi; angka berwarna merah menandai capaian di bawah batas penilaian (kehadiran atau sertifikat di bawah {{ $SB::BATAS_HADIR }}%, atau nilai yang turun setelah kegiatan).</p>
    </div>
    <table class="tiny" style="table-layout: fixed;">
        <thead><tr><th style="width: 17%;">Lokasi</th><th style="width: 8%;">Tgl</th><th class="num" style="width: 9%;">Daftar</th><th class="num" style="width: 9%;">Hadir</th><th class="num" style="width: 9%;">Pre</th><th class="num" style="width: 9%;">Post</th><th class="num" style="width: 9%;">Naik</th><th class="num" style="width: 10%;">Microsite</th><th class="num" style="width: 11%;">Sertifikat</th><th class="num" style="width: 9%;">ToT</th></tr></thead>
        <tbody>
            @foreach ($locs as $r)
                <tr>
                    <td>{{ $r['lokasi'] }}</td>
                    <td>{{ $r['tanggal']?->locale('id')->translatedFormat('d M') ?? '–' }}</td>
                    <td class="num">{{ $n($r['terdaftar']) }}</td>
                    <td class="num {{ $r['pct_hadir'] !== null && $r['pct_hadir'] < $SB::BATAS_HADIR ? 'low' : '' }}">{{ $p($r['pct_hadir']) }}</td>
                    <td class="num">{{ $n($r['rata_pre'], 1) }}</td>
                    <td class="num">{{ $n($r['rata_post'], 1) }}</td>
                    <td class="num {{ $r['kenaikan'] !== null && $r['kenaikan'] < 0 ? 'low' : '' }}">{{ $signed($r['kenaikan']) }}</td>
                    <td class="num">{{ $n($r['microsite']) }}</td>
                    <td class="num {{ $r['pct_sertifikat'] !== null && $r['pct_sertifikat'] < $SB::BATAS_SERTIFIKAT ? 'low' : '' }}">{{ $p($r['pct_sertifikat']) }}</td>
                    <td class="num">{{ $r['tutor_tot'] }}/{{ $r['tutor'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr><td>Total</td><td></td><td class="num">{{ $n($totals['terdaftar']) }}</td><td class="num">{{ $p($totals['pct_hadir']) }}</td><td class="num">{{ $n($totals['rata_pre'], 1) }}</td><td class="num">{{ $n($totals['rata_post'], 1) }}</td><td class="num">{{ $signed($ind['kenaikan']) }}</td><td class="num">{{ $n($totals['microsite']) }}</td><td class="num">{{ $p($totals['pct_sertifikat']) }}</td><td></td></tr></tfoot>
    </table>
    <p class="note">Rata-rata pre/post-test dihitung dari semua percobaan tes. Kenaikan dihitung per peserta dari nilai tertinggi post-test dikurangi nilai tertinggi pre-test.</p>

    <div class="avoid">
        <h3 class="sub"><span class="no">2.4</span>Survei self-efficacy</h3>
        <div class="criteria"><b>Kriteria</b>Pre- and post-workshop self-efficacy survey scores, measuring participants' confidence in using online tools.</div>
        @if ($ind['self_efficacy_dari_skor'])
            <div class="flag">
                <div class="label">Belum terukur</div>
                <p style="margin: 1mm 0 0;">Indikator ini belum dapat dilaporkan. Nilai self-efficacy yang tercatat di sistem, yaitu {{ $n($ind['self_efficacy_pre'], 1) }} sebelum dan {{ $n($ind['self_efficacy_post'], 1) }} sesudah workshop, merupakan salinan nilai kuis dan bukan hasil survei kepercayaan diri peserta. Untuk memenuhi kriteria ini, diperlukan survei self-efficacy berskala 1–5 yang diisi peserta sebelum dan sesudah workshop.</p>
            </div>
        @else
            <p>Rata-rata skor self-efficacy peserta tercatat {{ $n($ind['self_efficacy_pre'], 1) }} sebelum workshop dan {{ $n($ind['self_efficacy_post'], 1) }} sesudah workshop.</p>
        @endif
    </div>

    <div class="avoid">
        <h3 class="sub"><span class="no">2.5</span>Kenaikan nilai kuis literasi digital</h3>
        <div class="criteria"><b>Kriteria</b>Average score improvement on a standardized digital-literacy quiz (security, marketing, content-creation).</div>
        <p>Nilai rata-rata peserta berubah sebesar {{ $signed($ind['kenaikan']) }} poin, dihitung dari {{ $n($ind['pasangan']) }} peserta yang mengikuti pre-test dan post-test. Sebanyak {{ $n($ind['naik']) }} peserta mengalami kenaikan nilai, {{ $n($ind['tetap']) }} peserta memperoleh nilai yang sama, dan {{ $n($ind['turun']) }} peserta mengalami penurunan. Sebanyak {{ $p($ind['pre_85']) }} peserta telah meraih nilai 85 atau lebih sejak pre-test, sehingga ruang kenaikan nilai terbatas. Kondisi ini menunjukkan bahwa kuis relatif mudah, dengan rata-rata hanya {{ $ind['soal_per_tes'] }} soal per tes, dan baru mencakup aspek keamanan digital. Aspek marketing dan content creation belum diukur, sehingga kuis perlu diperluas dengan jumlah soal yang lebih banyak dan tingkat kesulitan yang lebih beragam.</p>
        @php $maxD = max(1, collect($dist)->max(fn ($d) => max($d['pre'], $d['post']))); @endphp
        <table class="small">
            <thead><tr><th style="width: 14%;">Rentang nilai</th><th class="num" style="width: 10%;">Pre-test</th><th style="width: 28%;"></th><th class="num" style="width: 10%;">Post-test</th><th style="width: 28%;"></th></tr></thead>
            <tbody>
                @foreach ($dist as $d)
                    <tr>
                        <td>{{ $d['rentang'] }}</td>
                        <td class="num">{{ $n($d['pre']) }}</td><td><div class="bar"><div class="pre" style="width: {{ $w($d['pre'], $maxD) }}%;"></div></div></td>
                        <td class="num">{{ $n($d['post']) }}</td><td><div class="bar"><div style="width: {{ $w($d['post'], $maxD) }}%;"></div></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @php $gains = $locs->whereNotNull('kenaikan')->sortByDesc('kenaikan'); @endphp
    <table class="tiny avoid">
        <thead><tr><th>Lokasi</th><th class="num">Peserta (pre &amp; post)</th><th class="num">Naik</th><th class="num">Tetap</th><th class="num">Turun</th><th class="num">Kenaikan rata-rata (poin)</th></tr></thead>
        <tbody>
            @foreach ($gains as $r)
                <tr><td>{{ $r['lokasi'] }}</td><td class="num">{{ $n($r['pasangan']) }}</td><td class="num">{{ $n($r['naik']) }}</td><td class="num">{{ $n($r['tetap']) }}</td><td class="num">{{ $n($r['turun']) }}</td><td class="num {{ $r['kenaikan'] < 0 ? 'low' : '' }}">{{ $signed($r['kenaikan']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="avoid">
        <h3 class="sub"><span class="no">2.6</span>Mengenali phishing dan misinformasi</h3>
        <div class="criteria"><b>Kriteria</b>Percentage of participants correctly identifying and avoiding simulated phishing or misinformation scenarios in a hands-on exercise.</div>
        <p>Pada post-test, {{ $p($ind['skenario_post']) }} peserta menjawab benar seluruh soal skenario phishing dan hoaks yang diberikan, dibandingkan {{ $p($ind['skenario_pre']) }} pada pre-test. Perlu dicatat bahwa pengukuran ini berbasis soal skenario dalam kuis, seperti SMS hadiah, permintaan kode OTP, email yang mengatasnamakan bank, dan berita viral, serta belum melalui simulasi langsung (hands-on).</p>
        <table class="small">
            <thead><tr><th style="width: 32%;">Tema soal</th><th class="num" style="width: 12%;">Pre-test</th><th style="width: 22%;"></th><th class="num" style="width: 12%;">Post-test</th><th style="width: 22%;"></th></tr></thead>
            <tbody>
                @foreach ($themes as $t)
                    <tr><td>{{ $t['tema'] }}</td><td class="num">{{ $p($t['pre']) }}</td><td><div class="bar"><div class="pre" style="width: {{ $w($t['pre'], 100) }}%;"></div></div></td><td class="num">{{ $p($t['post']) }}</td><td><div class="bar"><div style="width: {{ $w($t['post'], 100) }}%;"></div></div></td></tr>
                @endforeach
            </tbody>
        </table>
        <p class="note">Persentase jawaban benar per tema. Tema ditentukan dari isi soal, bukan dari judul modul.</p>
    </div>
    <table class="tiny avoid">
        <thead><tr><th>Lokasi</th><th class="num">Benar semua · pre-test</th><th class="num">Benar semua · post-test</th><th class="num">Selisih (poin %)</th></tr></thead>
        <tbody>
            @foreach ($locs->whereNotNull('skenario_post')->sortByDesc('skenario_post') as $r)
                @php $diff = $r['skenario_pre'] !== null ? $r['skenario_post'] - $r['skenario_pre'] : null; @endphp
                <tr><td>{{ $r['lokasi'] }}</td><td class="num">{{ $p($r['skenario_pre']) }}</td><td class="num">{{ $p($r['skenario_post']) }}</td><td class="num {{ $diff !== null && $diff < 0 ? 'low' : '' }}">{{ $signed($diff) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    {{-- ============================== BAB 3 ============================== --}}
    <div class="page-break"></div>
    <h2 class="bab"><small>Bab 3</small>Kesimpulan dan tindak lanjut</h2>
    <p>Secara umum, Program Digital Safety Champions telah terlaksana dengan capaian jangkauan, kehadiran, dan penyelesaian yang baik. Sebanyak {{ $n($totals['terdaftar']) }} peserta di {{ $n($totals['lokasi']) }} lokasi mengikuti kegiatan dengan tingkat kehadiran {{ $p($totals['pct_hadir']) }}, dan {{ $n($totals['sertifikat']) }} peserta telah menerima e-sertifikat. Peserta menunjukkan pemahaman keamanan digital yang tinggi, baik sebelum maupun sesudah kegiatan.</p>
    <p>Meski demikian, terdapat beberapa hal yang memerlukan tindak lanjut manajemen. @if ($ind['self_efficacy_dari_skor'])Pertama, survei self-efficacy perlu disusun secara terpisah agar kriteria 2.4 dapat dilaporkan. Kedua, data honor tutor perlu dilengkapi sebelum pembayaran. Ketiga, @else Pertama, data honor tutor perlu dilengkapi sebelum pembayaran. Kedua, @endif instrumen kuis perlu diperluas agar peningkatan pengetahuan serta aspek marketing dan content creation dapat terukur. Selain itu, lokasi dengan capaian di bawah batas penilaian perlu ditindaklanjuti sesuai rincian berikut.</p>
    <table class="small">
        <thead><tr><th style="width: 5%;">No</th><th style="width: 20%;">Lokasi</th><th style="width: 20%;">Isu</th><th>Keterangan</th></tr></thead>
        <tbody>
            @forelse ($attention as $a)
                <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $a['lokasi'] }}</td><td class="{{ $a['level'] === 'danger' ? 'low' : '' }}">{{ $a['isu'] }}</td><td>{{ $a['detail'] }}</td></tr>
            @empty
                <tr><td colspan="4">Tidak ada lokasi di bawah batas penilaian.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ============================== LAMPIRAN ============================== --}}
    @foreach ($lampiran as $i => [$judul])
        <div class="page-break"></div>
        <h2 class="bab"><small>Lampiran {{ $huruf[$i] }}</small>{{ $judul }}</h2>
        @if ($judul === 'Metodologi dan definisi')
            <p><b>Sumber data.</b> Seluruh angka dalam laporan ini diambil langsung dari sistem ISOC pada {{ $generatedAt->locale('id')->translatedFormat('d F Y, H.i') }} WIB. Event yang dibatalkan atau ditolak tidak diikutsertakan.</p>
            <p><b>Peserta terdaftar</b> dihitung per pendaftaran event, sehingga dapat sedikit berbeda dari jumlah peserta unik ({{ $n($totals['peserta_unik']) }} peserta).</p>
            <p><b>Kehadiran</b> adalah jumlah peserta yang tercatat "hadir" dibagi peserta terdaftar. Persentase dapat melebihi 100% apabila terdapat peserta hadir yang terdaftar di event lain.</p>
            <p><b>Rata-rata nilai pre-test dan post-test</b> dihitung dari seluruh percobaan tes. <b>Kenaikan per peserta</b> adalah selisih nilai post-test tertinggi dan pre-test tertinggi milik peserta yang sama di lokasi yang sama.</p>
            <p><b>Soal skenario</b> adalah soal pilihan ganda tentang phishing, penipuan online, dan hoaks. Tema soal ditentukan dari isi pertanyaan, bukan dari judul modul. Seorang peserta dihitung "benar semua" apabila seluruh soal skenario yang ia terima dijawab dengan benar.</p>
            <p><b>Batas penilaian</b> yang digunakan: kuota peserta di bawah {{ $SB::BATAS_KUOTA }}%, kehadiran di bawah {{ $SB::BATAS_HADIR }}%, sertifikat di bawah {{ $SB::BATAS_SERTIFIKAT }}%, serta penurunan nilai rata-rata setelah kegiatan.</p>
            <p><b>Perlindungan data.</b> Laporan ini tidak memuat NIK, NIS, NPWP, maupun nomor rekening. Data honor tutor hanya disajikan dalam bentuk status kelengkapan.</p>
        @elseif ($judul === 'Daftar tutor per lokus')
            <p class="lead">{{ $tutorNote }} Jumlah: {{ $n($tutorList->count()) }} penugasan.</p>
            <table class="tiny">
                <thead><tr><th style="width: 4%;">No</th><th style="width: 18%;">Nama</th><th style="width: 13%;">Lokasi</th><th style="width: 19%;">Lembaga</th><th style="width: 21%;">Email</th><th style="width: 11%;">No HP</th><th class="center">ToT</th><th class="center">Nilai</th><th class="center">Honor</th></tr></thead>
                <tbody>
                    @foreach ($tutorList as $r)
                        <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $r->name }}</td><td>{{ $short($r->event_title) }}</td><td>{{ $r->institution ?: '–' }}</td><td>{{ $r->email ?: '–' }}</td><td>{{ $r->phone ?: '–' }}</td><td class="center">{{ $r->tot_completed ? 'Ya' : 'Tidak' }}</td><td class="center">{{ $n($r->nilai_tot) }}</td><td class="center {{ $r->honor_lengkap ? '' : 'low' }}">{{ $r->honor_lengkap ? 'Lengkap' : 'Belum' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="lead">{{ $participantNote }} Jumlah: {{ $n($participantList->count()) }} peserta.</p>
            <table class="tiny">
                <thead><tr><th style="width: 4%;">No</th><th style="width: 16%;">Nama</th><th style="width: 10%;">Lokasi</th><th style="width: 17%;">Sekolah</th><th style="width: 6%;">Kelas</th><th style="width: 18%;">Email</th><th style="width: 11%;">No HP</th><th class="center">Hadir</th><th class="center">Pre</th><th class="center">Post</th><th class="center">Sert.</th></tr></thead>
                <tbody>
                    @foreach ($participantList as $r)
                        <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $r->name }}</td><td>{{ $short($r->event_title) }}</td><td>{{ $r->sekolah ?: '–' }}</td><td>{{ $r->grade ?: '–' }}</td><td>{{ $r->email ?: '–' }}</td><td>{{ $r->phone ?: '–' }}</td><td class="center">{{ $r->hadir ? 'Ya' : 'Tidak' }}</td><td class="center">{{ $n($r->nilai_pre) }}</td><td class="center">{{ $n($r->nilai_post) }}</td><td class="center">{{ $r->sertifikat ? 'Ya' : 'Belum' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach
</body>
</html>
