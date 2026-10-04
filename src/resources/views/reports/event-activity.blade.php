@php
    use App\Filament\Resources\LearningEventResource;
    use App\Support\TorEventTemplate;
    use Illuminate\Support\Facades\Storage;

    $formatScore = fn ($score) => $score === null ? '-' : number_format((float) $score, 2, ',', '.');
    $check = fn (bool $ok, string $yes = 'Lengkap', string $no = 'Belum lengkap') => '<span class="badge ' . ($ok ? 'badge-ok' : 'badge-wait') . '">' . ($ok ? $yes : $no) . '</span>';
    $reached = fn (?float $value, float $target) => $value !== null && $value >= $target;
    $rundownItems = collect($event->rundown_items ?? []);
    $termChecked = fn (int $term) => TorEventTemplate::checkedKeys($event->budget_items, $term);
    $evidenceStatuses = ['approved' => 'Disetujui', 'pending' => 'Menunggu', 'needs_revision' => 'Perlu revisi', 'rejected' => 'Ditolak'];
    $participantCount = $participants->count();
    $durationMinutes = $event->starts_at && $event->ends_at ? (int) $event->starts_at->diffInMinutes($event->ends_at) : null;
    $partnerNames = $event->orderedPartners->pluck('name');
    $evidenceComplete = collect($torEvidence)->every(fn ($row) => $row[3]);
    $kpis = [
        ['Siswa terlatih', $targetParticipants . ' siswa', $participantCount . ' siswa', $participantCount >= $targetParticipants],
        ['Tutor terlibat', $targetTutors . ' tutor', $tutors->count() . ' tutor', $tutors->count() >= $targetTutors],
        ['Skor rata-rata Post-Test', '≥ 85', $formatScore($postAverage), $reached($postAverage, 85)],
        ['Identifikasi ancaman digital', '≥ 82%', $threatAverage === null ? '-' : $formatScore($threatAverage) . '%', $reached($threatAverage, 82)],
        ['Self-efficacy setelah pelatihan', '≥ 70', $formatScore($selfEfficacyPost), $reached($selfEfficacyPost, 70)],
        ['Anggota WAG Mentoring', '70 per lokasi', $joinedWag . ' peserta', $joinedWag >= 70],
        ['Microsite s.id peserta', $participantCount . ' peserta', $microsites->count() . ' microsite', $participantCount > 0 && $microsites->count() >= $participantCount],
    ];
    $evidenceThumb = function ($evidence): ?string {
        $path = $evidence->file_path;
        if (! $path || ! preg_match('/\.(jpe?g|png|webp|gif)$/i', $path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:' . (Storage::disk('public')->mimeType($path) ?: 'image/png') . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    };
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; line-height: 1.45; margin: 0; }
        h1, h2, h3 { color: #0f172a; margin: 0; }
        h1 { font-size: 17pt; line-height: 1.2; text-align: center; text-transform: uppercase; }
        h2 { border-bottom: 1px solid #d1d5db; font-size: 11.5pt; margin-top: 7mm; padding-bottom: 1.5mm; }
        h3 { font-size: 10pt; margin-top: 4mm; }
        p { margin: 2mm 0; }
        ul { margin: 2mm 0; padding-left: 5mm; }
        .subtitle { color: #334155; font-size: 10.5pt; margin-top: 1.5mm; text-align: center; }
        .meta-grid { border-collapse: collapse; margin-top: 6mm; width: 100%; }
        .meta-grid td { border: 1px solid #cbd5e1; padding: 1.8mm 2.4mm; vertical-align: top; }
        .meta-grid td:first-child { background: #f8fafc; color: #334155; font-weight: 700; width: 38mm; }
        table { border-collapse: collapse; margin-top: 3mm; width: 100%; }
        /* Lebar kolom tetap (dalam persen) agar tabel tidak melebar keluar halaman. */
        table.fixed { table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 1.6mm 2mm; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #f8fafc; color: #0f172a; font-weight: 800; }
        .break { word-break: break-all; }
        .compact th, .compact td { font-size: 8pt; padding: 1.3mm 1.4mm; }
        .num { text-align: right; }
        .center { text-align: center; }
        .small { color: #64748b; font-size: 8pt; }
        .badge { border-radius: 999px; display: inline-block; font-size: 7.5pt; font-weight: 800; padding: 0.8mm 2mm; white-space: nowrap; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-wait { background: #fef3c7; color: #92400e; }
        .evidence-thumb { border: 1px solid #cbd5e1; height: 20mm; object-fit: contain; padding: 1mm; width: 28mm; }
        .page-break { page-break-before: always; }
        .summary { background: #f8fafc; border: 1px solid #cbd5e1; margin-top: 3mm; padding: 3mm; }
        .signature td { border: 0; padding-top: 4mm; text-align: center; width: 50%; }
        .footer { bottom: -9mm; color: #64748b; font-size: 7.5pt; left: 0; position: fixed; right: 0; text-align: right; }
    </style>
</head>
<body>
    <div class="footer">Laporan Kegiatan {{ $event->title }} · dicetak {{ now()->locale('id')->translatedFormat('d F Y H:i') }}</div>

    <h1>Laporan Kegiatan</h1>
    <div class="subtitle">Program Literasi Digital "Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia"</div>
    <div class="subtitle"><strong>Lokasi: {{ $school?->name ?? 'Event umum' }}</strong></div>

    <table class="meta-grid">
        <tr><td>Nama Kegiatan</td><td>{{ $event->title }}</td></tr>
        <tr><td>Tanggal</td><td>{{ $event->starts_at?->locale('id')->translatedFormat('l, d F Y') ?? '-' }}</td></tr>
        <tr><td>Waktu</td><td>{{ $event->starts_at?->format('H.i') ?? '-' }}–{{ $event->ends_at?->format('H.i') ?? '-' }} WIB{{ $durationMinutes ? ' (' . $durationMinutes . ' menit)' : '' }}</td></tr>
        <tr><td>Tipe Kegiatan</td><td>{{ LearningEventResource::eventTypeOptions()[$event->event_type ?: 'offline'] ?? $event->event_type }}{{ $event->zoom_url ? ' · ' . $event->zoom_url : '' }}</td></tr>
        <tr><td>Lokasi</td><td>{{ $school?->name ?? 'Event umum' }}{{ $school?->address ? ', ' . $school->address : '' }}</td></tr>
        <tr><td>Penyelenggara</td><td>ISOC Indonesia – Chapter Jakarta bersama Relawan TIK (RTIK) Daerah</td></tr>
        <tr><td>Mitra</td><td>{{ $partnerNames->isNotEmpty() ? $partnerNames->implode(', ') : '-' }}</td></tr>
        <tr><td>Jumlah Peserta</td><td>{{ $participantCount }} siswa + {{ $tutors->count() }} tutor (target {{ $targetParticipants }} siswa + {{ $targetTutors }} tutor)</td></tr>
        <tr><td>Periode Program</td><td>1 Oktober – 30 November 2026</td></tr>
    </table>

    <h2>1. Latar Belakang</h2>
    <p>
        Pesatnya pemanfaatan internet oleh generasi muda membawa tantangan serius terkait keamanan siber, seperti penipuan
        daring (scam), perundungan siber (cyberbullying), hoaks, hingga pencurian dan penyalahgunaan data pribadi. Pelajar SMA/SMK
        sangat aktif di ruang digital, namun sebagian besar belum memiliki literasi yang memadai untuk melindungi diri.
    </p>
    <ul>
        <li><strong>BSSN:</strong> 3,64 miliar anomali serangan siber Januari–Juli 2025, 83,68% berbasis malware via aplikasi palsu dan tautan phishing.</li>
        <li><strong>IASC:</strong> pemulihan dana korban kejahatan digital Rp161 miliar dari 1.070 korban (22 November 2024 – 12 Januari 2026).</li>
        <li><strong>Komdigi (data UNICEF):</strong> sekitar 45% anak di Indonesia pernah mengalami perundungan melalui media pesan digital.</li>
    </ul>
    <p>
        ISOC Indonesia – Chapter Jakarta menginisiasi program edukasi terstruktur berbasis multipihak untuk membangun budaya
        Online Trust and Safety (OTS) bagi pelajar secara berkelanjutan di 20 komunitas sekolah di Indonesia.
    </p>

    <h2>2. Tujuan Kegiatan</h2>
    <table class="fixed">
        <tr><th style="width: 12%;">Kode</th><th style="width: 50%;">Tujuan</th><th style="width: 38%;">Target</th></tr>
        <tr><td>BO-01</td><td>Meningkatkan kapasitas & keterampilan pelajar</td><td>{{ $targetParticipants }} siswa terlatih</td></tr>
        <tr><td>BO-02</td><td>Menstandardisasi mutu pembelajaran</td><td>Modul OTS + kuis terstandar + e-Certificate</td></tr>
        <tr><td>BO-03</td><td>Memberdayakan komunitas lokal berkelanjutan</td><td>Kader ToT + kelompok belajar sebaya</td></tr>
        <tr><td>BO-04</td><td>Efektivitas pembelajaran</td><td>Skor kuis rata-rata ≥ 85 (baseline 70)</td></tr>
        <tr><td>BO-05</td><td>Identifikasi ancaman digital</td><td>≥ 82% peserta</td></tr>
        <tr><td>BO-06</td><td>Kepercayaan diri digital (self-efficacy)</td><td>55 → 70 (pelatihan) → 85 (pendampingan)</td></tr>
        <tr><td>BO-07</td><td>Keberlanjutan komunitas</td><td>WhatsApp Group Mentoring aktif</td></tr>
    </table>

    <h2>3. Sasaran Peserta</h2>
    <ul>
        <li>{{ $targetParticipants }} siswa SMA/SMK di lokasi {{ $school?->name ?? 'kegiatan' }} (terdaftar: {{ $participantCount }} siswa).</li>
        <li>{{ $targetTutors }} tutor/fasilitator lokal (terlibat: {{ $tutors->count() }} tutor).</li>
        <li>Total program nasional: 2.000 siswa di 20 lokasi.</li>
    </ul>

    <h2>4. Stakeholder yang Terlibat</h2>
    <table class="fixed">
        <tr><th style="width: 38%;">Stakeholder</th><th style="width: 62%;">Peran dalam Kegiatan</th></tr>
        @foreach($event->orderedPartners as $partner)
            <tr><td>{{ $partner->name }}</td><td>{{ TorEventTemplate::PARTNER_ROLES[$partner->name] ?? 'Mitra pendukung kegiatan' }}</td></tr>
        @endforeach
        @if($school)
            <tr><td>{{ $school->name }}</td><td>Lokasi kegiatan, peserta, dan dukungan manajemen sekolah</td></tr>
        @endif
        <tr><td>Pelajar / Peserta</td><td>Penerima manfaat program</td></tr>
    </table>

    <h2>5. Rundown Kegiatan</h2>
    <table class="fixed">
        <tr><th style="width: 16%;">Waktu</th><th style="width: 10%;">Durasi</th><th style="width: 34%;">Sesi</th><th style="width: 18%;">PIC</th><th style="width: 22%;">Format & Deskripsi</th></tr>
        @forelse($rundownItems as $item)
            @php
                $start = $item['start_time'] ?? null;
                $end = $item['end_time'] ?? null;
                $minutes = $start && $end ? (int) \Illuminate\Support\Carbon::parse($start)->diffInMinutes(\Illuminate\Support\Carbon::parse($end)) : null;
            @endphp
            <tr>
                <td>{{ $start ?? '-' }}–{{ $end ?? '-' }}</td>
                <td>{{ $minutes !== null ? $minutes . "'" : '-' }}</td>
                <td>{{ $item['activity'] ?? '-' }}</td>
                <td>{{ $item['pic'] ?? '-' }}</td>
                <td class="small">{{ $item['notes'] ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Rundown belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>6. Materi yang Disampaikan</h2>
    <table class="fixed">
        <tr><th style="width: 6%;">No</th><th style="width: 74%;">Modul</th><th style="width: 20%;">Bahan</th></tr>
        @forelse($meetings as $meeting)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $meeting->title }}</strong>@if($meeting->description)<br><span class="small">{{ $meeting->description }}</span>@endif</td>
                <td>{{ $meeting->materials->pluck('type')->map(fn ($type) => strtoupper($type))->unique()->implode(', ') ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Materi belum tersedia.</td></tr>
        @endforelse
    </table>
    <p class="small">Praktik: microsite s.id (PANDI), simulasi kasus, role-play, dan open mic.</p>

    <h2>7. Metode</h2>
    <ul>
        <li>Video ajar keamanan siber dan ceramah interaktif modul ajar kepada siswa.</li>
        <li>Pre-Test dan Post-Test melalui formulir daring.</li>
        <li>Praktik pembuatan microsite s.id.</li>
        <li>Simulasi kasus, role-play, diskusi, dan tanya jawab (open mic).</li>
        <li>Pembentukan WhatsApp Group Mentoring (Digital Safety Champions).</li>
    </ul>

    <h2>8. Hasil Evaluasi</h2>
    <table class="fixed">
        <tr><th style="width: 40%;">Instrumen</th><th style="width: 18%;">Target</th><th style="width: 18%;">Hasil</th><th style="width: 24%;">Status</th></tr>
        <tr><td>Pre-Test (rata-rata)</td><td>Baseline 70</td><td>{{ $formatScore($preAverage) }}</td><td>{!! $check($preAverage !== null, 'Terukur', 'Belum ada data') !!}</td></tr>
        <tr><td>Post-Test (rata-rata)</td><td>≥ 85</td><td>{{ $formatScore($postAverage) }}</td><td>{!! $check($reached($postAverage, 85), 'Tercapai', 'Belum tercapai') !!}</td></tr>
        <tr><td>Identifikasi ancaman digital</td><td>≥ 82%</td><td>{{ $threatAverage === null ? '-' : $formatScore($threatAverage) . '%' }}</td><td>{!! $check($reached($threatAverage, 82), 'Tercapai', 'Belum tercapai') !!}</td></tr>
        <tr><td>Self-efficacy (sebelum)</td><td>Baseline 55</td><td>{{ $formatScore($selfEfficacyPre) }}</td><td>{!! $check($selfEfficacyPre !== null, 'Terukur', 'Belum ada data') !!}</td></tr>
        <tr><td>Self-efficacy (setelah pelatihan)</td><td>≥ 70</td><td>{{ $formatScore($selfEfficacyPost) }}</td><td>{!! $check($reached($selfEfficacyPost, 70), 'Tercapai', 'Belum tercapai') !!}</td></tr>
        <tr><td>Peserta mengerjakan Pre / Post-Test</td><td>{{ $participantCount }} peserta</td><td>{{ $preCompleted }} / {{ $postCompleted }}</td><td>{!! $check($participantCount > 0 && $postCompleted >= $participantCount, 'Lengkap', 'Belum lengkap') !!}</td></tr>
        <tr><td>Kehadiran tutor</td><td>{{ $targetTutors }} tutor</td><td>{{ $tutors->count() }}</td><td>{!! $check($tutors->count() >= $targetTutors, 'Tercapai', 'Belum tercapai') !!}</td></tr>
    </table>

    <h2>9. Dokumentasi & Bukti Dukung (sesuai TOR)</h2>
    <table class="fixed">
        <tr><th style="width: 6%;">No</th><th style="width: 44%;">Bukti Dukung</th><th style="width: 16%;">Target</th><th style="width: 18%;">Capaian</th><th style="width: 16%;">Status</th></tr>
        @foreach($torEvidence as [$label, $target, $achieved, $complete])
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $label }}</td>
                <td>{{ $target }}</td>
                <td>{{ $achieved }}</td>
                <td>{!! $check($complete) !!}</td>
            </tr>
        @endforeach
    </table>
    <p class="small">Rincian berkas bukti dukung tercantum pada Lampiran 3.</p>

    <h2>10. KPI Kegiatan</h2>
    <table class="fixed">
        <tr><th style="width: 40%;">KPI</th><th style="width: 20%;">Target</th><th style="width: 20%;">Capaian</th><th style="width: 20%;">Status</th></tr>
        @foreach($kpis as [$label, $target, $achieved, $ok])
            <tr><td>{{ $label }}</td><td>{{ $target }}</td><td>{{ $achieved }}</td><td>{!! $check($ok, 'Tercapai', 'Belum tercapai') !!}</td></tr>
        @endforeach
    </table>

    <h2>11. Anggaran (Checklist Termin)</h2>
    <p>Sesuai TOR, anggaran ditransfer ke rekening RTIK Pusat dalam dua termin. Checklist keperluan diisi Fasilitator pada wizard event.</p>
    @foreach(TorEventTemplate::TERM_CHECKLIST as $term => $list)
        @php $checked = $termChecked($term); @endphp
        <h3>Termin-{{ $term }} ({{ TorEventTemplate::TERM_NOTES[$term] }})</h3>
        <table class="fixed">
            <tr><th style="width: 8%;">No</th><th style="width: 62%;">Keperluan</th><th style="width: 30%;">Status</th></tr>
            @foreach($list as $key => $label)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $label }}</td><td>{!! $check(in_array($key, $checked, true), 'Terpenuhi', 'Belum') !!}</td></tr>
            @endforeach
        </table>
    @endforeach

    <h2>12. Kesimpulan & Rekomendasi</h2>
    <h3>Kesimpulan</h3>
    <ul>
        <li>Kegiatan dilaksanakan {{ $durationMinutes ? 'selama ' . $durationMinutes . ' menit ' : '' }}mengikuti susunan acara TOR dengan {{ $meetings->count() }} modul ajar.</li>
        <li>{{ $participantCount }} dari target {{ $targetParticipants }} siswa terdaftar; {{ $postCompleted }} siswa menyelesaikan Post-Test.</li>
        <li>Rata-rata Post-Test {{ $formatScore($postAverage) }} ({{ $reached($postAverage, 85) ? 'mencapai' : 'belum mencapai' }} target ≥ 85).</li>
        <li>Bukti dukung lokasi {{ $evidenceComplete ? 'lengkap dan siap diverifikasi untuk Termin-2' : 'belum lengkap; lihat bagian 9 untuk item yang perlu dilengkapi' }}.</li>
    </ul>
    <h3>Rekomendasi</h3>
    <ul>
        <li>Lanjutkan pendampingan melalui WhatsApp Group Mentoring.</li>
        <li>Bentuk kelompok belajar sebaya (peer-to-peer) di sekolah.</li>
        <li>Perluas praktik microsite s.id ke seluruh peserta.</li>
        <li>Pantau self-efficacy pasca-pendampingan (target 85).</li>
    </ul>

    <div class="page-break"></div>
    <h1>Lampiran</h1>

    <h2>Lampiran 1 — Daftar Peserta & Rekap Pre-Test / Post-Test</h2>
    <table class="fixed compact">
        <tr><th style="width: 5%;">No</th><th style="width: 20%;">Nama</th><th style="width: 9%;">Kelas</th><th style="width: 14%;">NISN/NIM</th><th style="width: 17%;">NIK</th><th style="width: 9%;">Pre</th><th style="width: 9%;">Post</th><th style="width: 8%;">Selisih</th><th style="width: 9%;">IG / WAG</th></tr>
        @forelse($participantScores as $index => $participant)
            @php $delta = $participant['pre'] !== null && $participant['post'] !== null ? $participant['post'] - $participant['pre'] : null; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $participant['name'] }}</td>
                <td>{{ $participant['grade'] }}</td>
                <td class="break">{{ $participant['identity'] }}</td>
                <td class="break">{{ $participant['nik'] }}</td>
                <td class="num">{{ $formatScore($participant['pre']) }}</td>
                <td class="num">{{ $formatScore($participant['post']) }}</td>
                <td class="num">{{ $delta === null ? '-' : ($delta >= 0 ? '+' : '') . number_format($delta, 1, ',', '.') }}</td>
                <td class="center">{{ $participant['followed_instagram'] ? '✓' : '–' }} / {{ $participant['joined_wag'] ? '✓' : '–' }}</td>
            </tr>
        @empty
            <tr><td colspan="9">Peserta belum tersedia.</td></tr>
        @endforelse
        <tr><th colspan="5">Rata-rata</th><th class="num">{{ $formatScore($preAverage) }}</th><th class="num">{{ $formatScore($postAverage) }}</th><th class="num">{{ $preAverage !== null && $postAverage !== null ? ($postAverage >= $preAverage ? '+' : '') . number_format($postAverage - $preAverage, 1, ',', '.') : '-' }}</th><th></th></tr>
    </table>

    <h2>Lampiran 2 — Daftar Tutor</h2>
    <table class="fixed">
        <tr><th style="width: 6%;">No</th><th style="width: 34%;">Nama</th><th style="width: 40%;">Instansi</th><th style="width: 20%;">Tanda Tangan</th></tr>
        @forelse($tutors as $tutor)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $tutor->user?->name ?? '-' }}</td><td>{{ $tutor->institution ?: '-' }}</td><td></td></tr>
        @empty
            <tr><td colspan="4">Tutor belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>Lampiran 3 — Rincian Berkas Bukti Dukung</h2>
    <h3>3A. Bukti Dukung Kegiatan</h3>
    <table class="fixed">
        <tr><th style="width: 19%;">Preview</th><th style="width: 27%;">Jenis Bukti</th><th style="width: 14%;">Status</th><th style="width: 22%;">Berkas / Tautan</th><th style="width: 18%;">Catatan</th></tr>
        @forelse($evidences->where('type', '!=', 'follow_ig') as $evidence)
            <tr>
                <td>
                    @if($thumb = $evidenceThumb($evidence))
                        <img class="evidence-thumb" src="{{ $thumb }}" alt="">
                    @else
                        <span class="small">-</span>
                    @endif
                </td>
                <td>{{ $evidenceTypes[$evidence->type] ?? $evidence->type }}</td>
                <td><span class="badge {{ $evidence->status === 'approved' ? 'badge-ok' : 'badge-wait' }}">{{ $evidenceStatuses[$evidence->status] ?? $evidence->status }}</span></td>
                <td class="break small">{{ $evidence->file_path ? basename($evidence->file_path) : ($evidence->link ?: '-') }}</td>
                <td class="small">{{ $evidence->review_notes ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Bukti dukung kegiatan belum tersedia.</td></tr>
        @endforelse
    </table>

    @if($event->attendance_proof_mode === 'system')
        <h3>Daftar Hadir Peserta (generate sistem dari kode absensi)</h3>
        <table class="fixed compact">
            <tr><th style="width: 6%;">No</th><th style="width: 40%;">Nama</th><th style="width: 14%;">Kelas</th><th style="width: 14%;">Mode</th><th style="width: 26%;">Waktu Absen</th></tr>
            @forelse($checkedIn as $attendance)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $attendance->participant?->user?->name ?? '-' }}</td>
                    <td>{{ $attendance->participant?->grade ?: '-' }}</td>
                    <td>{{ ucfirst((string) ($attendance->mode ?: 'offline')) }}</td>
                    <td>{{ $attendance->checked_in_at?->locale('id')->translatedFormat('d M Y H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada peserta yang absen.</td></tr>
            @endforelse
        </table>
    @endif

    <h3>3B. Bukti Dukung per Peserta</h3>
    <p class="small">Seluruh peserta terdaftar ({{ $participantProofs->count() }} orang) beserta screenshot follow Instagram ISOC, status join WhatsApp Group, microsite, dan e-Sertifikat.</p>
    <table class="fixed compact">
        <tr><th style="width: 5%;">No</th><th style="width: 22%;">Nama</th><th style="width: 21%;">Screenshot Follow IG</th><th style="width: 9%;">Join WAG</th><th style="width: 31%;">Microsite</th><th style="width: 12%;">Sertifikat</th></tr>
        @forelse($participantProofs as $proof)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $proof['name'] }}<br><span class="small">{{ $proof['grade'] }}</span></td>
                <td>
                    @if($proof['ig_evidence'] && ($thumb = $evidenceThumb($proof['ig_evidence'])))
                        <img class="evidence-thumb" src="{{ $thumb }}" alt="">
                    @else
                        <span class="badge badge-wait">Belum upload</span>
                    @endif
                </td>
                <td class="center">{!! $check($proof['joined_wag'], 'Sudah', 'Belum') !!}</td>
                <td class="break small">{{ $proof['microsite'] ?: '-' }}</td>
                <td class="center">{!! $check($proof['certificate'], 'Terbit', 'Belum') !!}</td>
            </tr>
        @empty
            <tr><td colspan="6">Peserta belum tersedia.</td></tr>
        @endforelse
    </table>

    <h2>Lampiran 4 — Peserta yang Sudah Join WhatsApp Group</h2>
    <table class="fixed">
        <tr><th style="width: 6%;">No</th><th style="width: 32%;">Nama</th><th style="width: 14%;">Kelas</th><th style="width: 24%;">No. HP</th><th style="width: 24%;">Email</th></tr>
        @forelse($wagMembers as $member)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $member->user?->name ?? '-' }}</td>
                <td>{{ $member->grade ?: '-' }}</td>
                <td class="break">{{ $member->user?->phone ?: '-' }}</td>
                <td class="break small">{{ $member->user?->email ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada peserta yang join WhatsApp Group.</td></tr>
        @endforelse
        <tr><th colspan="3">Total</th><th colspan="2">{{ $wagMembers->count() }} dari {{ $participantCount }} peserta</th></tr>
    </table>

    <h2>Lampiran 5 — Microsite s.id Karya Peserta</h2>
    <table class="fixed">
        <tr><th style="width: 6%;">No</th><th style="width: 34%;">Nama</th><th style="width: 60%;">URL Microsite</th></tr>
        @forelse($microsites as $microsite)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $microsite->participant?->user?->name ?? '-' }}</td><td class="break">{{ $microsite->sid_url }}</td></tr>
        @empty
            <tr><td colspan="3">Microsite belum tersedia.</td></tr>
        @endforelse
        <tr><th colspan="2">Total</th><th>{{ $microsites->count() }} microsite</th></tr>
    </table>

    <h2>Lampiran 6 — Bukti Materi Modul yang Dibawakan</h2>
    <p class="small">Modul yang dipilih untuk event ini beserta materi ajar yang digunakan.</p>
    @forelse($meetings as $meeting)
        <h3>{{ $meeting->title }}{{ $meeting->duration_minutes ? ' (' . $meeting->duration_minutes . ' menit)' : '' }}</h3>
        @if(filled($meeting->description))
            <p>{{ trim(strip_tags((string) $meeting->description)) }}</p>
        @endif
        <table class="fixed compact">
            <tr><th style="width: 6%;">No</th><th style="width: 40%;">Materi</th><th style="width: 12%;">Jenis</th><th style="width: 42%;">Berkas / Tautan</th></tr>
            @forelse($meeting->materials->sortBy('order')->values() as $material)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $material->title }}</td>
                    <td>{{ strtoupper((string) $material->type) }}</td>
                    <td class="break small">{{ $material->external_url ?: ($material->file_path ? Storage::disk('public')->url($material->file_path) : '-') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Materi belum tersedia.</td></tr>
            @endforelse
        </table>
    @empty
        <p>Modul belum dipilih.</p>
    @endforelse

    <h2>Lampiran 7 — Berita Acara Serah Terima Bukti Dukung</h2>
    <p>
        Pada hari ini, ........................ tanggal ........................, telah dilakukan serah terima bukti dukung kegiatan
        <strong>{{ $event->title }}</strong> dari Admin RTIK Daerah kepada Admin RTIK Pusat / ISOC, dengan rincian sesuai bagian 9 laporan ini.
    </p>
    <p><strong>Status:</strong> {{ $evidenceComplete ? 'LENGKAP dan siap diverifikasi untuk Termin-2.' : 'BELUM LENGKAP, perlu dilengkapi sebelum verifikasi Termin-2.' }}</p>
    <table class="signature">
        <tr><td>Pihak yang Menyerahkan<br>Admin RTIK Daerah</td><td>Pihak yang Menerima<br>Admin RTIK Pusat / ISOC</td></tr>
        <tr><td style="padding-top: 18mm;">( {{ $event->creator?->name ?? '..............................' }} )</td><td style="padding-top: 18mm;">( .............................. )</td></tr>
    </table>
</body>
</html>
