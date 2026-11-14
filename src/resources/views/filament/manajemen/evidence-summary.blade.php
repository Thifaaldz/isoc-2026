<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $short = fn ($t) => str($t)->replace('Digital Safety Champions - ', '');
        $detail = fn ($id) => \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $id]);
    @endphp
    <div class="eo" style="display: grid; gap: 16px;">
        <div class="eo-stats">
            @foreach ($cards as [$label, $value, $color])
                <div class="eo-stat" style="border-top: 4px solid var(--eo-{{ $color === 'gray' ? 'track' : $color }});">
                    <div class="eo-stat-label">{{ $label }}</div>
                    <div class="eo-stat-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <div class="eo-card" style="overflow: hidden;">
            <div class="eo-pad" style="border-bottom: 1px solid var(--eo-border);">
                <p class="eo-h3" style="margin: 0;">Kelengkapan bukti per lokasi</p>
                <p class="eo-muted" style="font-size: 12px;">Foto wajib a-g dan video slogan dihitung setelah disetujui Admin RTIK Pusat.</p>
            </div>
            <div style="overflow-x: auto;">
                <table style="border-collapse: collapse; font-size: 13px; min-width: 760px; width: 100%;">
                    <thead>
                        <tr style="background: var(--eo-soft); color: var(--eo-muted); font-size: 12px; text-align: left;">
                            <th style="padding: 10px 16px;">Lokasi</th>
                            <th style="padding: 10px 12px; width: 170px;">Foto wajib a-g</th>
                            <th style="padding: 10px 12px;">Video slogan</th>
                            <th style="padding: 10px 12px;">Disetujui</th>
                            <th style="padding: 10px 12px;">Menunggu</th>
                            <th style="padding: 10px 16px;">Ditolak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $p = $pct($row['photos_done'], $row['photos_total']); $ev = $row['evidence']; @endphp
                            <tr style="border-top: 1px solid var(--eo-border); color: var(--eo-text);">
                                <td style="padding: 10px 16px;"><a href="{{ $detail($row['event']->id) }}" style="font-weight: 700; color: var(--eo-primary); text-decoration: none;">{{ $short($row['event']->title) }}</a></td>
                                <td style="padding: 10px 12px;">{{ $row['photos_done'] }}<span class="eo-muted"> / {{ $row['photos_total'] }}</span>
                                    <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div></td>
                                <td style="padding: 10px 12px;"><span class="eo-pill {{ $row['video_done'] ? 'success' : 'gray' }}">{{ $row['video_done'] ? 'Ada' : 'Belum' }}</span></td>
                                <td style="padding: 10px 12px;">{{ $ev['approved'] }}</td>
                                <td style="padding: 10px 12px; {{ $ev['pending'] ? 'color: var(--eo-warning); font-weight: 800;' : '' }}">{{ $ev['pending'] }}</td>
                                <td style="padding: 10px 16px; {{ $ev['rejected'] ? 'color: var(--eo-danger); font-weight: 800;' : '' }}">{{ $ev['rejected'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
