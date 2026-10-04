@php
    $label = fn (?string $type) => match ($type) { 'pre' => 'Pre-Test', 'post' => 'Post-Test', 'quiz' => 'Kuis Modul', default => 'Tes' };
@endphp
<div style="display: grid; gap: 10px;">
    @forelse ($attempts as $attempt)
        @php $score = (float) $attempt->score; $color = $score >= 80 ? 'rgb(22, 163, 74)' : ($score >= 60 ? 'rgb(202, 138, 4)' : 'rgb(220, 38, 38)'); @endphp
        <div style="align-items: center; border: 1px solid rgba(148, 163, 184, .35); border-radius: 10px; display: flex; gap: 12px; justify-content: space-between; padding: 10px 12px;">
            <div style="min-width: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .04em; opacity: .65; text-transform: uppercase;">{{ $label($attempt->assessment?->type) }}</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $attempt->assessment?->title }}</div>
                <div style="font-size: 12px; opacity: .65;">Benar {{ $attempt->correct_count }}/{{ $attempt->total_questions }} · {{ $attempt->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</div>
            </div>
            <div style="color: {{ $color }}; font-size: 22px; font-weight: 800;">{{ \App\Filament\Pages\TutorScores::formatScore($attempt->score) }}</div>
        </div>
    @empty
        <p style="font-size: 14px; opacity: .7;">Peserta ini belum mengerjakan tes apa pun.</p>
    @endforelse
</div>
