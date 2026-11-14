<x-filament-widgets::widget>
    @include('filament.event-overview.styles')
    @php
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $stageColors = ['#94a3b8', '#6366f1', '#0ea5e9', '#14b8a6', '#f59e0b', '#16a34a'];
    @endphp
    <div class="eo">
        {{-- Tabel progres per lokasi --}}
        <div class="eo-card" style="overflow: hidden;">
            <div class="eo-pad eo-row eo-between" style="border-bottom: 1px solid var(--eo-border);">
                <div>
                    <p class="eo-h3" style="margin: 0;">Progres per lokasi</p>
                    <p class="eo-muted" style="font-size: 12px;">Tahapan, capaian peserta, nilai, dan kelengkapan laporan. Klik nama lokasi untuk melihat seluruh datanya.</p>
                </div>
                <div class="eo-row" style="gap: 6px;">
                    @foreach ($filters as $key => $f)
                        <button type="button" wire:click="$set('filter', '{{ $key }}')" class="eo-btn {{ $filter === $key ? 'primary' : '' }}" style="padding: 5px 10px; font-size: 12px;">
                            {{ $f['label'] }} <span style="opacity: .7;">{{ $f['count'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table style="border-collapse: collapse; font-size: 13px; min-width: 1080px; width: 100%;">
                    <thead>
                        <tr style="background: var(--eo-soft); color: var(--eo-muted); font-size: 12px; text-align: left;">
                            <th style="padding: 10px 16px;">Lokasi</th>
                            <th style="padding: 10px 12px;">Jadwal</th>
                            <th style="padding: 10px 12px;">Status</th>
                            <th style="padding: 10px 12px; width: 150px;">Tahapan</th>
                            <th style="padding: 10px 12px; width: 140px;">Peserta</th>
                            <th style="padding: 10px 12px;">Hadir</th>
                            <th style="padding: 10px 12px;">Pre → Post</th>
                            <th style="padding: 10px 12px;">Sertifikat</th>
                            <th style="padding: 10px 12px;">Tutor ToT</th>
                            <th style="padding: 10px 16px; width: 130px;">Syarat laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php $s = $row['stats']; $e = $row['event']; @endphp
                            <tr style="border-top: 1px solid var(--eo-border); color: var(--eo-text);">
                                <td style="padding: 10px 16px;">
                                    <a href="{{ \App\Filament\Manajemen\Pages\LocationDetailPage::getUrl(['event' => $e->id]) }}" style="font-weight: 700; color: var(--eo-primary); text-decoration: none;">{{ str($e->title)->replace('Digital Safety Champions - ', '') }}</a>
                                    <div class="eo-muted" style="font-size: 12px;">{{ $e->school?->name }}</div>
                                </td>
                                <td style="padding: 10px 12px; white-space: nowrap;">
                                    {{ $e->starts_at?->translatedFormat('d M Y') ?? '-' }}
                                    <div class="eo-muted" style="font-size: 12px;">{{ $row['overview']->countdown() }}</div>
                                </td>
                                <td style="padding: 10px 12px;"><span class="eo-pill {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span></td>
                                <td style="padding: 10px 12px;">
                                    <div style="display: flex; gap: 3px;" title="{{ collect($row['steps'])->map(fn ($st) => $st['title'] . ': ' . $st['hint'])->join(' · ') }}">
                                        @foreach ($row['steps'] as $st)
                                            <span style="flex: 1; height: 8px; border-radius: 4px; background: {{ $st['state'] === 'done' ? 'var(--eo-success)' : ($st['state'] === 'current' ? 'var(--eo-info)' : 'var(--eo-track)') }};"></span>
                                        @endforeach
                                    </div>
                                    <div class="eo-muted" style="font-size: 12px; margin-top: 3px;">{{ $row['steps_done'] }}/5 · {{ $row['stage'] < 5 ? \App\Support\ProgramMetrics::STAGES[$row['stage']] : 'Selesai' }}</div>
                                </td>
                                <td style="padding: 10px 12px;">
                                    {{ $s['participants'] }}<span class="eo-muted"> / {{ $s['target'] }}</span>
                                    <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $pct($s['participants'], $s['target']) }}%; background: var(--eo-info); border-radius: 999px;"></span></div>
                                </td>
                                <td style="padding: 10px 12px;">{{ $s['attended'] }}<span class="eo-muted"> / {{ $s['participants'] }}</span></td>
                                <td style="padding: 10px 12px; white-space: nowrap;">
                                    {{ $row['avg_pre'] !== null ? number_format($row['avg_pre'], 1, ',', '.') : '-' }} → <strong>{{ $row['avg_post'] !== null ? number_format($row['avg_post'], 1, ',', '.') : '-' }}</strong>
                                    <div class="eo-muted" style="font-size: 12px;">{{ $s['post'] }} selesai post-test</div>
                                </td>
                                <td style="padding: 10px 12px;">{{ $s['certificates'] }}</td>
                                <td style="padding: 10px 12px;">{{ $s['tutors_tot'] }}<span class="eo-muted"> / {{ $s['tutors'] }}</span></td>
                                <td style="padding: 10px 16px;">
                                    {{ $row['proof_done'] }}<span class="eo-muted"> / {{ $row['proof_total'] }}</span>
                                    <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $pct($row['proof_done'], $row['proof_total']) }}%; background: {{ $barColor($pct($row['proof_done'], $row['proof_total'])) }}; border-radius: 999px;"></span></div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="eo-muted" style="padding: 24px 16px; text-align: center;">Belum ada event pada filter ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
