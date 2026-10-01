@php
    use App\Models\Assessment;
    use App\Models\AssessmentAttempt;

    $participant = $certificate->participant;
    $event = $certificate->learningEvent ?? $participant?->learningEvents()->first();
    $school = $participant?->school;
    $templateImage = public_path('certificate-templates/esertifikat-litdig-2026.png');
    $issuedDate = optional($certificate->issued_at)->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y');
    $eventDate = optional($event?->starts_at)->translatedFormat('d F Y') ?? $issuedDate;

    $assessments = $event
        ? Assessment::query()
            ->with('meeting')
            ->where('learning_event_id', $event->id)
            ->orderByRaw("case type when 'pre' then 0 when 'quiz' then 1 when 'post' then 2 else 3 end")
            ->orderBy('learning_meeting_id')
            ->orderBy('id')
            ->get()
        : collect();

    $attempts = AssessmentAttempt::query()
        ->where('participant_id', $participant?->id)
        ->whereIn('assessment_id', $assessments->pluck('id'))
        ->get()
        ->keyBy('assessment_id');

    $scoreRows = $assessments->map(function (Assessment $assessment) use ($attempts): array {
        $attempt = $attempts->get($assessment->id);
        $meetingTitle = $assessment->meeting?->title;
        $label = match ($assessment->type) {
            'pre' => 'Pre-Test',
            'post' => 'Post-Test',
            'quiz' => 'Kuis Modul',
            default => strtoupper((string) $assessment->type),
        };

        return [
            'component' => $meetingTitle ?: $assessment->title,
            'type' => $label,
            'score' => $attempt?->score,
            'correct' => $attempt ? ($attempt->correct_count . '/' . $attempt->total_questions) : '-',
            'status' => $attempt ? 'Selesai' : 'Belum',
        ];
    });

    $averageScore = $scoreRows->whereNotNull('score')->avg('score');
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body {
            color: #202427;
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
        }
        .page {
            height: 210mm;
            overflow: hidden;
            page-break-after: always;
            position: relative;
            width: 297mm;
        }
        .page:last-child { page-break-after: auto; }
        .template-bg {
            height: 210mm;
            left: 0;
            object-fit: cover;
            position: absolute;
            top: 0;
            width: 297mm;
            z-index: 1;
        }
        .cover-box {
            background: #fff;
            position: absolute;
            z-index: 2;
        }
        .overlay {
            position: absolute;
            text-align: center;
            z-index: 3;
        }
        .cert-number {
            color: #202427;
            font-size: 15pt;
            font-weight: 500;
            left: 86mm;
            top: 60mm;
            width: 125mm;
        }
        .participant-name {
            color: #202427;
            font-size: 26pt;
            font-weight: 800;
            left: 61mm;
            line-height: 1.1;
            top: 80mm;
            width: 175mm;
        }
        .event-date {
            color: #202427;
            font-size: 15pt;
            font-weight: 800;
            left: 113mm;
            top: 123mm;
            width: 52mm;
        }
        .page-2 {
            background: #f8fafc;
            padding: 18mm 20mm;
        }
        .transcript-card {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 10px;
            height: 174mm;
            padding: 12mm;
        }
        .transcript-head {
            border-bottom: 3px solid #072757;
            margin-bottom: 8mm;
            padding-bottom: 6mm;
        }
        .eyebrow {
            color: #2563eb;
            font-size: 9pt;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1 {
            color: #072757;
            font-size: 24pt;
            line-height: 1.15;
            margin: 2mm 0 0;
        }
        .meta {
            color: #475569;
            font-size: 10pt;
            line-height: 1.55;
            margin-top: 3mm;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th {
            background: #072757;
            color: #fff;
            font-size: 9pt;
            padding: 3mm;
            text-align: left;
        }
        td {
            border-bottom: 1px solid #e2e8f0;
            color: #202427;
            font-size: 9pt;
            padding: 3mm;
            vertical-align: top;
        }
        .score {
            font-weight: 800;
            text-align: center;
        }
        .summary {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: #9a3412;
            font-size: 10pt;
            font-weight: 800;
            margin-top: 7mm;
            padding: 4mm;
            text-align: right;
        }
    </style>
</head>
<body>
    <section class="page">
        @if (file_exists($templateImage))
            <img class="template-bg" src="{{ $templateImage }}" alt="">
        @endif

        <div class="cover-box" style="left: 88mm; top: 59mm; width: 121mm; height: 10mm;"></div>
        <div class="overlay cert-number">No. {{ $certificate->number }}</div>

        <div class="cover-box" style="left: 70mm; top: 79mm; width: 157mm; height: 18mm;"></div>
        <div class="overlay participant-name">{{ $participant?->user?->name ?? 'Nama Peserta' }}</div>

        <div class="cover-box" style="left: 112mm; top: 122mm; width: 56mm; height: 8mm;"></div>
        <div class="overlay event-date">{{ $eventDate }}</div>
    </section>

    <section class="page page-2">
        <div class="transcript-card">
            <div class="transcript-head">
                <div class="eyebrow">Lampiran Sertifikat</div>
                <h1>Materi Pembelajaran dan Nilai</h1>
                <div class="meta">
                    <strong>{{ $participant?->user?->name ?? '-' }}</strong><br>
                    {{ $event?->title ?? 'Digital Safety Champions' }}<br>
                    {{ $school?->name ?? '-' }} - No. Sertifikat: {{ $certificate->number }}
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 10mm;">No</th>
                        <th>Materi / Komponen</th>
                        <th style="width: 34mm;">Jenis</th>
                        <th style="width: 28mm;">Benar</th>
                        <th style="width: 25mm;">Nilai</th>
                        <th style="width: 28mm;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scoreRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['component'] }}</td>
                            <td>{{ $row['type'] }}</td>
                            <td class="score">{{ $row['correct'] }}</td>
                            <td class="score">{{ $row['score'] !== null ? number_format((float) $row['score'], 0) : '-' }}</td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada data materi dan nilai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary">
                Rata-rata nilai: {{ $averageScore !== null ? number_format((float) $averageScore, 2) : '-' }}
            </div>
        </div>
    </section>
</body>
</html>
