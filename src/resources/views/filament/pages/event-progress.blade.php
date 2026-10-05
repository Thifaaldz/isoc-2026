<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $event = $this->event;
        $overview = $this->overview;
        $status = $overview->status();
        $next = $overview->nextStep();
        $sections = $this->sections;
        $all = collect($sections)->flatMap(fn ($section) => $section['items']);
        $doneAll = $all->where('done', true)->count();
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
    @endphp

    <style>
        .ep-sum { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr)); }
        .ep-cols { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); align-items: start; }
        .ep-item { align-items: flex-start; border-top: 1px solid var(--eo-border); display: flex; gap: 10px; padding: 10px 0; text-decoration: none; }
        .ep-item:first-child { border-top: 0; }
        .ep-item svg { flex: 0 0 20px; height: 20px; width: 20px; margin-top: 1px; }
        .ep-item.done svg { color: var(--eo-success); }
        .ep-item.todo svg { color: var(--eo-danger); }
        .ep-item-label { color: var(--eo-text); font-size: 14px; font-weight: 700; line-height: 1.4; }
        .ep-item:hover .ep-item-label { color: var(--eo-primary); }
        .ep-list-title { align-items: center; display: flex; font-size: 13px; font-weight: 800; gap: 6px; margin: 0 0 4px; }
    </style>

    <div class="eo">
        <div class="eo-card eo-pad" style="display: grid; gap: 12px;">
            <div class="eo-row eo-between" style="align-items: flex-start;">
                <div style="display: grid; gap: 6px; min-width: 0;">
                    <div class="eo-row">
                        <span class="eo-pill {{ $status['color'] }}">{{ $status['label'] }}</span>
                        @if ($overview->countdown())
                            <span class="eo-pill gray">{{ $overview->countdown() }}</span>
                        @endif
                        <span class="eo-pill {{ $overview->finalReportStatus()['color'] }}">Laporan: {{ $overview->finalReportStatus()['label'] }}</span>
                    </div>
                    <p class="eo-title">{{ $event->title }}</p>
                    <div class="eo-row" style="gap: 16px;">
                        <span class="eo-meta"><x-heroicon-o-calendar-days /> {{ $overview->schedule() }}</span>
                        <span class="eo-meta"><x-heroicon-o-map-pin /> {{ $event->school?->name ?? '-' }}</span>
                        @if ($event->creator)
                            <span class="eo-meta"><x-heroicon-o-user /> {{ $event->creator->name }} · {{ $event->creator->email }}</span>
                        @endif
                    </div>
                </div>
                <div class="eo-row">
                    <a class="eo-btn" href="{{ \Filament\Pages\Dashboard::getUrl() }}"><x-heroicon-o-arrow-left /> Dashboard</a>
                    <a class="eo-btn primary" href="{{ \App\Filament\Resources\LearningEventResource::getUrl('view', ['record' => $event]) }}"><x-heroicon-o-eye /> Detail event</a>
                    <button type="button" class="eo-btn" wire:click="recalculate" wire:loading.attr="disabled" wire:target="recalculate"
                        wire:confirm="Hitung ulang laporan event ini? Nilai indikator yang kosong diisi, sertifikat peserta yang memenuhi syarat diterbitkan, dan PDF bukti sistem dibuat ulang.">
                        <x-heroicon-o-arrow-path wire:loading.class="animate-spin" wire:target="recalculate" />
                        <span wire:loading.remove wire:target="recalculate">Hitung ulang laporan</span>
                        <span wire:loading wire:target="recalculate">Menghitung…</span>
                    </button>
                    <a class="eo-btn" href="{{ route('reports.events.activity.preview', $event) }}" target="_blank" rel="noopener"><x-heroicon-o-document-magnifying-glass /> Preview laporan</a>
                </div>
            </div>
            @include('filament.event-overview.steps')
        </div>

        <div class="ep-sum">
            <div class="eo-card eo-pad">
                <div class="eo-muted">Checklist terpenuhi</div>
                <div style="font-size: 28px; font-weight: 800; color: var(--eo-text);">{{ $doneAll }}<span class="eo-muted" style="font-size: 15px;">/{{ $all->count() }}</span></div>
                <div class="eo-bar" style="margin-top: 6px;"><span style="background: var(--eo-success); width: {{ $pct($doneAll, $all->count()) }}%;"></span></div>
            </div>
            @foreach ($sections as $section)
                @php $done = collect($section['items'])->where('done', true)->count(); $total = count($section['items']); @endphp
                <div class="eo-card eo-pad">
                    <div class="eo-muted">{{ $section['title'] }}</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--eo-text);">{{ $done }}<span class="eo-muted" style="font-size: 15px;">/{{ $total }}</span></div>
                    <div class="eo-bar" style="margin-top: 6px;"><span style="background: {{ $done >= $total ? 'var(--eo-success)' : 'var(--eo-warning)' }}; width: {{ $pct($done, $total) }}%;"></span></div>
                </div>
            @endforeach
        </div>

        <div class="eo-card eo-pad">
            <p class="eo-h3" style="margin-bottom: 4px;">Langkah berikutnya: {{ $next['title'] }}</p>
            <p class="eo-muted">{{ $next['text'] }}</p>
        </div>

        @foreach ($sections as $section)
            @php
                $todo = collect($section['items'])->where('done', false);
                $done = collect($section['items'])->where('done', true);
            @endphp
            <div class="eo-card eo-pad" wire:key="ep-{{ $loop->index }}">
                <div class="eo-row eo-between" style="margin-bottom: 10px;">
                    <div>
                        <p class="eo-h3" style="margin: 0;">{{ $section['title'] }}</p>
                        @if ($section['note'])
                            <p class="eo-muted" style="font-size: 12px;">{{ $section['note'] }}</p>
                        @endif
                    </div>
                    <span class="eo-pill {{ $todo->isEmpty() ? 'success' : 'warning' }}">{{ $done->count() }}/{{ count($section['items']) }} terpenuhi</span>
                </div>
                <div class="ep-cols">
                    <div>
                        <p class="ep-list-title" style="color: var(--eo-danger);"><x-heroicon-s-x-circle style="width: 16px; height: 16px;" /> Belum terpenuhi ({{ $todo->count() }})</p>
                        @forelse ($todo as $item)
                            <a class="ep-item todo" href="{{ $item['link'] }}">
                                <x-heroicon-s-x-circle />
                                <div style="min-width: 0;">
                                    <div class="ep-item-label">{{ $item['label'] }}</div>
                                    @if ($item['detail'])
                                        <div class="eo-muted" style="font-size: 12px;">{{ $item['detail'] }}</div>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="eo-muted" style="padding: 10px 0;">Semua sudah terpenuhi.</p>
                        @endforelse
                    </div>
                    <div>
                        <p class="ep-list-title" style="color: var(--eo-success);"><x-heroicon-s-check-circle style="width: 16px; height: 16px;" /> Sudah terpenuhi ({{ $done->count() }})</p>
                        @forelse ($done as $item)
                            <a class="ep-item done" href="{{ $item['link'] }}">
                                <x-heroicon-s-check-circle />
                                <div style="min-width: 0;">
                                    <div class="ep-item-label">{{ $item['label'] }}</div>
                                    @if ($item['detail'])
                                        <div class="eo-muted" style="font-size: 12px;">{{ $item['detail'] }}</div>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="eo-muted" style="padding: 10px 0;">Belum ada.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach

        <div class="eo-card eo-pad">
            <p class="eo-h3">Peserta & tutor</p>
            @include('filament.event-overview.stats')
        </div>
    </div>
</x-filament-panels::page>
