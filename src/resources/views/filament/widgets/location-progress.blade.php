<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0; @endphp
    <div class="eo">
        <div class="eo-card" style="overflow: hidden;">
            <div class="eo-pad eo-row eo-between" style="border-bottom: 1px solid var(--eo-border);">
                <div>
                    <p class="eo-h3" style="margin: 0;">Progres per lokasi</p>
                    <p class="eo-muted" style="font-size: 12px;">Klik baris untuk membuka detail event.</p>
                </div>
                <div class="eo-row" style="gap: 6px;">
                    @foreach (['all' => 'Semua', 'action' => 'Perlu tindakan', 'upcoming' => 'Akan datang', 'finished' => 'Sudah berjalan'] as $key => $label)
                        <button type="button" wire:click="$set('filter', '{{ $key }}')" class="eo-btn {{ $filter === $key ? 'primary' : '' }}" style="padding: 5px 10px; font-size: 12px;">
                            {{ $label }} <span style="opacity: .7;">{{ $counts[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table style="border-collapse: collapse; font-size: 13px; min-width: 900px; width: 100%;">
                    <thead>
                        <tr style="background: var(--eo-soft); color: var(--eo-muted); font-size: 12px; text-align: left;">
                            <th style="padding: 10px 16px;">Lokasi</th>
                            <th style="padding: 10px 12px;">Jadwal</th>
                            <th style="padding: 10px 12px;">Status</th>
                            <th style="padding: 10px 12px; width: 150px;">Peserta</th>
                            <th style="padding: 10px 12px;">Hadir</th>
                            <th style="padding: 10px 12px;">Post-test</th>
                            <th style="padding: 10px 12px; width: 140px;">Syarat laporan</th>
                            <th style="padding: 10px 16px;">Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $s = $row['stats']; @endphp
                            <tr style="border-top: 1px solid var(--eo-border); color: var(--eo-text); cursor: pointer;" onclick="window.location='{{ $row['url'] }}'">
                                <td style="padding: 10px 16px;">
                                    <div style="font-weight: 700;">{{ $row['event']->school?->name ?? $row['event']->title }}</div>
                                    <div class="eo-muted" style="font-size: 12px;">{{ $row['event']->creator?->email }}</div>
                                </td>
                                <td style="padding: 10px 12px; white-space: nowrap;">
                                    {{ $row['event']->starts_at?->translatedFormat('d M Y') ?? '-' }}
                                    <div class="eo-muted" style="font-size: 12px;">{{ $row['overview']->countdown() }}</div>
                                </td>
                                <td style="padding: 10px 12px;"><span class="eo-pill {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span></td>
                                <td style="padding: 10px 12px;">
                                    <div style="font-weight: 700;">{{ $s['participants'] }}<span class="eo-muted" style="font-size: 12px;">/{{ $s['target'] }}</span></div>
                                    <div class="eo-bar" style="margin-top: 4px;"><span style="width: {{ $pct($s['participants'], $s['target']) }}%;"></span></div>
                                </td>
                                <td style="padding: 10px 12px;">{{ $s['attended'] }}</td>
                                <td style="padding: 10px 12px;">{{ $s['post'] }}</td>
                                <td style="padding: 10px 12px;">
                                    <div style="font-weight: 700;">{{ $row['proof_done'] }}/{{ $row['proof_total'] }}</div>
                                    <div class="eo-bar" style="margin-top: 4px;"><span style="background: var(--eo-success); width: {{ $pct($row['proof_done'], $row['proof_total']) }}%;"></span></div>
                                </td>
                                <td style="padding: 10px 16px;"><span class="eo-pill {{ $row['report']['color'] }}">{{ $row['report']['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="eo-muted" style="padding: 18px 16px;">Tidak ada lokasi pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
