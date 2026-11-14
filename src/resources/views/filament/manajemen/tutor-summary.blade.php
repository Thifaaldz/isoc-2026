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
            @foreach ($cards as [$label, $value, $whole])
                <div class="eo-stat">
                    <div class="eo-stat-label">{{ $label }}</div>
                    <div class="eo-stat-value">{{ $value }}@if ($whole !== null)<span class="eo-muted" style="font-size: 13px; font-weight: 600;"> / {{ $whole }}</span>@endif</div>
                    @if ($whole !== null)
                        @php $p = $pct($value, $whole); @endphp
                        <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div>
                    @endif
                </div>
            @endforeach
            <div class="eo-stat">
                <div class="eo-stat-label">Rata-rata nilai ToT</div>
                <div class="eo-stat-value">{{ $avg !== null ? number_format($avg, 1, ',', '.') : '-' }}</div>
            </div>
        </div>
        <div class="eo-card" style="overflow: hidden;">
            <div class="eo-pad" style="border-bottom: 1px solid var(--eo-border);">
                <p class="eo-h3" style="margin: 0;">Kesiapan tutor per lokasi</p>
                <p class="eo-muted" style="font-size: 12px;">Kuota tutor dan kelulusan ToT. Klik lokasi untuk detail.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 0;">
                @foreach ($rows as $row)
                    @php $s = $row['stats']; $whole = max($s['tutors'], $s['tutors_target']); $p = $pct($s['tutors_tot'], $whole); @endphp
                    <a href="{{ $detail($row['event']->id) }}" style="padding: 12px 16px; border-top: 1px solid var(--eo-border); border-right: 1px solid var(--eo-border); text-decoration: none;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--eo-text);"><strong>{{ $short($row['event']->title) }}</strong><span>{{ $s['tutors_tot'] }}/{{ $whole }} lulus</span></div>
                        <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div>
                        <div class="eo-muted" style="font-size: 12px; margin-top: 4px;">{{ $s['tutors'] }} ditugaskan · kuota {{ $s['tutors_target'] }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
