{{-- Popup detail event saat card lokasi di Kelola Event (Super Admin) diklik. --}}
@php
    $overview = new \App\Support\EventOverview($event);
    $status = $overview->status();
    $next = $overview->nextStep();
    $resource = \App\Filament\Resources\LearningEventResource::class;
    $typeLabel = $resource::eventTypeOptions()[$event->event_type ?: 'offline'] ?? $event->event_type;
    $reportLabel = $resource::finalReportStatusOptions()[$event->final_report_status ?: 'draft'] ?? $event->final_report_status;
@endphp

@include('filament.event-overview.styles')

<div class="eo">
    <div class="eo-row">
        <span class="eo-pill {{ $status['color'] }}">{{ $status['label'] }}</span>
        @if ($overview->countdown())
            <span class="eo-pill gray">{{ $overview->countdown() }}</span>
        @endif
        <span class="eo-pill {{ $event->is_published ? 'success' : 'gray' }}">{{ $event->is_published ? 'Sudah publish' : 'Belum publish' }}</span>
        <span class="eo-pill gray">Laporan: {{ $reportLabel }}</span>
    </div>

    <div class="eo-row" style="gap: 16px;">
        <span class="eo-meta"><x-heroicon-o-calendar-days /> {{ $overview->schedule() }}</span>
        <span class="eo-meta"><x-heroicon-o-map-pin /> {{ $event->school?->name ?? '-' }}</span>
        <span class="eo-meta"><x-heroicon-o-tag /> {{ $typeLabel }}</span>
    </div>

    @include('filament.event-overview.steps')

    <div class="eo-card eo-pad">
        <p class="eo-h3" style="margin-bottom: 4px;">Langkah berikutnya: {{ $next['title'] }}</p>
        <p class="eo-muted">{{ $next['text'] }}</p>
        @if ($next['items'])
            <p class="eo-muted" style="margin-top: 4px;">{{ implode(', ', $next['items']) }}</p>
        @endif
    </div>

    <div class="eo-card eo-pad">
        <p class="eo-h3">Peserta & tutor</p>
        @include('filament.event-overview.stats')
    </div>

    <div class="eo-card eo-pad">
        <p class="eo-h3">Tim tutor ({{ $event->tutors->count() }})</p>
        <div style="display: grid; gap: 8px;">
            @forelse ($event->tutors as $tutor)
                <div class="eo-row eo-between" style="flex-wrap: nowrap;">
                    <div style="min-width: 0;">
                        <div style="color: var(--eo-text); font-size: 14px; font-weight: 700;">{{ $tutor->user?->name }}</div>
                        <div class="eo-muted" style="font-size: 12px;">{{ $tutor->user?->email }}{{ $tutor->user?->phone ? ' · ' . $tutor->user->phone : '' }}</div>
                    </div>
                    <span class="eo-pill {{ $tutor->tot_completed ? 'success' : 'warning' }}">{{ $tutor->tot_completed ? 'Lulus ToT' : 'Belum ToT' }}</span>
                </div>
            @empty
                <p class="eo-muted">Belum ada tutor yang ditugaskan.</p>
            @endforelse
        </div>
    </div>

    <div class="eo-row">
        <a class="eo-btn primary" href="{{ $resource::getUrl('view', ['record' => $event]) }}"><x-heroicon-o-eye /> Buka Preview</a>
        @if ($resource::canEdit($event))
            <a class="eo-btn" href="{{ $resource::getUrl('edit', ['record' => $event]) }}"><x-heroicon-o-pencil-square /> Edit</a>
        @endif
        <a class="eo-btn" href="{{ route('reports.events.activity.preview', $event) }}" target="_blank" rel="noopener"><x-heroicon-o-document-magnifying-glass /> Preview Laporan</a>
    </div>
</div>
