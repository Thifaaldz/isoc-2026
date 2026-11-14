<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @include('filament.manajemen.theme')
    @php
        $ring = 2 * M_PI * 52;
        $groupIcons = ['Jangkauan' => 'heroicon-o-globe-asia-australia', 'Kualitas belajar' => 'heroicon-o-light-bulb', 'Penyelesaian' => 'heroicon-o-flag'];
    @endphp
    <div class="eo mg" style="display: grid; gap: 22px;">
        {{-- Banner progres program --}}
        <div class="mg-hero">
            <div style="display: flex; align-items: center; gap: 22px; flex-wrap: wrap;">
                <svg width="128" height="128" viewBox="0 0 128 128" style="flex: none;" role="img" aria-label="Progres program {{ $progress }} persen">
                    <circle cx="64" cy="64" r="52" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="12"/>
                    <circle cx="64" cy="64" r="52" fill="none" stroke="#5eead4" stroke-width="12" stroke-linecap="round"
                            stroke-dasharray="{{ $ring }}" stroke-dashoffset="{{ $ring * (1 - $progress / 100) }}" transform="rotate(-90 64 64)"/>
                    <text x="64" y="62" text-anchor="middle" fill="#fff" font-size="28" font-weight="800">{{ $progress }}%</text>
                    <text x="64" y="82" text-anchor="middle" fill="rgba(255,255,255,.75)" font-size="11" font-weight="600">progres</text>
                </svg>
                <div style="min-width: 0; flex: 1;">
                    <div class="mg-eyebrow">Program Digital Safety Champions</div>
                    <div style="font-size: 22px; font-weight: 800; line-height: 1.25; margin-top: 4px;">{{ $events }} lokasi sedang berjalan</div>
                    <div style="opacity: .8; font-size: 13px; margin-top: 4px;">Tahap terbanyak: <strong>{{ $stageNow['label'] ?? '-' }}</strong> ({{ $stageNow['count'] ?? 0 }} lokasi) · diperbarui {{ $updated }} WIB</div>
                </div>
            </div>
            <div class="mg-hero-stats">
                @foreach ($hero as [$label, $value])
                    <div>
                        <div style="font-size: 24px; font-weight: 800; line-height: 1.1;">{{ $value }}</div>
                        <div style="font-size: 12px; opacity: .75; margin-top: 2px;">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- KPI per kelompok --}}
        @foreach ($groups as $group => $cards)
            <section style="display: grid; gap: 10px;">
                <div class="mg-section-title">
                    <x-filament::icon :icon="$groupIcons[$group]" style="width: 18px; height: 18px;" />
                    {{ $group }}
                </div>
                <div class="mg-kpi-grid">
                    @foreach ($cards as $c)
                        @php
                            $p = $c['target'] ? min(100, (int) round($c['value'] / $c['target'] * 100)) : null;
                            $met = $p !== null && $c['value'] >= $c['target'];
                            $tone = $p === null ? 'info' : ($met ? 'success' : ($p >= 50 ? 'info' : 'warning'));
                        @endphp
                        <a href="{{ $c['url'] }}" class="mg-kpi">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                <span class="mg-icon {{ $tone }}"><x-filament::icon :icon="$c['icon']" style="width: 20px; height: 20px;" /></span>
                                @if ($p !== null)
                                    <span class="eo-pill {{ $met ? 'success' : 'gray' }}" style="font-size: 11px;">{{ $met ? 'Tercapai' : $p . '% dari target' }}</span>
                                @endif
                            </div>
                            <div class="mg-kpi-label">{{ $c['label'] }}</div>
                            <div class="mg-kpi-value">{{ $c['shown'] }}</div>
                            @if ($p !== null)
                                <div class="eo-bar" style="height: 6px; margin-top: 10px;"><span style="display: block; height: 100%; width: {{ max($p, $c['value'] > 0 ? 2 : 0) }}%; background: var(--eo-{{ $tone }}); border-radius: 999px;"></span></div>
                            @endif
                            <div class="mg-kpi-hint">{{ $c['hint'] }}</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-filament-widgets::widget>
