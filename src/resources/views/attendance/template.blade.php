<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Template Absensi - {{ $session->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .meta { font-size: 12px; margin-bottom: 16px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .signature { height: 28px; }
        @media print { body { margin: 12mm; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print / Save PDF</button>
    <h1>Daftar Hadir Peserta Digital Safety Champions</h1>
    <div class="meta">
        <div><strong>Sesi:</strong> {{ $session->title }}</div>
        <div><strong>Sekolah/Lokus:</strong> {{ $session->school?->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $session->date?->format('d F Y') }} | {{ $session->start_time }} - {{ $session->end_time }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th>Nama Peserta</th>
                <th style="width: 100px;">NISN/NIK</th>
                <th style="width: 80px;">Kelas</th>
                <th style="width: 140px;">Tanda Tangan</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($participants as $participant)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $participant->user?->name }}</td>
                    <td>{{ $participant->nis }}</td>
                    <td>{{ $participant->grade }}</td>
                    <td class="signature"></td>
                    <td></td>
                </tr>
            @empty
                @for($i = 1; $i <= 100; $i++)
                    <tr>
                        <td>{{ $i }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="signature"></td>
                        <td></td>
                    </tr>
                @endfor
            @endforelse
        </tbody>
    </table>
</body>
</html>
