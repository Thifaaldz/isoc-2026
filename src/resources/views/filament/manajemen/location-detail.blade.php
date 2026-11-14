<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $row = $this->row;
        $pct = fn ($part, $whole) => $whole > 0 ? min(100, (int) round($part / $whole * 100)) : 0;
        $barColor = fn (int $p) => $p >= 80 ? 'var(--eo-success)' : ($p >= 40 ? 'var(--eo-info)' : 'var(--eo-warning)');
        $options = \App\Filament\Manajemen\Tables\ManagementTables::eventOptions();
    @endphp
    <div class="eo" style="display: grid; gap: 16px;">
        <div class="eo-card eo-pad eo-row eo-between">
            <label style="display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 700; color: var(--eo-text);">
                Pilih lokasi
                <select wire:model.live="eventId" class="fi-select-input" style="border: 1px solid var(--eo-border); border-radius: 10px; padding: 6px 32px 6px 10px; font-size: 13px; background-color: var(--eo-card); color: var(--eo-text);">
                    @foreach ($options as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($row)
                <span class="eo-pill {{ $row['status']['color'] }}">{{ $row['status']['label'] }}</span>
            @endif
        </div>

        @if (! $row)
            <div class="eo-card eo-pad eo-muted">Lokasi tidak ditemukan.</div>
        @else
            @php $e = $row['event']; $s = $row['stats']; @endphp
            {{-- Identitas & tahapan --}}
            <div class="eo-card eo-pad" style="display: grid; gap: 14px;">
                <div>
                    <p class="eo-title">{{ $e->title }}</p>
                    <p class="eo-muted">{{ $row['overview']->schedule() }} · {{ $e->school?->name }}{{ $e->school?->city ? ', ' . str($e->school->city)->title() : '' }} · {{ ucfirst($e->event_type ?? 'offline') }} · {{ $row['overview']->countdown() }}</p>
                </div>
                <div class="eo-steps">
                    @foreach ($row['steps'] as $i => $st)
                        <div class="eo-step {{ $st['state'] }}">
                            <div class="eo-step-title">{{ $i + 1 }}. {{ $st['title'] }}</div>
                            <div class="eo-step-hint">{{ $st['hint'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Angka utama --}}
            <div class="eo-stats">
                @foreach ([
                    ['Peserta terdaftar', $s['participants'], $s['target']],
                    ['Hadir', $s['attended'], $s['participants']],
                    ['Pre-Test', $s['pre'], $s['participants']],
                    ['Post-Test', $s['post'], $s['participants']],
                    ['Praktik microsite', $s['microsite'], $s['participants']],
                    ['Peserta lengkap', $s['complete'], $s['participants']],
                    ['Sertifikat terbit', $s['certificates'], $s['participants']],
                    ['Tutor lulus ToT', $s['tutors_tot'], max($s['tutors'], $s['tutors_target'])],
                ] as [$label, $value, $whole])
                    @php $p = $pct($value, $whole); @endphp
                    <div class="eo-stat">
                        <div class="eo-stat-label">{{ $label }}</div>
                        <div class="eo-stat-value">{{ $value }}<span class="eo-muted" style="font-size: 13px; font-weight: 600;"> / {{ $whole }}</span></div>
                        <div class="eo-bar"><span style="display: block; height: 100%; width: {{ $p }}%; background: {{ $barColor($p) }}; border-radius: 999px;"></span></div>
                    </div>
                @endforeach
            </div>

            <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
                {{-- 13 syarat laporan --}}
                <div class="eo-card eo-pad">
                    <div class="eo-row eo-between" style="margin-bottom: 10px;">
                        <p class="eo-h3" style="margin: 0;">Syarat laporan final</p>
                        <span class="eo-count">{{ $row['proof_done'] }}/{{ $row['proof_total'] }}</span>
                    </div>
                    <div style="display: grid; gap: 6px;">
                        @foreach ($row['checklist'] as $item)
                            <div style="display: flex; gap: 10px; align-items: flex-start; padding: 6px 0; border-top: 1px solid var(--eo-border);">
                                <span style="flex: none; width: 20px; height: 20px; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: #fff; background: {{ $item['done'] ? 'var(--eo-success)' : 'var(--eo-track)' }};">{{ $item['done'] ? '✓' : '' }}</span>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: var(--eo-text);">{{ $item['label'] }}</div>
                                    <div class="eo-muted" style="font-size: 12px;">{{ $item['detail'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Nilai, bukti, termin --}}
                <div style="display: grid; gap: 16px; align-content: start;">
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Nilai</p>
                        <div class="eo-stats">
                            <div class="eo-stat"><div class="eo-stat-label">Rata-rata Pre-Test</div><div class="eo-stat-value">{{ $row['avg_pre'] !== null ? number_format($row['avg_pre'], 1, ',', '.') : '-' }}</div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Rata-rata Post-Test</div><div class="eo-stat-value">{{ $row['avg_post'] !== null ? number_format($row['avg_post'], 1, ',', '.') : '-' }}</div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Kenaikan</div><div class="eo-stat-value">{{ $row['avg_pre'] !== null && $row['avg_post'] !== null ? (($row['avg_post'] - $row['avg_pre']) >= 0 ? '+' : '') . number_format($row['avg_post'] - $row['avg_pre'], 1, ',', '.') : '-' }}</div></div>
                        </div>
                    </div>
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Bukti dukung</p>
                        <div class="eo-stats">
                            <div class="eo-stat"><div class="eo-stat-label">Foto wajib a-g</div><div class="eo-stat-value">{{ $row['photos_done'] }}<span class="eo-muted" style="font-size: 13px;"> / {{ $row['photos_total'] }}</span></div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Video slogan</div><div class="eo-stat-value" style="font-size: 18px;">{{ $row['video_done'] ? 'Ada' : 'Belum' }}</div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Disetujui</div><div class="eo-stat-value">{{ $row['evidence']['approved'] }}</div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Menunggu</div><div class="eo-stat-value">{{ $row['evidence']['pending'] }}</div></div>
                            <div class="eo-stat"><div class="eo-stat-label">Ditolak</div><div class="eo-stat-value">{{ $row['evidence']['rejected'] }}</div></div>
                        </div>
                    </div>
                    <div class="eo-card eo-pad">
                        <p class="eo-h3">Laporan & termin</p>
                        @php
                            $term = fn ($st) => match ($st) { 'eligible', 'paid' => ['Cair', 'success'], 'revision' => ['Revisi', 'danger'], 'pending' => ['Menunggu', 'gray'], default => ['Belum ada', 'gray'] };
                            [$t1, $c1] = $term($row['overview']->paymentStatus(1));
                            [$t2, $c2] = $term($row['overview']->paymentStatus(2));
                            $rep = $row['overview']->finalReportStatus();
                        @endphp
                        <div class="eo-row">
                            <span class="eo-muted">Termin-1</span> <span class="eo-pill {{ $c1 }}">{{ $t1 }}</span>
                            <span class="eo-muted" style="margin-left: 8px;">Laporan final</span> <span class="eo-pill {{ $rep['color'] }}">{{ $rep['label'] }}</span>
                            <span class="eo-muted" style="margin-left: 8px;">Termin-2</span> <span class="eo-pill {{ $c2 }}">{{ $t2 }}</span>
                        </div>
                        @if ($e->final_report_notes)
                            <p class="eo-muted" style="margin-top: 8px;">Catatan Pusat: {{ $e->final_report_notes }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Data lengkap per tab --}}
            <div style="display: grid; gap: 12px;">
                <div>
                    <h2 class="text-lg font-bold text-gray-950 dark:text-white">Data lengkap lokasi ini</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pilih tab untuk melihat datanya. Bisa dicari, difilter, dan diurutkan.</p>
                </div>
                <div class="eo-row" style="gap: 6px;">
                    @foreach (\App\Filament\Manajemen\Pages\LocationDetailPage::TABS as $key => $label)
                        <button type="button" wire:click="setTab('{{ $key }}')" class="eo-btn {{ $tab === $key ? 'primary' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
                {{ $this->table }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
