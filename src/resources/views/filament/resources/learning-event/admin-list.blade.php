<x-filament-panels::page>
    @include('filament.event-overview.styles')

    <div class="eo">
        @forelse ($this->adminEvents as $event)
            @php
                $overview = new \App\Support\EventOverview($event);
                $status = $overview->status();
                $next = $overview->nextStep();
            @endphp
            <div class="eo-card eo-pad" style="display: grid; gap: 16px;" wire:key="eo-{{ $event->id }}">
                <div class="eo-row eo-between" style="align-items: flex-start;">
                    <div style="display: grid; gap: 6px; min-width: 0;">
                        <div class="eo-row">
                            <span class="eo-pill {{ $status['color'] }}">{{ $status['label'] }}</span>
                            @if ($overview->countdown())
                                <span class="eo-pill gray">{{ $overview->countdown() }}</span>
                            @endif
                        </div>
                        <p class="eo-title">{{ $event->title }}</p>
                        <div class="eo-row" style="gap: 16px;">
                            <span class="eo-meta"><x-heroicon-o-calendar-days /> {{ $overview->schedule() }}</span>
                            <span class="eo-meta"><x-heroicon-o-map-pin /> {{ $event->school?->name ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="eo-row">
                        <a class="eo-btn primary" href="{{ \App\Filament\Resources\LearningEventResource::getUrl('view', ['record' => $event]) }}">
                            <x-heroicon-o-eye /> Buka Event
                        </a>
                        @if (\App\Filament\Resources\LearningEventResource::canEdit($event) && ! in_array($event->workflow_status, ['cancelled', 'closed'], true))
                            <a class="eo-btn" href="{{ \App\Filament\Resources\LearningEventResource::getUrl('edit', ['record' => $event]) }}">
                                <x-heroicon-o-pencil-square /> Edit
                            </a>
                        @endif
                    </div>
                </div>

                @include('filament.event-overview.steps')
                @include('filament.event-overview.stats')

                <div class="eo-row eo-between" style="align-items: flex-start;">
                    <div style="flex: 1 1 320px;">
                        <p class="eo-muted"><strong style="color: var(--eo-text);">Langkah berikutnya:</strong> {{ $next['title'] }} — {{ $next['text'] }}</p>
                        @if ($next['items'])
                            <p class="eo-muted" style="margin-top: 4px;">{{ implode(', ', $next['items']) }}</p>
                        @endif
                    </div>
                    <div class="eo-row">
                        @foreach ($overview->quickLinks() as $link)
                            <a class="eo-btn" href="{{ $link['url'] }}"><x-dynamic-component :component="$link['icon']" /> {{ $link['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="eo-card eo-pad">
                <p class="eo-title" style="font-size: 16px;">Belum ada event</p>
                <p class="eo-muted">Event lokus Anda disiapkan oleh RTIK Pusat dan akan muncul di sini.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
