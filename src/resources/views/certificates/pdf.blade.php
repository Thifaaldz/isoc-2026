@php
    use App\Models\LearningMeeting;

    $participant = $certificate->participant;
    $event = $certificate->learningEvent ?? $participant?->learningEvents()->first();
    $event?->loadMissing('orderedPartners');
    $school = $participant?->school;
    $templateImage = public_path(\App\Models\CertificateTemplate::DEFAULT_BACKGROUND);
    $templateImageSrc = file_exists($templateImage)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($templateImage))
        : null;
    $senaLogoImage = public_path('images/sena-logo.png');
    $senaLogoSrc = file_exists($senaLogoImage)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($senaLogoImage))
        : null;
    $issuedDate = optional($certificate->issued_at)->translatedFormat('d F Y') ?? now()->translatedFormat('d F Y');
    $eventDate = optional($event?->starts_at)->translatedFormat('d F Y') ?? $issuedDate;
    $participantName = $participant?->user?->name ?? 'Nama Peserta';
    $nameFontSize = mb_strlen($participantName) > 34 ? 20 : (mb_strlen($participantName) > 24 ? 23 : 26);
    $eventTitle = $event?->title ?? 'Digital Safety Champions';
    $competencyTitle = 'PESERTA LITERASI DIGITAL SAFETY CHAMPIONS';
    $primaryTutor = $event?->exists
        ? $event->tutors()->with('user')->orderBy('tutors.id')->first()
        : null;
    $tutorName = $primaryTutor?->user?->name ?? 'Nama Tutor';
    $tutorInstitution = $primaryTutor?->institution ?: ($primaryTutor ? ($school?->name ?? '-') : 'Lembaga Tutor');
    $organizerName = 'Sena';
    $verifyUrl = filled($certificate->number) ? route('certificate.verify', $certificate->number) : null;
    $qrCodeSrc = null;

    if ($verifyUrl) {
        try {
            $qrCodeSrc = (new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
                'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
                'scale' => 6,
                'quietzoneSize' => 1,
                'outputBase64' => true,
            ])))->render($verifyUrl);
        } catch (\Throwable) {
            $qrCodeSrc = null;
        }
    }

    // Lampiran halaman 2: JP (jam pelajaran) dihitung dari lama waktu tiap modul, 1 JP = 45 menit.
    $minutesPerJp = 45;
    $formatJp = fn (float $jp): string => rtrim(rtrim(number_format($jp, 2, ',', '.'), '0'), ',');

    $meetings = $event?->exists
        ? LearningMeeting::query()
            ->with(['materials' => fn ($query) => $query->where('is_published', true)->orderBy('order')])
            ->where('learning_event_id', $event->id)
            ->where('is_published', true)
            ->orderBy('order')
            ->get()
        : collect();

    $jpRows = $meetings->map(function (LearningMeeting $meeting) use ($minutesPerJp): array {
        $minutes = (int) ($meeting->duration_minutes ?: $meeting->materials->sum('duration_minutes'));

        return [
            'module' => $meeting->title ?: 'Pertemuan ' . $meeting->order,
            'materials' => $meeting->materials->pluck('type')->filter()->map(fn ($type) => strtoupper((string) $type))->unique()->implode(', ') ?: '-',
            'minutes' => $minutes,
            'jp' => $minutes / $minutesPerJp,
        ];
    });

    $totalMinutes = $jpRows->sum('minutes');
    $totalJp = $totalMinutes / $minutesPerJp;

    $certificateTemplate = $certificate->certificateTemplate ?? $event?->certificateTemplate;
    $uploadedPath = function (mixed $value) use (&$uploadedPath): ?string {
        if (blank($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $uploadedPath($decoded);
            }

            return $value;
        }

        if (is_array($value)) {
            if (isset($value['path'])) {
                return $uploadedPath($value['path']);
            }

            if (isset($value['file'])) {
                return $uploadedPath($value['file']);
            }

            foreach ($value as $item) {
                if (filled($item)) {
                    return $uploadedPath($item);
                }
            }
        }

        return null;
    };

    $imageDataUri = function (mixed $value) use ($uploadedPath): ?string {
        $path = $uploadedPath($value);

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $absolutePath = str_starts_with($path, '/')
            ? public_path(ltrim($path, '/'))
            : \Illuminate\Support\Facades\Storage::disk('public')->path($path);

        if (! file_exists($absolutePath)) {
            $absolutePath = public_path(ltrim($path, '/'));
        }

        if (! file_exists($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
    };

    $templateImageSrc = $imageDataUri($certificateTemplate?->background_image) ?: $templateImageSrc;
    $templateElements = collect($certificateTemplate?->elements ?? [])->values();
    $imageElementTypes = ['sena_logo', 'logo', 'partner_logo', 'uploaded_logo', 'signature_image', 'uploaded_signature', 'image'];
    $eventPartnerLogoSrcs = $event?->orderedPartners
        ?->where('status', 'active')
        ->map(fn ($partner) => $imageDataUri($partner->logo_path) ?: $imageDataUri($partner->logo_url) ?: $senaLogoSrc)
        ->filter()
        ->values()
        ?? collect();

    if (! $templateElements->contains(fn ($element) => ($element['type'] ?? null) === 'event_partner_logos') && $eventPartnerLogoSrcs->isNotEmpty()) {
        $templateElements->prepend([
            'type' => 'event_partner_logos',
            'label' => 'Logo Mitra Event',
            'x' => 18,
            'y' => 14,
            'width' => 86,
            'height' => 16,
        ]);
    }

    // Rasio lebar/tinggi logo dipakai agar logo tidak gepeng (dompdf tidak mendukung object-fit).
    $logoRatio = function (string $src): float {
        if (! str_starts_with($src, 'data:')) {
            return 1.0;
        }

        $binary = base64_decode(substr($src, strpos($src, ',') + 1)) ?: '';

        if (str_contains(substr($src, 0, 40), 'svg')) {
            if (preg_match('/viewBox="\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/', $binary, $match) && (float) $match[2] > 0) {
                return (float) $match[1] / (float) $match[2];
            }

            return 1.0;
        }

        $size = @getimagesizefromstring($binary);

        return $size && $size[1] > 0 ? $size[0] / $size[1] : 1.0;
    };
    $fitLogo = function (string $src, float $maxWidthMm, float $maxHeightMm) use ($logoRatio): array {
        $ratio = max(0.1, $logoRatio($src));
        $width = min($maxWidthMm, $maxHeightMm * $ratio);

        return ['src' => $src, 'width' => round($width, 2), 'height' => round($width / $ratio, 2)];
    };

    $pageTwoLogoSrcs = $eventPartnerLogoSrcs;
    $elementText = function (array $element) use ($participantName, $eventTitle, $certificate, $issuedDate, $school, $participant, $eventDate, $competencyTitle, $tutorName, $tutorInstitution, $organizerName): string {
        $type = $element['type'] ?? 'custom_text';
        $content = match ($type) {
            'participant_name' => $participantName,
            'event_title' => $eventTitle,
            'certificate_number' => $certificate->number ?: '-',
            'issued_date' => $issuedDate,
            'school_name' => $school?->name ?? 'Nama Sekolah / Instansi',
            'class_name' => $participant?->grade ?? 'Kelas / Peran',
            'tutor_name' => $tutorName,
            'tutor_institution' => $tutorInstitution,
            'organizer_name' => $organizerName,
            'signature_line' => '________________________',
            default => $element['content'] ?? 'Tulisan bebas',
        };

        return strtr((string) $content, [
            '{{participant_name}}' => $participantName,
            '{{event_title}}' => $eventTitle,
            '{{certificate_number}}' => $certificate->number ?: '-',
            '{{issued_date}}' => $issuedDate,
            '{{school_name}}' => $school?->name ?? 'Nama Sekolah / Instansi',
            '{{class_name}}' => $participant?->grade ?? 'Kelas / Peran',
            '{{event_date}}' => $eventDate,
            '{{competency_title}}' => $competencyTitle,
            '{{tutor_name}}' => $tutorName,
            '{{tutor_institution}}' => $tutorInstitution,
            '{{organizer_name}}' => $organizerName,
        ]);
    };
    $elementImageSrc = function (array $element) use ($imageDataUri, $eventPartnerLogoSrcs, $senaLogoSrc): ?string {
        $type = $element['type'] ?? null;

        if ($type === 'sena_logo') {
            return $imageDataUri($element['image_path'] ?? null) ?: $senaLogoSrc;
        }

        if ($type === 'partner_logo') {
            return $imageDataUri($element['image_path'] ?? null) ?: $eventPartnerLogoSrcs->first();
        }

        return $imageDataUri($element['image_path'] ?? null);
    };
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
            position: relative;
            width: 297mm;
        }
        .page-1 { page-break-after: always; }
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
        .studio-image-element {
            object-fit: contain;
            position: absolute;
            z-index: 5;
        }
        .event-partner-logos {
            border-collapse: collapse;
            position: absolute;
            table-layout: fixed;
            z-index: 5;
        }
        .event-partner-logos td,
        .partner-logo-row td {
            border: 0;
            padding: 0 1.3mm;
            text-align: center;
            vertical-align: middle;
        }
        .partner-logo-row {
            border-collapse: collapse;
            table-layout: fixed;
            width: auto;
        }
        .template-text-element {
            line-height: 1.25;
            overflow: hidden;
            position: absolute;
            white-space: pre-line;
            z-index: 5;
        }
        .template-shape-element {
            position: absolute;
            z-index: 4;
        }
        .template-qr-element {
            background: #ffffff;
            position: absolute;
            text-align: center;
            z-index: 5;
        }
        .template-qr-element img {
            height: 100%;
            width: 100%;
        }
        .cert-number {
            color: #202427;
            font-size: 14pt;
            font-weight: 500;
            left: 70mm;
            top: 57.8mm;
            width: 157mm;
        }
        .given-to {
            color: #202427;
            font-size: 18pt;
            font-weight: 400;
            left: 70mm;
            line-height: 1;
            top: 70.2mm;
            width: 157mm;
        }
        .participant-name {
            color: #202427;
            font-size: {{ $nameFontSize }}pt;
            font-weight: 800;
            left: 48mm;
            line-height: 1.1;
            top: 80mm;
            width: 201mm;
        }
        .event-line {
            color: #202427;
            font-size: 13.5pt;
            font-weight: 400;
            left: 14mm;
            line-height: 1.34;
            top: 98.8mm;
            width: 269mm;
        }
        .event-line strong {
            font-weight: 800;
        }
        .event-program {
            display: inline-block;
            font-size: 15.5pt;
            font-weight: 800;
            line-height: 1.25;
            max-width: 260mm;
        }
        .competency-line {
            display: inline-block;
            font-size: 15pt;
            font-weight: 900;
            letter-spacing: .02em;
            line-height: 1.25;
            max-width: 260mm;
        }
        .page-2 {
            background: #ffffff;
            height: 210mm;
            overflow: hidden;
            padding: 0;
        }
        .side-pattern {
            background: #19c6d3;
            bottom: 0;
            height: 210mm;
            overflow: hidden;
            position: absolute;
            top: 0;
            width: 8mm;
            z-index: 0;
        }
        .side-pattern.left { left: 0; }
        .side-pattern.right { right: 0; }
        .side-pattern div {
            display: none;
            height: 10.5mm;
        }
        .side-pattern div:nth-child(4n+1) { background: #19c6d3; }
        .side-pattern div:nth-child(4n+2) { background: #f5ce39; }
        .side-pattern div:nth-child(4n+3) { background: #0b2a57; }
        .side-pattern div:nth-child(4n+4) { background: #15a1dc; }
        .transcript-card {
            background: #fff;
            height: 166mm;
            left: 16mm;
            padding: 5mm 7mm 10mm;
            position: absolute;
            top: 10mm;
            width: 265mm;
            z-index: 1;
        }
        .transcript-head {
            margin-bottom: 7mm;
            padding-bottom: 3mm;
            position: relative;
        }
        .eyebrow {
            color: #202427;
            font-size: 8pt;
            font-weight: 700;
        }
        h1 {
            color: #202427;
            font-size: 11pt;
            line-height: 1.15;
            margin: 8mm 0 0;
        }
        .meta {
            color: #202427;
            font-size: 8.5pt;
            line-height: 1.45;
            margin-top: 1mm;
        }
        .watermark {
            color: rgba(5, 143, 206, .05);
            display: none;
            font-size: 74pt;
            font-weight: 900;
            left: 70mm;
            letter-spacing: -.08em;
            position: absolute;
            top: 73mm;
            z-index: 0;
        }
        table {
            border-collapse: collapse;
            page-break-inside: avoid;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        tr { page-break-inside: avoid; }
        th {
            background: #f7f7f7;
            border: 1px solid #555;
            color: #202427;
            font-size: 7.7pt;
            font-weight: 800;
            padding: 1.8mm 2.2mm;
            text-align: left;
        }
        td {
            border: 1px solid #555;
            color: #202427;
            font-size: 7.5pt;
            line-height: 1.28;
            padding: 1.6mm 2.2mm;
            vertical-align: top;
        }
        .score {
            font-weight: 800;
            text-align: center;
        }
        .summary {
            color: #202427;
            font-size: 9pt;
            font-weight: 800;
            margin-top: 5mm;
            padding: 3mm;
            text-align: right;
        }
        .page-footer {
            bottom: 8mm;
            color: #64748b;
            font-size: 9pt;
            left: 25mm;
            position: absolute;
            right: 25mm;
            z-index: 2;
        }
        .page-footer .right {
            float: right;
            text-align: right;
        }
    </style>
</head>
<body>
    <section class="page page-1">
        @if ($templateImageSrc)
            <img class="template-bg" src="{{ $templateImageSrc }}" alt="">
        @endif
        @foreach ($templateElements as $element)
            @php
                $type = $element['type'] ?? 'custom_text';
                $x = (float) ($element['x'] ?? 0);
                $y = (float) ($element['y'] ?? 0);
                $widthMm = max(1, (float) ($element['width'] ?? 20));
                $heightMm = max(1, (float) ($element['height'] ?? 10));
            @endphp

            @if ($type === 'event_partner_logos')
                @if ($eventPartnerLogoSrcs->isNotEmpty())
                    @php
                        // Logo disusun dalam sel tabel berukuran sama agar seluruh mitra muat dalam satu baris.
                        $logoCount = max(1, $eventPartnerLogoSrcs->count());
                        $logoSlotMm = min(32, ($widthMm / $logoCount) - 2.6);
                    @endphp
                    <table
                        class="event-partner-logos"
                        style="left: {{ $x }}mm; top: {{ $y }}mm; width: {{ min($widthMm, ($logoSlotMm + 2.6) * $logoCount) }}mm; height: {{ $heightMm }}mm;"
                    >
                        <tr>
                            @foreach ($eventPartnerLogoSrcs as $partnerLogoSrc)
                                @php $logo = $fitLogo($partnerLogoSrc, $logoSlotMm, $heightMm); @endphp
                                <td style="width: {{ $logoSlotMm }}mm; height: {{ $heightMm }}mm;">
                                    <img src="{{ $logo['src'] }}" alt="" style="width: {{ $logo['width'] }}mm; height: {{ $logo['height'] }}mm;">
                                </td>
                            @endforeach
                        </tr>
                    </table>
                @endif
            @elseif ($type === 'white_box')
                <div
                    class="template-shape-element"
                    style="left: {{ $x }}mm; top: {{ $y }}mm; width: {{ $widthMm }}mm; height: {{ $heightMm }}mm; background: {{ $element['color'] ?? '#ffffff' }};"
                ></div>
            @elseif (in_array($type, $imageElementTypes, true))
                @php $studioImageSrc = $elementImageSrc($element); @endphp
                @if ($studioImageSrc)
                    <img
                        class="studio-image-element"
                        src="{{ $studioImageSrc }}"
                        alt=""
                        style="left: {{ $x }}mm; top: {{ $y }}mm; width: {{ $widthMm }}mm; height: {{ $heightMm }}mm;"
                    >
                @endif
            @elseif ($type === 'qr_code')
                <div
                    class="template-qr-element"
                    style="left: {{ $x }}mm; top: {{ $y }}mm; width: {{ $widthMm }}mm; height: {{ $heightMm }}mm;"
                >
                    @if ($qrCodeSrc)
                        <img src="{{ $qrCodeSrc }}" alt="QR verifikasi sertifikat">
                    @endif
                </div>
            @else
                <div
                    class="template-text-element"
                    style="left: {{ $x }}mm; top: {{ $y }}mm; width: {{ $widthMm }}mm; height: {{ $heightMm }}mm; font-size: {{ (float) ($element['font_size'] ?? 12) }}pt; font-weight: {{ $element['font_weight'] ?? '400' }}; text-align: {{ $element['align'] ?? 'center' }}; color: {{ $element['color'] ?? '#111827' }};"
                >{{ $elementText($element) }}</div>
            @endif
        @endforeach
    </section>

    <section class="page page-2">
        <div class="side-pattern left">
            @for ($i = 0; $i < 19; $i++)
                <div></div>
            @endfor
        </div>
        <div class="side-pattern right">
            @for ($i = 0; $i < 19; $i++)
                <div></div>
            @endfor
        </div>
        <div class="transcript-card">
            <div class="transcript-head">
                @if ($pageTwoLogoSrcs->isNotEmpty())
                    @php
                        // Baris logo sama dengan halaman 1: slot maksimal 32mm, tinggi 24mm, di tengah.
                        $pageTwoLogoCount = $pageTwoLogoSrcs->count();
                        $pageTwoLogoSlotMm = min(32, (251 / $pageTwoLogoCount) - 2.6);
                    @endphp
                    <table class="partner-logo-row" style="margin: 0 auto;">
                        <tr>
                            @foreach ($pageTwoLogoSrcs as $pageTwoLogoSrc)
                                @php $logo = $fitLogo($pageTwoLogoSrc, $pageTwoLogoSlotMm, 24); @endphp
                                <td style="width: {{ $pageTwoLogoSlotMm }}mm; height: 24mm;">
                                    <img src="{{ $logo['src'] }}" alt="" style="width: {{ $logo['width'] }}mm; height: {{ $logo['height'] }}mm;">
                                </td>
                            @endforeach
                        </tr>
                    </table>
                @endif
                <h1>{{ $eventTitle }}</h1>
                <div class="meta">
                    Program literasi keamanan digital<br>
                    No. Sertifikat: {{ $certificate->number }}<br>
                    Peserta: <strong>{{ $participant?->user?->name ?? '-' }}</strong>{{ $school?->name ? ' - ' . $school->name : '' }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10mm;">No</th>
                        <th>Modul</th>
                        <th style="width: 34mm;">Materi</th>
                        <th style="width: 28mm;">Durasi (menit)</th>
                        <th style="width: 26mm;">JP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jpRows as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row['module'] }}</td>
                            <td>{{ $row['materials'] }}</td>
                            <td class="score">{{ $row['minutes'] ?: '-' }}</td>
                            <td class="score">{{ $row['minutes'] ? $formatJp($row['jp']) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Belum ada data modul.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary">
                Total: {{ $totalMinutes }} menit = {{ $formatJp($totalJp) }} JP (1 JP = {{ $minutesPerJp }} menit)
            </div>
        </div>
        <div class="page-footer">
            <span class="right">Diterbitkan pada {{ $issuedDate }}</span>
        </div>
    </section>
</body>
</html>
