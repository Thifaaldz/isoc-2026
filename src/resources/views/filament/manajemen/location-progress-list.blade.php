<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php $tone = fn (int $p) => $p >= 80 ? 'success' : ($p >= 40 ? 'info' : 'warning'); @endphp
    <div class="eo" style="height: 100%;">
        <div class="eo-card" style="height: 100%; display: flex; flex-direction: column;">
            <div class="eo-pad" style="border-bottom: 1px solid var(--eo-border);">
                <p class="eo-h3" style="margin: 0;">Progres tiap lokasi</p>
                <div class="eo-row" style="gap: 6px; margin-top: 8px;">
                    <span class="eo-pill success">≥ 80%</span>
                    <span class="eo-pill info">40–79%</span>
                    <span class="eo-pill warning">&lt; 40%</span>
                </div>
            </div>
            <div style="max-height: 440px; overflow-y: auto; padding: 6px 20px 12px;">
                @foreach ($rows as $row)
                    @php $t = $tone($row['progress']); @endphp
                    <a href="{{ $row['url'] }}" style="display: block; padding: 9px 0; border-top: {{ $loop->first ? '0' : '1px solid var(--eo-border)' }}; text-decoration: none;">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 10px;">
                            <span style="font-size: 13.5px; font-weight: 700; color: var(--eo-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $row['name'] }}</span>
                            <span style="font-size: 13.5px; font-weight: 800; color: var(--eo-{{ $t }}); flex: none;">{{ $row['progress'] }}%</span>
                        </div>
                        <div class="eo-bar" style="height: 8px; margin-top: 6px;"><span style="display: block; height: 100%; width: {{ max($row['progress'], 2) }}%; background: var(--eo-{{ $t }}); border-radius: 999px;"></span></div>
                        <div class="eo-muted" style="font-size: 12px; margin-top: 4px;">Tahap: {{ $row['stage'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
