<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    <div class="eo">
        <div class="eo-card eo-pad" style="display: grid; gap: 14px;">
            <div class="eo-row eo-between">
                <div>
                    <p class="eo-title" style="font-size: 18px;">Perlu tindakan</p>
                    <p class="eo-muted">Hal yang menunggu keputusan Admin RTIK Pusat. Klik untuk langsung membuka daftarnya.</p>
                </div>
                <span class="eo-pill {{ collect($actions)->sum('count') > 0 ? 'warning' : 'success' }}">
                    {{ collect($actions)->sum('count') > 0 ? collect($actions)->sum('count') . ' item menunggu' : 'Semua beres' }}
                </span>
            </div>
            <div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}" class="eo-card" style="display: grid; gap: 8px; padding: 14px 16px; text-decoration: none; border-color: {{ $action['count'] > 0 ? 'var(--eo-' . ($action['color'] === 'danger' ? 'danger' : ($action['color'] === 'warning' ? 'warning' : ($action['color'] === 'success' ? 'success' : 'info'))) . ')' : 'var(--eo-border)' }};">
                        <div class="eo-row eo-between" style="flex-wrap: nowrap;">
                            <span class="eo-pill {{ $action['count'] > 0 ? $action['color'] : 'gray' }}" style="padding: 6px;"><x-dynamic-component :component="$action['icon']" style="height: 18px; width: 18px;" /></span>
                            <span style="color: var(--eo-text); font-size: 30px; font-weight: 800; line-height: 1;">{{ $action['count'] }}</span>
                        </div>
                        <div style="color: var(--eo-text); font-size: 14px; font-weight: 800;">{{ $action['label'] }}</div>
                        <div class="eo-muted" style="font-size: 12px;">{{ $action['hint'] }}</div>
                        <span style="color: var(--eo-primary); font-size: 12.5px; font-weight: 700;">{{ $action['cta'] }} →</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="eo-card eo-pad" style="display: grid; gap: 12px;">
            <div class="eo-row eo-between">
                <p class="eo-h3" style="margin: 0;">Tahapan program ({{ $pipeline['total'] }} lokasi)</p>
                <span class="eo-muted" style="font-size: 12px;">Posisi tiap lokasi pada tahap yang sedang berjalan</span>
            </div>
            <div style="display: flex; height: 14px; border-radius: 999px; overflow: hidden; background: var(--eo-track);">
                @php $colors = ['#94a3b8', '#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#16a34a']; @endphp
                @foreach ($pipeline['steps'] as $i => $step)
                    @if ($step['count'] > 0)
                        <span title="{{ $step['title'] }}: {{ $step['count'] }}" style="background: {{ $colors[$i] }}; width: {{ $step['count'] / max(1, $pipeline['total']) * 100 }}%;"></span>
                    @endif
                @endforeach
            </div>
            <div style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
                @foreach ($pipeline['steps'] as $i => $step)
                    <div class="eo-stat" style="padding: 10px 12px;">
                        <div class="eo-row" style="gap: 6px; flex-wrap: nowrap;"><span style="background: {{ $colors[$i] }}; border-radius: 999px; height: 10px; width: 10px; flex: 0 0 10px;"></span><span class="eo-stat-label">{{ $step['title'] }}</span></div>
                        <div class="eo-stat-value">{{ $step['count'] }}<small> lokasi</small></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
