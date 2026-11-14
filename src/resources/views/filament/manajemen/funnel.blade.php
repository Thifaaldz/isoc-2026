<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $stageColors = ['#94a3b8', '#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#16a34a'];
    @endphp
    <div class="eo">
        {{-- Corong peserta --}}
        <div class="eo-card eo-pad">
            <p class="eo-h3" style="margin: 0;">Corong peserta seluruh lokasi</p>
            <p class="eo-muted" style="font-size: 12px; margin-bottom: 14px;">Jumlah pendaftaran di semua event (satu siswa bisa ikut lebih dari satu event). Persentase dibanding target {{ number_format($target, 0, ',', '.') }} siswa dan dibanding jumlah terdaftar.</p>
            @php $registered = $funnel[0]['value']; @endphp
            <div style="display: grid; gap: 10px;">
                @foreach ($funnel as $step)
                    @php $p = $pct($step['value'], $target); @endphp
                    <div style="display: grid; grid-template-columns: minmax(110px, 160px) 1fr auto; gap: 12px; align-items: center;">
                        <div style="font-size: 13px; font-weight: 700; color: var(--eo-text);">{{ $step['label'] }}</div>
                        <div class="eo-bar" style="height: 12px; margin: 0;"><span style="display: block; height: 100%; width: {{ max($p, $step['value'] > 0 ? 1 : 0) }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div>
                        <div style="font-size: 13px; color: var(--eo-text); white-space: nowrap; text-align: right; min-width: 120px;">
                            <strong>{{ number_format($step['value'], 0, ',', '.') }}</strong>
                            <span class="eo-muted" style="font-size: 12px;">· {{ $p }}%@if (! $loop->first) · {{ $pct($step['value'], $registered) }}% dari terdaftar @endif</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
