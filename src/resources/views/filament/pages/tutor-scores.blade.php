<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $s = $event ? $this->summary : null;
        $fmt = fn ($value) => \App\Filament\Pages\TutorScores::formatScore($value);
        $pct = fn ($part, $whole) => $whole > 0 ? (int) round($part / $whole * 100) : 0;
    @endphp

    <style>
        .ts { --ts-border: rgba(148, 163, 184, .35); --ts-muted: rgb(100, 116, 139); --ts-text: rgb(17, 24, 39); --ts-card: #fff; --ts-track: rgba(148, 163, 184, .22); display: grid; gap: 16px; }
        .dark .ts { --ts-border: rgba(255, 255, 255, .1); --ts-muted: rgb(148, 163, 184); --ts-text: rgb(241, 245, 249); --ts-card: rgb(24, 24, 27); }
        .ts-card { background: var(--ts-card); border: 1px solid var(--ts-border); border-radius: 14px; }
        .ts-head { align-items: center; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; padding: 16px 20px; }
        .ts-title { color: var(--ts-text); font-size: 17px; font-weight: 800; margin: 0; }
        .ts-sub { color: var(--ts-muted); font-size: 13px; margin: 3px 0 0; }
        .ts-tools { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
        .ts-stats { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
        .ts-stat { padding: 14px 16px; }
        .ts-label { color: var(--ts-muted); font-size: 12px; font-weight: 700; letter-spacing: .04em; margin: 0; text-transform: uppercase; }
        .ts-value { color: var(--ts-text); font-size: 28px; font-weight: 800; line-height: 1.1; margin: 6px 0 2px; }
        .ts-value small { color: var(--ts-muted); font-size: 15px; font-weight: 700; }
        .ts-hint { color: var(--ts-muted); font-size: 12px; margin: 0; }
        .ts-bar { background: var(--ts-track); border-radius: 999px; height: 6px; margin-top: 8px; overflow: hidden; }
        .ts-bar span { border-radius: inherit; display: block; height: 100%; }
        .ts-up { color: rgb(22, 163, 74); }
        .ts-down { color: rgb(220, 38, 38); }
        .ts-grid2 { display: grid; gap: 12px; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); }
        .ts-compare { align-items: flex-end; display: flex; gap: 18px; height: 150px; padding-top: 8px; }
        .ts-col { align-items: center; display: flex; flex: 1; flex-direction: column; gap: 6px; height: 100%; justify-content: flex-end; }
        .ts-col-bar { border-radius: 8px 8px 4px 4px; flex: 0 0 auto; min-height: 4px; width: 100%; max-width: 90px; }
        .ts-col-val { color: var(--ts-text); font-size: 15px; font-weight: 800; }
        .ts-band { align-items: center; display: grid; gap: 10px; grid-template-columns: 70px 1fr 34px; margin-top: 10px; }
        .ts-band-label { color: var(--ts-muted); font-size: 12px; font-weight: 700; }
        .ts-band-count { color: var(--ts-text); font-size: 13px; font-weight: 800; text-align: right; }
        @media (max-width: 900px) { .ts-grid2 { grid-template-columns: 1fr; } }
    </style>

    <div class="ts">
        @if ($events->isEmpty())
            <div class="ts-card ts-head"><p class="ts-sub" style="margin: 0;">Belum ada event yang ditugaskan kepada Anda.</p></div>
        @else
            <div class="ts-card ts-head">
                <div>
                    <p class="ts-title">{{ $event?->title }}</p>
                    <p class="ts-sub">{{ $event?->starts_at?->translatedFormat('l, d F Y') }} · nilai tertinggi tiap peserta yang dipakai</p>
                </div>
                <div class="ts-tools">
                    @if ($events->count() > 1)
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedEventId" aria-label="Pilih event">
                                @foreach ($events as $option)
                                    <option value="{{ $option->id }}">{{ $option->title }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                    <x-filament::button color="gray" icon="heroicon-o-arrow-down-tray" wire:click="exportCsv">Unduh CSV</x-filament::button>
                </div>
            </div>

            <div class="ts-stats">
                <div class="ts-card ts-stat">
                    <p class="ts-label">Peserta</p>
                    <p class="ts-value">{{ $s['participants'] }}</p>
                    <p class="ts-hint">{{ $s['attended'] }} sudah absen hadir</p>
                </div>
                <div class="ts-card ts-stat">
                    <p class="ts-label">Pre-Test</p>
                    <p class="ts-value">{{ $s['pre_done'] }}<small>/{{ $s['participants'] }}</small></p>
                    <div class="ts-bar"><span style="background: rgb(59, 130, 246); width: {{ $pct($s['pre_done'], $s['participants']) }}%;"></span></div>
                </div>
                <div class="ts-card ts-stat">
                    <p class="ts-label">Post-Test</p>
                    <p class="ts-value">{{ $s['post_done'] }}<small>/{{ $s['participants'] }}</small></p>
                    <div class="ts-bar"><span style="background: rgb(22, 163, 74); width: {{ $pct($s['post_done'], $s['participants']) }}%;"></span></div>
                </div>
                <div class="ts-card ts-stat">
                    <p class="ts-label">Peningkatan rata-rata</p>
                    <p class="ts-value {{ ($s['gain'] ?? 0) > 0 ? 'ts-up' : (($s['gain'] ?? 0) < 0 ? 'ts-down' : '') }}">
                        {{ $s['gain'] === null ? '-' : (($s['gain'] > 0 ? '+' : '') . $fmt($s['gain'])) }}
                    </p>
                    <p class="ts-hint">{{ $s['improved'] }} dari {{ $s['compared'] }} peserta nilainya naik</p>
                </div>
                <div class="ts-card ts-stat">
                    <p class="ts-label">Microsite & Sertifikat</p>
                    <p class="ts-value">{{ $s['certificates'] }}<small>/{{ $s['participants'] }}</small></p>
                    <p class="ts-hint">{{ $s['microsite'] }} sudah isi microsite · {{ $s['certificates'] }} sertifikat terbit</p>
                </div>
            </div>

            <div class="ts-grid2">
                <div class="ts-card ts-stat">
                    <p class="ts-label">Rata-rata nilai</p>
                    <div class="ts-compare">
                        @foreach ([['Pre-Test', $s['pre_avg'], 'rgb(59, 130, 246)'], ['Post-Test', $s['post_avg'], 'rgb(22, 163, 74)']] as [$name, $value, $color])
                            <div class="ts-col">
                                <span class="ts-col-val">{{ $fmt($value) }}</span>
                                <div class="ts-col-bar" style="background: {{ $color }}; height: {{ max(4, (int) round(($value ?? 0) * 0.9)) }}px;"></div>
                                <span class="ts-hint">{{ $name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="ts-card ts-stat">
                    <p class="ts-label">Sebaran nilai post-test</p>
                    @foreach ($s['bands'] as $band)
                        <div class="ts-band">
                            <span class="ts-band-label">{{ $band['label'] }}</span>
                            <div class="ts-bar" style="margin: 0; height: 10px;"><span style="background: {{ $band['color'] }}; width: {{ $pct($band['count'], max(1, $s['post_done'])) }}%;"></span></div>
                            <span class="ts-band-count">{{ $band['count'] }}</span>
                        </div>
                    @endforeach
                    <p class="ts-hint" style="margin-top: 12px;">Dari {{ $s['post_done'] }} peserta yang sudah post-test.</p>
                </div>
            </div>

            {{ $this->table }}
        @endif
    </div>
</x-filament-panels::page>
