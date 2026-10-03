<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Hadir - {{ $session->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        .subtitle { font-size: 12px; margin-bottom: 12px; text-align: center; }
        .meta { font-size: 12px; margin-bottom: 8px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .signature { height: 26px; }
        .note { font-size: 11px; margin-top: 6px; color: #444; }
        @media print { body { margin: 12mm; } .no-print { display: none; } h2 { page-break-after: avoid; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print / Save PDF</button>
    <h1>Daftar Hadir Kegiatan Literasi Digital</h1>
    <div class="subtitle">Program "Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia"</div>
    <div class="meta">
        <div><strong>Sesi:</strong> {{ $session->title }}</div>
        <div><strong>Lokasi:</strong> {{ $session->school?->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $session->date?->locale('id')->translatedFormat('l, d F Y') ?? '-' }} | {{ $session->start_time }} - {{ $session->end_time }} WIB</div>
    </div>
    <div class="note">Sesuai TOR: absensi dengan nama dan tanda tangan basah terdiri dari {{ \App\Support\TorEventTemplate::DEFAULT_TUTORS }} orang tutor dan {{ \App\Support\TorEventTemplate::DEFAULT_PARTICIPANTS }} orang peserta.</div>

    <h2>A. Tutor / Fasilitator</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th>Nama Tutor</th>
                <th style="width: 200px;">Instansi</th>
                <th style="width: 160px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $tutorRows; $i++)
                @php $tutor = $tutors->get($i); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $tutor?->user?->name }}</td>
                    <td>{{ $tutor?->institution }}</td>
                    <td class="signature"></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <h2>B. Peserta</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th>Nama Peserta</th>
                <th style="width: 110px;">NISN / NIM</th>
                <th style="width: 70px;">Kelas</th>
                <th style="width: 160px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $participantRows; $i++)
                @php $participant = $participants->get($i); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $participant?->user?->name }}</td>
                    <td>{{ $participant?->nis }}</td>
                    <td>{{ $participant?->grade }}</td>
                    <td class="signature"></td>
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
