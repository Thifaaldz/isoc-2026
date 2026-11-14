<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @include('filament.manajemen.theme')
    @php $colors = ['#94a3b8', '#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#16a34a']; @endphp
    <div class="eo mg">
        <div class="eo-card eo-pad" style="display: grid; gap: 16px;">
            <div class="eo-row eo-between">
                <div>
                    <p class="eo-h3" style="margin: 0;">Tahapan seluruh lokasi</p>
                    <p class="eo-muted" style="font-size: 12px;">Posisi {{ $total }} lokasi saat ini. Alur: Disetujui Pusat → Termin-1 & pendaftaran → Pelaksanaan → Laporan final → Termin-2.</p>
                </div>
                <a href="{{ \App\Filament\Manajemen\Pages\LocationProgressPage::getUrl() }}" class="eo-btn">Lihat progres lokasi →</a>
            </div>
            <div class="mg-dist" role="img" aria-label="Distribusi lokasi per tahap">
                @foreach ($stages as $i => $stage)
                    @if ($stage['count'] > 0)
                        <span title="{{ $stage['label'] }}: {{ $stage['count'] }} lokasi" style="width: {{ $total ? $stage['count'] / $total * 100 : 0 }}%; background: {{ $colors[$i] }};"></span>
                    @endif
                @endforeach
            </div>
            <div class="mg-pipeline">
                @foreach ($stages as $i => $stage)
                    <div class="mg-stage" style="{{ $stage['count'] ? 'border-color: ' . $colors[$i] . ';' : 'opacity: .7;' }}">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="mg-stage-num" style="background: {{ $colors[$i] }};">{{ $i < 5 ? $i + 1 : '✓' }}</span>
                            <span style="font-size: 12.5px; font-weight: 800; color: var(--eo-text); line-height: 1.3;">{{ $stage['label'] }}</span>
                        </div>
                        <div style="font-size: 26px; font-weight: 800; color: var(--eo-text); margin-top: 8px; line-height: 1;">{{ $stage['count'] }}</div>
                        <div class="eo-muted" style="font-size: 11.5px; margin-top: 2px;">lokasi · {{ $total ? round($stage['count'] / $total * 100) : 0 }}%</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
