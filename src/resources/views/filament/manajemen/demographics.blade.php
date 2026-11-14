<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $short = fn ($t) => str($t)->replace('Digital Safety Champions - ', '');
        $detail = fn ($id) => \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $id]);
    @endphp
    @php
        $groups = ['Jenis kelamin' => $gender, 'Kategori' => $category, 'Kelas' => $grade, 'Keterlibatan' => $engagement];
        $colors = ['#6366f1', '#14b8a6', '#f59e0b', '#e11d48', '#0ea5e9', '#8b5cf6', '#16a34a', '#64748b'];
    @endphp
    <div class="eo">
        <div class="eo-card eo-pad">
            <p class="eo-h3" style="margin: 0;">Profil peserta</p>
            <p class="eo-muted" style="font-size: 12px; margin-bottom: 14px;">Dari {{ number_format($total, 0, ',', '.') }} peserta (orang unik). Persentase terhadap seluruh peserta.</p>
            <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                @foreach ($groups as $title => $items)
                    <div>
                        <div class="eo-stat-label" style="margin-bottom: 8px;">{{ $title }}</div>
                        @forelse ($items as $label => $value)
                            @php $p = $pct($value, $total); @endphp
                            <div style="margin-bottom: 8px;">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; color: var(--eo-text);"><span>{{ $label }}</span><span><strong>{{ $value }}</strong> <span class="eo-muted" style="font-size: 12px;">· {{ $p }}%</span></span></div>
                                <div class="eo-bar" style="margin-top: 4px;"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $colors[$loop->index % count($colors)] }}; border-radius: 999px;"></span></div>
                            </div>
                        @empty
                            <p class="eo-muted">Belum ada data.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
