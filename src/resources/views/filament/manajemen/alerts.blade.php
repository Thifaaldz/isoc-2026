<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $short = fn ($t) => str($t)->replace('Digital Safety Champions - ', '');
        $detail = fn ($id) => \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $id]);
    @endphp
    <div class="eo">
        <div class="eo-card eo-pad">
            <div class="eo-row eo-between" style="margin-bottom: 8px;">
                <div>
                    <p class="eo-h3" style="margin: 0;">Perlu perhatian</p>
                    <p class="eo-muted" style="font-size: 12px;">Dihitung otomatis dari data terbaru. Klik untuk melihat detail lokasi.</p>
                </div>
                <span class="eo-count">{{ $total }}</span>
            </div>
            @forelse ($alerts as $a)
                <a href="{{ $detail($a['event']->id) }}" style="display: flex; gap: 10px; align-items: center; padding: 8px 0; border-top: 1px solid var(--eo-border); text-decoration: none;">
                    <span style="flex: none; width: 8px; height: 8px; border-radius: 999px; background: var(--eo-{{ $a['level'] }});"></span>
                    <span style="font-size: 13px; font-weight: 800; color: var(--eo-text); min-width: 130px;">{{ $a['name'] }}</span>
                    <span class="eo-muted">{{ $a['text'] }}</span>
                </a>
            @empty
                <p class="eo-muted" style="padding: 8px 0;">Tidak ada yang perlu perhatian saat ini.</p>
            @endforelse
            @if ($more)
                <p class="eo-muted" style="font-size: 12px; padding-top: 6px;">+{{ $more }} lainnya. Lihat menu Progres Lokasi.</p>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
