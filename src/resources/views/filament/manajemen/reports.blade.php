<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $stageColors = ['#94a3b8', '#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#16a34a'];
    @endphp
    <div class="eo" style="display: grid; gap: 16px;">
        <div class="eo-stats">
            @foreach ($summary as $item)
                <div class="eo-stat">
                    <div class="eo-stat-label">{{ $item['label'] }}</div>
                    <div class="eo-stat-value">{{ $item['value'] }}<span class="eo-muted" style="font-size: 13px; font-weight: 600;"> / {{ $total }}</span></div>
                    <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $pct($item['value'], $total) }}%; background: {{ $barColor($pct($item['value'], $total)) }}; border-radius: 999px;"></span></div>
                </div>
            @endforeach
        </div>

        <div class="eo-card" style="overflow: hidden;">
            <div class="eo-pad" style="border-bottom: 1px solid var(--eo-border);">
                <p class="eo-h3" style="margin: 0;">Laporan final & termin per lokasi</p>
                <p class="eo-muted" style="font-size: 12px;">Termin-1 cair setelah event disetujui & dipublish; Termin-2 cair setelah laporan final (13 syarat) disetujui Pusat.</p>
            </div>
            <div style="overflow-x: auto;">
                <table style="border-collapse: collapse; font-size: 13px; min-width: 860px; width: 100%;">
                    <thead>
                        <tr style="background: var(--eo-soft); color: var(--eo-muted); font-size: 12px; text-align: left;">
                            <th style="padding: 10px 16px;">Lokasi</th>
                            <th style="padding: 10px 12px;">Jadwal</th>
                            <th style="padding: 10px 12px;">Termin-1</th>
                            <th style="padding: 10px 12px; width: 160px;">Syarat laporan</th>
                            <th style="padding: 10px 12px;">Laporan final</th>
                            <th style="padding: 10px 16px;">Termin-2</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $e = $row['event']; $p = $pct($row['proof_done'], $row['proof_total']); @endphp
                            <tr style="border-top: 1px solid var(--eo-border); color: var(--eo-text);">
                                <td style="padding: 10px 16px;">
                                    <a href="{{ \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $e->id]) }}" style="font-weight: 700; color: var(--eo-primary); text-decoration: none;">{{ str($e->title)->replace('Digital Safety Champions - ', '') }}</a>
                                    <div class="eo-muted" style="font-size: 12px;">{{ $e->school?->name }}</div>
                                </td>
                                <td style="padding: 10px 12px; white-space: nowrap;">
                                    {{ $e->starts_at?->translatedFormat('d M Y') ?? '-' }}
                                    <div class="eo-muted" style="font-size: 12px;">{{ $row['overview']->countdown() }}</div>
                                </td>
                                <td style="padding: 10px 12px;"><span class="eo-pill {{ $row['term1']['color'] }}">{{ $row['term1']['label'] }}</span></td>
                                <td style="padding: 10px 12px;">
                                    {{ $row['proof_done'] }}<span class="eo-muted"> / {{ $row['proof_total'] }}</span>
                                    <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div>
                                </td>
                                <td style="padding: 10px 12px;"><span class="eo-pill {{ $row['report']['color'] }}">{{ $row['report']['label'] }}</span></td>
                                <td style="padding: 10px 16px;"><span class="eo-pill {{ $row['term2']['color'] }}">{{ $row['term2']['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="eo-muted" style="padding: 24px 16px; text-align: center;">Belum ada event.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
