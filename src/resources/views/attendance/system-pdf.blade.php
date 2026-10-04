<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16mm 14mm; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; }
        h1 { font-size: 14pt; margin: 0; text-align: center; text-transform: uppercase; }
        .subtitle { font-size: 9pt; margin: 2mm 0 5mm; text-align: center; }
        .meta td { border: 0; padding: 0.8mm 0; }
        .meta td:first-child { font-weight: 700; width: 32mm; }
        table { border-collapse: collapse; width: 100%; }
        .list { margin-top: 4mm; }
        .list th, .list td { border: 1px solid #6b7280; padding: 1.6mm 2mm; text-align: left; }
        .list th { background: #f3f4f6; }
        .summary { background: #f9fafb; border: 1px solid #d1d5db; margin-top: 4mm; padding: 2.5mm 3mm; }
        .note { color: #6b7280; font-size: 8pt; margin-top: 3mm; }
        .sign { margin-top: 12mm; }
        .sign td { border: 0; text-align: center; width: 50%; }
    </style>
</head>
<body>
    <h1>Daftar Hadir Peserta</h1>
    <div class="subtitle">Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia</div>

    <table class="meta">
        <tr><td>Event</td><td>: {{ $event->title }}</td></tr>
        <tr><td>Lokasi</td><td>: {{ $event->school?->name ?? '-' }}</td></tr>
        <tr><td>Tanggal</td><td>: {{ $event->starts_at ? $event->starts_at->locale('id')->translatedFormat('l, d F Y') . ' | ' . $event->starts_at->format('H.i') . ($event->ends_at ? ' - ' . $event->ends_at->format('H.i') : '') . ' WIB' : '-' }}</td></tr>
        <tr><td>Tutor</td><td>: {{ $event->tutors->map(fn ($tutor) => $tutor->user?->name)->filter()->implode(', ') ?: '-' }}</td></tr>
    </table>

    <div class="summary">
        Peserta hadir: <strong>{{ $attendances->count() }}</strong> dari {{ $registered }} peserta terdaftar
        · Offline: {{ $attendances->where('mode', '!=', 'online')->count() }} · Online: {{ $attendances->where('mode', 'online')->count() }}
    </div>

    <table class="list">
        <tr><th style="width: 7%;">No</th><th style="width: 37%;">Nama Peserta</th><th style="width: 14%;">Kelas</th><th style="width: 14%;">Mode</th><th style="width: 28%;">Waktu Absen</th></tr>
        @forelse ($attendances as $attendance)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $attendance->participant?->user?->name ?? '-' }}</td>
                <td>{{ $attendance->participant?->grade ?: '-' }}</td>
                <td>{{ ucfirst((string) ($attendance->mode ?: 'offline')) }}</td>
                <td>{{ $attendance->checked_in_at?->locale('id')->translatedFormat('d M Y H:i') ?? '-' }} WIB</td>
            </tr>
        @empty
            <tr><td colspan="5">Belum ada peserta yang memasukkan kode absensi.</td></tr>
        @endforelse
    </table>

    <p class="note">Dokumen ini dibuat otomatis oleh sistem sena pada {{ now()->locale('id')->translatedFormat('d F Y H:i') }} WIB berdasarkan peserta yang memasukkan kode absensi event.</p>

    <table class="sign">
        <tr><td>Mengetahui,<br>Admin RTIK Daerah</td><td>Tutor</td></tr>
        <tr><td style="padding-top: 18mm;">( {{ $event->creator?->name ?? '..............................' }} )</td><td style="padding-top: 18mm;">( {{ $event->tutors->first()?->user?->name ?? '..............................' }} )</td></tr>
    </table>
</body>
</html>
