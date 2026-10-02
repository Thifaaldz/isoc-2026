@php
    $logo = public_path('images/sena-logo.png');
    $logoSrc = file_exists($logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo)) : null;
    $formatScore = fn ($score) => $score === null ? '-' : number_format((float) $score, 2);
    $rundownItems = collect($event->rundown_items ?? []);
    $approvedEvidence = $evidences->where('status', 'approved')->count();
    $budgetItems = collect($event->budget_items ?? []);
    $budgetTotal = $budgetItems->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    $completionPercent = fn ($count) => $participants->count() > 0 ? round(($count / $participants->count()) * 100) . '%' : '0%';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 10pt; line-height: 1.45; margin: 0; }
        h1, h2, h3 { color: #0f172a; margin: 0; }
        h1 { font-size: 18pt; line-height: 1.2; text-transform: uppercase; }
        h2 { border-bottom: 1px solid #d1d5db; font-size: 12pt; margin-top: 8mm; padding-bottom: 1.5mm; }
        h3 { font-size: 10.5pt; margin-top: 4mm; }
        .header { display: table; width: 100%; }
        .header-left, .header-right { display: table-cell; vertical-align: top; }
        .header-right { text-align: right; width: 42mm; }
        .logo { max-height: 20mm; max-width: 42mm; object-fit: contain; }
        .subtitle { color: #475569; font-size: 10pt; margin-top: 2mm; }
        .meta-grid { border: 1px solid #cbd5e1; border-collapse: collapse; margin-top: 6mm; width: 100%; }
        .meta-grid td { border: 1px solid #cbd5e1; padding: 2mm 2.4mm; vertical-align: top; }
        .meta-grid td:first-child { background: #f8fafc; color: #334155; font-weight: 700; width: 39mm; }
        table { border-collapse: collapse; margin-top: 3mm; width: 100%; }
        th, td { border: 1px solid #cbd5e1; padding: 1.8mm 2mm; text-align: left; vertical-align: top; }
        th { background: #f8fafc; color: #0f172a; font-weight: 800; }
        .small { color: #64748b; font-size: 8.5pt; }
        .badge { border-radius: 999px; display: inline-block; font-size: 8pt; font-weight: 800; padding: 1mm 2.2mm; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-wait { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1d4ed8; }
        .kpi-grid { display: table; margin-top: 4mm; table-layout: fixed; width: 100%; }
        .kpi { border: 1px solid #cbd5e1; display: table-cell; padding: 3mm; vertical-align: top; width: 25%; }
        .kpi strong { color: #0f172a; display: block; font-size: 15pt; line-height: 1.1; }
        .kpi span { color: #64748b; display: block; font-size: 8.5pt; margin-top: 1mm; }
        .evidence-thumb { border: 1px solid #cbd5e1; border-radius: 2mm; height: 20mm; max-width: 30mm; object-fit: contain; padding: 1.5mm; width: 30mm; }
        .two-col { display: table; gap: 4mm; margin-top: 3mm; width: 100%; }
        .two-col > div { display: table-cell; vertical-align: top; width: 50%; }
        .page-break { page-break-before: always; }
        .summary { background: #f8fafc; border: 1px solid #cbd5e1; margin-top: 4mm; padding: 3mm; }
        .footer { bottom: -9mm; color: #64748b; font-size: 8pt; left: 0; position: fixed; right: 0; text-align: right; }
    </style>
</head>
<body>
    <div class="footer">Dicetak otomatis oleh sena - {{ now()->translatedFormat('d F Y H:i') }}</div>

    <section class="header">
        <div class="header-left">
            <h1>Laporan Kegiatan Digital Safety Champions</h1>
            <div class="subtitle">Dokumen verifikasi pelaksanaan, bukti dukung, evaluasi pembelajaran, dan kelengkapan termin kegiatan.</div>
        </div>
        <div class="header-right">
            @if($logoSrc)
                <img class="logo" src="{{ $logoSrc }}" alt="">
            @endif
        </div>
    </section>

    <table class="meta-grid">
        <tr><td>Nama Kegiatan</td><td>{{ $event->title }}</td></tr>
        <tr><td>Nama Program</td><td>Digital Safety Champions - Literasi Keamanan Digital</td></tr>
        <tr><td>Tanggal</td><td>{{ $event->starts_at?->translatedFormat('d F Y') ?? '-' }}</td></tr>
        <tr><td>Waktu</td><td>{{ $event->starts_at?->format('H.i') ?? '-' }}-{{ $event->ends_at?->format('H.i') ?? '-' }} WIB</td></tr>
        <tr><td>Tipe</td><td>{{ strtoupper((string) $event->event_type) }}</td></tr>
        <tr><td>Lokasi</td><td>{{ $school?->name ?? 'Event umum' }}{{ $school?->address ? ', ' . $school->address : '' }}</td></tr>
        <tr><td>Link Zoom</td><td>{{ $event->zoom_url ?: '-' }}</td></tr>
        <tr><td>Penyelenggara</td><td>ISOC Indonesia Chapter Jakarta / RTIK Daerah</td></tr>
        <tr><td>Jumlah Peserta</td><td>{{ $participants->count() }} peserta terdaftar + {{ $tutors->count() }} tutor/fasilitator</td></tr>
    </table>

    <h2>1. Latar Belakang</h2>
    <p>
        Program Digital Safety Champions diselenggarakan untuk meningkatkan literasi keamanan digital,
        kemampuan mengenali ancaman online, dan praktik aman berinternet. Kegiatan ini mencakup
        pembelajaran modul, pre-test, post-test, praktik microsite s.id, dokumentasi kegiatan, dan
        pendampingan komunitas.
    </p>
    <p>
        Fokus Digital Safety Champions pada kegiatan ini adalah membangun kebiasaan aman bagi peserta melalui
        pembelajaran terstruktur, latihan mengenali modus ancaman, praktik kampanye microsite, dan penguatan
        komunitas melalui WhatsApp Group mentoring. Laporan ini disusun sebagai bukti pelaksanaan kegiatan
        sekaligus dasar pemeriksaan termin pembayaran oleh Admin RTIK Pusat.
    </p>

    <div class="kpi-grid">
        <div class="kpi"><strong>{{ $participants->count() }}</strong><span>Peserta terdaftar</span></div>
        <div class="kpi"><strong>{{ $meetings->count() }}</strong><span>Pertemuan/modul</span></div>
        <div class="kpi"><strong>{{ $formatScore($postAverage) }}</strong><span>Rata-rata post-test</span></div>
        <div class="kpi"><strong>{{ $approvedEvidence }}/{{ $evidences->count() }}</strong><span>Bukti approved</span></div>
    </div>

    <h2>2. Tujuan Kegiatan</h2>
    <table>
        <tr><th>Kode</th><th>Tujuan</th><th>Target</th></tr>
        <tr><td>BO-01</td><td>Meningkatkan kapasitas peserta</td><td>{{ $event->target_participants ?: 100 }} peserta terlatih</td></tr>
        <tr><td>BO-02</td><td>Menstandardisasi mutu pembelajaran</td><td>{{ $meetings->count() }} modul/pertemuan tersampaikan</td></tr>
        <tr><td>BO-03</td><td>Efektivitas pembelajaran</td><td>Post-test rata-rata >= 85</td></tr>
        <tr><td>BO-04</td><td>Keberlanjutan komunitas</td><td>WAG mentoring dan bukti dukung lengkap</td></tr>
    </table>

    <h2>3. Sasaran dan Pelaksana</h2>
    <p>
        Sasaran kegiatan adalah peserta pada lokus {{ $school?->name ?? 'umum' }} dengan komposisi
        {{ $participants->count() }} peserta, {{ $tutors->count() }} tutor/fasilitator, serta dukungan Admin RTIK Local.
        Seluruh peserta diarahkan untuk mengikuti pre-test, membaca materi, menyelesaikan kuis modul,
        mengikuti post-test, bergabung WAG mentoring, dan mengisi praktik microsite s.id.
    </p>
    <div class="two-col">
        <div>
            <h3>Tutor/Fasilitator</h3>
            <table>
                <tr><th>Nama</th><th>Instansi</th><th>Status</th></tr>
                @forelse($tutors as $tutor)
                    <tr>
                        <td>{{ $tutor->user?->name ?? '-' }}</td>
                        <td>{{ $tutor->institution ?: '-' }}</td>
                        <td>{{ $tutor->pivot?->status ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Tutor belum tersedia.</td></tr>
                @endforelse
            </table>
        </div>
        <div>
            <h3>Indikator Kelengkapan</h3>
            <table>
                <tr><th>Item</th><th>Hasil</th></tr>
                <tr><td>Pre-test selesai</td><td>{{ $preCompleted }}/{{ $participants->count() }} peserta ({{ $completionPercent($preCompleted) }})</td></tr>
                <tr><td>Kuis modul selesai</td><td>{{ $quizCompleted }}/{{ $participants->count() }} peserta ({{ $completionPercent($quizCompleted) }})</td></tr>
                <tr><td>Post-test selesai</td><td>{{ $postCompleted }}/{{ $participants->count() }} peserta ({{ $completionPercent($postCompleted) }})</td></tr>
                <tr><td>Rata-rata kuis</td><td>{{ $formatScore($quizAverage) }}</td></tr>
            </table>
        </div>
    </div>

    <h2>4. Rundown Kegiatan</h2>
    <table>
        <tr><th>Waktu</th><th>Durasi</th><th>Aktivitas</th><th>PIC</th></tr>
        @forelse($rundownItems as $item)
            @php
                $start = $item['start_time'] ?? null;
                $end = $item['end_time'] ?? null;
                $duration = '-';
                if ($start && $end) {
                    try { $duration = \Illuminate\Support\Carbon::parse($start)->diffInMinutes(\Illuminate\Support\Carbon::parse($end)) . "'"; } catch (\Throwable) {}
                }
            @endphp
            <tr>
                <td>{{ $start ?: '-' }}-{{ $end ?: '-' }}</td>
                <td>{{ $duration }}</td>
                <td>{{ $item['activity'] ?? '-' }}<div class="small">{{ $item['notes'] ?? '' }}</div></td>
                <td>{{ $item['pic'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Rundown belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>5. Materi yang Disampaikan</h2>
    <table>
        <tr><th>No</th><th>Materi</th><th>Durasi</th><th>Bahan</th><th>Kuis</th></tr>
        @forelse($meetings as $meeting)
            <tr>
                <td>{{ $meeting->order }}</td>
                <td><strong>{{ $meeting->title }}</strong><br><span class="small">{{ $meeting->description }}</span></td>
                <td>{{ $meeting->duration_minutes ?: 0 }} menit</td>
                <td>{{ $meeting->materials->pluck('type')->map(fn ($type) => strtoupper((string) $type))->unique()->implode(', ') ?: '-' }}</td>
                <td>{{ $meeting->assessments()->where('type', 'quiz')->where('is_open', true)->exists() ? 'Aktif' : 'Nonaktif' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Materi belum tersedia.</td></tr>
        @endforelse
    </table>

    <div class="page-break"></div>

    <h2>6. Hasil Evaluasi</h2>
    <table>
        <tr><th>Instrumen</th><th>Target</th><th>Hasil</th><th>Keterangan</th></tr>
        <tr><td>Pre-Test rata-rata</td><td>Baseline</td><td>{{ $formatScore($preAverage) }}</td><td>Mengukur pemahaman awal peserta.</td></tr>
        <tr><td>Kuis Modul rata-rata</td><td>>= 85</td><td>{{ $formatScore($quizAverage) }}</td><td>Evaluasi pemahaman per materi/pertemuan.</td></tr>
        <tr><td>Post-Test rata-rata</td><td>>= 85</td><td>{{ $formatScore($postAverage) }}</td><td>Mengukur peningkatan setelah pelatihan.</td></tr>
        <tr><td>Peserta terdaftar</td><td>{{ $event->target_participants ?: '-' }}</td><td>{{ $participants->count() }}</td><td>Data peserta pada sistem sena.</td></tr>
    </table>

    <h2>7. Daftar Peserta dan Nilai</h2>
    <table>
        <tr><th>No</th><th>Nama</th><th>Email</th><th>NISN/NIK/NIM</th><th>Kelas</th><th>Pre</th><th>Kuis</th><th>Post</th><th>Bukti Awal</th></tr>
        @forelse($participantScores as $index => $participant)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $participant['name'] }}</td>
                <td>{{ $participant['email'] }}</td>
                <td>{{ $participant['identity'] }}</td>
                <td>{{ $participant['grade'] }}</td>
                <td>{{ $formatScore($participant['pre']) }}</td>
                <td>{{ $formatScore($participant['quiz']) }}</td>
                <td>{{ $formatScore($participant['post']) }}</td>
                <td>
                    IG: {{ $participant['followed_instagram'] ? 'Ya' : 'Tidak' }}<br>
                    WAG: {{ $participant['joined_wag'] ? 'Ya' : 'Tidak' }}
                </td>
            </tr>
        @empty
            <tr><td colspan="9">Peserta belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>8. RAB dan Kebutuhan Kegiatan</h2>
    <table>
        <tr><th>Kategori</th><th>Uraian</th><th>Qty</th><th>Harga Satuan</th><th>Jumlah</th><th>Vendor/Catatan</th></tr>
        @forelse($budgetItems as $item)
            <tr>
                <td>{{ $item['category'] ?? '-' }}</td>
                <td>{{ $item['description'] ?? '-' }}</td>
                <td>{{ $item['quantity'] ?? '-' }} {{ $item['unit'] ?? '' }}</td>
                <td>Rp {{ number_format((float) ($item['unit_price'] ?? 0), 0, ',', '.') }}</td>
                <td>Rp {{ number_format((float) ($item['amount'] ?? 0), 0, ',', '.') }}</td>
                <td>{{ $item['vendor'] ?? '-' }}<br><span class="small">{{ $item['notes'] ?? '' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6">RAB belum tersedia.</td></tr>
        @endforelse
        <tr><th colspan="4">Total</th><th colspan="2">Rp {{ number_format($budgetTotal, 0, ',', '.') }}</th></tr>
    </table>

    <div class="page-break"></div>

    <h2>9. Bukti Dukung</h2>
    <table>
        <tr><th>Preview</th><th>Jenis Bukti</th><th>Status</th><th>File / Link</th><th>Catatan</th></tr>
        @forelse($evidences as $evidence)
            <tr>
                <td>
                    @if($evidencePreviewSrc)
                        <img class="evidence-thumb" src="{{ $evidencePreviewSrc }}" alt="">
                    @else
                        -
                    @endif
                </td>
                <td>{{ $evidenceTypes[$evidence->type] ?? $evidence->type }}</td>
                <td>
                    <span class="badge {{ $evidence->status === 'approved' ? 'badge-ok' : 'badge-wait' }}">
                        {{ $evidence->status }}
                    </span>
                </td>
                <td>{{ $evidence->file_path ?: ($evidence->link ?: '-') }}</td>
                <td>{{ $evidence->review_notes ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Bukti dukung belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>10. Ringkasan Kelengkapan Termin-2</h2>
    <div class="summary">
        <strong>Status laporan:</strong> {{ $event->final_report_status ?? 'draft' }}<br>
        <strong>Bukti approved:</strong> {{ $approvedEvidence }} dari {{ $evidences->count() }} bukti<br>
        <strong>Jumlah peserta:</strong> {{ $participants->count() }} dari target {{ $event->target_participants ?: '-' }} peserta<br>
        <strong>Total RAB:</strong> Rp {{ number_format($budgetTotal, 0, ',', '.') }}<br>
        <strong>Catatan pusat:</strong> {{ $event->final_report_notes ?: ($event->central_admin_notes ?: '-') }}
    </div>
</body>
</html>
