<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $stats = $this->summaryStats();
        $num = fn ($v, int $d = 0) => $v === null ? '-' : number_format((float) $v, $d, ',', '.');
        $attendPct = $stats['registered'] > 0 ? min(100, (int) round($stats['attended'] / $stats['registered'] * 100)) : 0;
    @endphp

    <div class="eo" style="display: grid; gap: 16px;">
        <div class="eo-stats">
            <div class="eo-stat">
                <div class="eo-stat-label">Jumlah kegiatan</div>
                <div class="eo-stat-value">{{ $num($stats['events']) }}</div>
                <div class="eo-muted" style="font-size: 12px;">{{ $stats['period'] }}</div>
            </div>
            <div class="eo-stat">
                <div class="eo-stat-label">Peserta hadir</div>
                <div class="eo-stat-value">{{ $num($stats['attended']) }}<span class="eo-muted" style="font-size: 13px; font-weight: 600;"> / {{ $num($stats['registered']) }}</span></div>
                <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $attendPct }}%; background: var(--eo-info); border-radius: 999px;"></span></div>
                <div class="eo-muted" style="font-size: 12px; margin-top: 4px;">{{ $attendPct }}% dari peserta terdaftar</div>
            </div>
            <div class="eo-stat">
                <div class="eo-stat-label">Rata-rata Pre-Test</div>
                <div class="eo-stat-value">{{ $num($stats['pre'], 1) }}</div>
                <div class="eo-muted" style="font-size: 12px;">Sebelum materi</div>
            </div>
            <div class="eo-stat">
                <div class="eo-stat-label">Rata-rata Post-Test</div>
                <div class="eo-stat-value" style="color: {{ $stats['post'] !== null && $stats['post'] >= 85 ? 'var(--eo-success)' : 'inherit' }};">{{ $num($stats['post'], 1) }}</div>
                <div class="eo-muted" style="font-size: 12px;">
                    @if ($stats['pre'] !== null && $stats['post'] !== null)
                        {{ $stats['post'] >= $stats['pre'] ? 'Naik' : 'Turun' }} {{ $num(abs($stats['post'] - $stats['pre']), 1) }} poin dari Pre-Test
                    @else
                        Setelah materi
                    @endif
                </div>
            </div>
        </div>

        <div style="display: grid; gap: 8px;">
            <div>
                <h2 class="text-base font-bold text-gray-950 dark:text-white">{{ $this->dataHeading() }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->dataDescription() }}</p>
            </div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
