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
        .list { margin-top: 4mm; table-layout: fixed; }
        .list th, .list td { border: 1px solid #6b7280; padding: 1.6mm 2mm; text-align: left; word-wrap: break-word; }
        .list th { background: #f3f4f6; }
        .summary { background: #f9fafb; border: 1px solid #d1d5db; margin-top: 4mm; padding: 2.5mm 3mm; }
        .note { color: #6b7280; font-size: 8pt; margin-top: 3mm; }
    </style>
</head>
<body>
    <h1>Rekap Hasil Praktik Microsite s.id</h1>
    <div class="subtitle">Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia</div>

    <table class="meta">
        <tr><td>Event</td><td>: {{ $event->title }}</td></tr>
        <tr><td>Lokasi</td><td>: {{ $event->school?->name ?? '-' }}</td></tr>
        <tr><td>Tanggal</td><td>: {{ $event->starts_at ? $event->starts_at->locale('id')->translatedFormat('l, d F Y') : '-' }}</td></tr>
    </table>

    <div class="summary">Peserta yang mengumpulkan microsite: <strong>{{ $microsites->count() }}</strong> dari {{ $registered }} peserta terdaftar</div>

    <table class="list">
        <tr><th style="width: 7%;">No</th><th style="width: 33%;">Nama Peserta</th><th style="width: 12%;">Kelas</th><th style="width: 48%;">Link Microsite</th></tr>
        @forelse ($microsites as $microsite)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $microsite->participant?->user?->name ?? '-' }}</td>
                <td>{{ $microsite->participant?->grade ?: '-' }}</td>
                <td>{{ $microsite->sid_url }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Belum ada peserta yang mengumpulkan link microsite.</td></tr>
        @endforelse
    </table>

    <p class="note">Dokumen ini dibuat otomatis oleh sistem sena pada {{ now()->locale('id')->translatedFormat('d F Y H:i') }} WIB dari link microsite yang dikumpulkan peserta (link sudah dicek dapat diakses saat disimpan).</p>
</body>
</html>
