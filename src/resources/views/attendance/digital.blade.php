<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi {{ $mode ? ucfirst($mode) : 'Online' }} - {{ $event->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; text-align: center; text-transform: uppercase; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        .subtitle { font-size: 12px; margin-bottom: 12px; text-align: center; }
        .meta { font-size: 12px; margin-bottom: 10px; line-height: 1.6; }
        .summary { display: flex; gap: 8px; margin: 10px 0 4px; }
        .summary div { flex: 1; border: 1px solid #333; padding: 6px 8px; font-size: 11px; }
        .summary strong { display: block; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .empty { font-size: 11px; color: #555; }
        .toolbar { display: flex; gap: 8px; margin-bottom: 14px; font-size: 12px; align-items: center; }
        .toolbar a, .toolbar button { border: 1px solid #333; background: #fff; border-radius: 4px; padding: 5px 10px; color: #111; text-decoration: none; cursor: pointer; font-size: 12px; }
        .toolbar a.active { background: #111; color: #fff; }
        .sign { margin-top: 28px; display: flex; justify-content: flex-end; font-size: 12px; }
        .sign div { width: 220px; text-align: center; }
        .sign .line { margin-top: 56px; border-top: 1px solid #333; }
        @media print { body { margin: 12mm; } .no-print { display: none; } h2 { page-break-after: avoid; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button onclick="window.print()">Print / Save PDF</button>
        <span>Tampilkan:</span>
        <a href="{{ route('events.attendance.digital', $event) }}" class="{{ $mode ? '' : 'active' }}">Semua</a>
        <a href="{{ route('events.attendance.digital', [$event, 'mode' => 'online']) }}" class="{{ $mode === 'online' ? 'active' : '' }}">Online</a>
        <a href="{{ route('events.attendance.digital', [$event, 'mode' => 'offline']) }}" class="{{ $mode === 'offline' ? 'active' : '' }}">Offline</a>
    </div>

    <h1>Rekap Absensi {{ $mode ? ucfirst($mode) : 'Online (Kode Absensi)' }}</h1>
    <div class="subtitle">Program "Digital Safety Champions: Membangun Literasi Online Trust and Safety di Kalangan Pelajar di Indonesia"</div>
    <div class="meta">
        <div><strong>Event:</strong> {{ $event->title }}</div>
        <div><strong>Lokasi:</strong> {{ $event->school?->name ?? '-' }}</div>
        <div><strong>Tanggal:</strong> {{ $dateText }}</div>
        <div><strong>Tutor:</strong> {{ $event->tutors->map(fn ($tutor) => $tutor->user?->name)->filter()->implode(', ') ?: '-' }}</div>
        <div><strong>Dicetak:</strong> {{ now()->translatedFormat('d F Y, H:i') }} WIB</div>
    </div>

    <div class="summary">
        <div>Peserta terdaftar<strong>{{ $summary['registered'] }}</strong></div>
        <div>Hadir offline<strong>{{ $summary['offline'] }}</strong></div>
        <div>Hadir online<strong>{{ $summary['online'] }}</strong></div>
        <div>Belum absen<strong>{{ $summary['absent'] }}</strong></div>
    </div>

    <h2>A. Peserta hadir{{ $mode ? ' (' . $mode . ')' : '' }}</h2>
    @if ($attendances->isEmpty())
        <p class="empty">Belum ada peserta yang absen{{ $mode ? ' ' . $mode : '' }} dengan kode.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th>Nama Peserta</th>
                    <th style="width: 110px;">NISN / NIM</th>
                    <th style="width: 70px;">Kelas</th>
                    <th style="width: 150px;">Sekolah / Instansi</th>
                    <th style="width: 70px;">Kehadiran</th>
                    <th style="width: 120px;">Waktu Absen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $row->participant?->user?->name ?? '-' }}</td>
                        <td>{{ $row->participant?->nis ?? '-' }}</td>
                        <td>{{ $row->participant?->grade ?? '-' }}</td>
                        <td>{{ $row->participant?->organization ?? $event->school?->name ?? '-' }}</td>
                        <td>{{ $row->mode === 'online' ? 'Online' : 'Offline' }}</td>
                        <td>{{ $row->checked_in_at?->translatedFormat('d M Y, H:i') ?? '-' }} WIB</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! $mode)
        <h2>B. Peserta belum absen</h2>
        @if ($absent->isEmpty())
            <p class="empty">Semua peserta terdaftar sudah absen.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th style="width: 32px;">No</th>
                        <th>Nama Peserta</th>
                        <th style="width: 110px;">NISN / NIM</th>
                        <th style="width: 70px;">Kelas</th>
                        <th style="width: 150px;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($absent as $participant)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $participant->user?->name ?? '-' }}</td>
                            <td>{{ $participant->nis ?? '-' }}</td>
                            <td>{{ $participant->grade ?? '-' }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="sign">
        <div>
            {{ $event->school?->city ? \Illuminate\Support\Str::title(mb_strtolower($event->school->city)) . ', ' : '' }}{{ now()->translatedFormat('d F Y') }}<br>Tutor / Fasilitator
            <div class="line"></div>
        </div>
    </div>
</body>
</html>
