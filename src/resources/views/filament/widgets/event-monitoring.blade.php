<x-filament-widgets::widget>
    <style>
        .em-wrap { display: grid; gap: 16px; }
        .em-head { align-items: flex-start; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; }
        .em-title { font-size: 16px; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
        .em-meta { align-items: center; color: rgb(100, 116, 139); display: flex; flex-wrap: wrap; font-size: 13px; gap: 6px 14px; }
        .em-meta span { align-items: center; display: inline-flex; gap: 5px; }
        .em-meta svg { height: 15px; width: 15px; }
        .em-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr)); margin-top: 14px; }
        .em-tile { border: 1px solid rgba(148, 163, 184, .35); border-radius: 12px; display: grid; gap: 8px; min-width: 0; padding: 14px; align-content: start; }
        .dark .em-tile { border-color: rgba(255, 255, 255, .1); }
        .em-label { align-items: center; display: flex; font-size: 13px; font-weight: 700; gap: 6px; }
        .em-label svg { color: rgb(var(--primary-500)); height: 18px; width: 18px; }
        .em-value { font-size: 26px; font-weight: 800; line-height: 1; }
        .em-value small { color: rgb(100, 116, 139); font-size: 14px; font-weight: 600; }
        .em-bar { background: rgba(148, 163, 184, .25); border-radius: 999px; height: 7px; overflow: hidden; }
        .em-bar span { display: block; height: 100%; }
        .em-note { color: rgb(100, 116, 139); font-size: 12px; }
        .em-missing summary { color: rgb(var(--primary-600)); cursor: pointer; font-size: 12px; font-weight: 600; }
        .em-missing ul { color: rgb(71, 85, 105); font-size: 12px; margin: 6px 0 0; max-height: 160px; overflow-y: auto; padding-left: 18px; }
        .dark .em-missing ul { color: rgb(203, 213, 225); }
        .em-links { display: flex; flex-wrap: wrap; gap: 10px; font-size: 12px; }
        .em-links a { color: rgb(var(--primary-600)); font-weight: 600; }
    </style>

    <div class="em-wrap" wire:poll.60s>
        @forelse ($events as $row)
            @php $event = $row['event']; @endphp
            <x-filament::section wire:key="em-{{ $event->id }}">
                <div class="em-head">
                    <div style="min-width: 0;">
                        <p class="em-title">Monitoring: {{ $event->title }}</p>
                        <div class="em-meta">
                            <span><x-heroicon-m-map-pin /> {{ $event->school?->name ?? '-' }}</span>
                            <span><x-heroicon-m-calendar-days /> {{ $event->starts_at?->translatedFormat('d M Y') ?? '-' }}</span>
                            <span><x-heroicon-m-user-group /> {{ $row['total'] }} peserta terdaftar</span>
                        </div>
                    </div>
                    <x-filament::button tag="a" :href="$row['recapUrl']" size="sm" color="gray" icon="heroicon-o-table-cells">Rekap peserta lengkap</x-filament::button>
                </div>

                <div class="em-grid">
                    @foreach ($row['metrics'] as $metric)
                        @php
                            $pct = $row['total'] ? min(100, (int) round($metric['done'] / $row['total'] * 100)) : 0;
                            $color = $pct >= 100 ? 'rgb(22, 163, 74)' : ($pct >= 50 ? 'rgb(217, 119, 6)' : 'rgb(220, 38, 38)');
                        @endphp
                        <div class="em-tile">
                            <div class="em-label"><x-dynamic-component :component="$metric['icon']" /> {{ $metric['label'] }}</div>
                            <div class="em-value">{{ $metric['done'] }}<small> / {{ $row['total'] }} · {{ $pct }}%</small></div>
                            <div class="em-bar"><span style="width: {{ $pct }}%; background: {{ $row['total'] ? $color : 'transparent' }};"></span></div>
                            @if ($metric['note'])
                                <div class="em-note">{{ $metric['note'] }}</div>
                            @endif
                            @if ($metric['missing']->isNotEmpty())
                                <details class="em-missing">
                                    <summary>{{ $metric['missingLabel'] }} ({{ $metric['missing']->count() }})</summary>
                                    <ul>
                                        @foreach ($metric['missing'] as $name)
                                            <li>{{ $name }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @elseif ($row['total'])
                                <div class="em-note" style="color: rgb(22, 163, 74); font-weight: 600;">Semua peserta sudah</div>
                            @endif
                            <div class="em-links">
                                <a href="{{ $metric['url'] }}">Lihat data</a>
                                @if ($metric['extraUrl'])
                                    <a href="{{ $metric['extraUrl'][1] }}">{{ $metric['extraUrl'][0] }}</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <p class="em-note" style="font-size: 14px;">Belum ada event yang Anda kelola untuk dimonitor.</p>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-widgets::widget>
