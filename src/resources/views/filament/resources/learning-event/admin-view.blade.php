<x-filament-panels::page>
    @include('filament.event-overview.styles')
    @php
        $event = $this->record;
        $overview = new \App\Support\EventOverview($event);
        $status = $overview->status();
        $next = $overview->nextStep();
        $registrationUrl = $overview->registrationUrl();
        $term1 = $event->payments->firstWhere('term', 1);
        $term2 = $event->payments->firstWhere('term', 2);
        $termLabel = fn ($payment) => match ($payment?->status) { 'eligible' => ['Cair', 'success'], 'paid' => ['Dibayar', 'success'], default => ['Belum', 'gray'] };
        $rundown = collect($event->rundown_items ?? [])->values();
        $checklist = collect($event->budget_items ?? []);
    @endphp

    <div class="eo">
        {{-- Ringkasan utama --}}
        <div class="eo-card eo-pad" style="display: grid; gap: 16px;">
            <div class="eo-row eo-between" style="align-items: flex-start;">
                <div style="display: grid; gap: 8px; min-width: 0;">
                    <div class="eo-row">
                        <span class="eo-pill {{ $status['color'] }}">{{ $status['label'] }}</span>
                        @if ($overview->countdown())
                            <span class="eo-pill gray">{{ $overview->countdown() }}</span>
                        @endif
                        <span class="eo-pill gray">{{ \App\Filament\Resources\LearningEventResource::eventTypeOptions()[$event->event_type] ?? $event->event_type }}</span>
                    </div>
                    <div class="eo-row" style="gap: 16px;">
                        <span class="eo-meta"><x-heroicon-o-calendar-days /> {{ $overview->schedule() }}</span>
                        <span class="eo-meta"><x-heroicon-o-map-pin /> {{ $event->school?->name ?? '-' }}{{ $event->school?->address ? ', ' . $event->school->address : '' }}</span>
                        @if ($event->school?->maps_url)
                            <a class="eo-meta" style="color: var(--eo-primary);" href="{{ $event->school->maps_url }}" target="_blank" rel="noopener"><x-heroicon-o-arrow-top-right-on-square /> Buka Maps</a>
                        @endif
                    </div>
                </div>
            </div>
            @include('filament.event-overview.steps')
        </div>

        {{-- Langkah berikutnya --}}
        <div class="eo-card eo-pad eo-next {{ $next['tone'] }}">
            <p class="eo-h3" style="margin-bottom: 4px;">Langkah berikutnya: {{ $next['title'] }}</p>
            <p class="eo-muted">{{ $next['text'] }}</p>
            @if ($next['items'])
                <ul>
                    @foreach ($next['items'] as $item)
                        <li>{{ ucfirst($item) }}</li>
                    @endforeach
                </ul>
            @endif
            <div class="eo-row" style="margin-top: 12px;">
                @foreach ($overview->quickLinks() as $link)
                    <a class="eo-btn" href="{{ $link['url'] }}"><x-dynamic-component :component="$link['icon']" /> {{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>

        {{-- Angka peserta --}}
        <div class="eo-card eo-pad">
            <p class="eo-h3">Peserta & tutor</p>
            @include('filament.event-overview.stats')
        </div>

        <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 440px), 1fr)); align-items: start;">
            {{-- Link pendaftaran --}}
            <div class="eo-card eo-pad" x-data="{ copied: false }">
                <p class="eo-h3">Link pendaftaran peserta</p>
                @if ($event->is_published)
                    <div class="eo-link-box">
                        <code>{{ $registrationUrl }}</code>
                        <button type="button" class="eo-btn" x-on:click="navigator.clipboard.writeText(@js($registrationUrl)); copied = true; setTimeout(() => copied = false, 2000)">
                            <x-heroicon-o-clipboard-document /> <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                        </button>
                        <a class="eo-btn" href="{{ $registrationUrl }}" target="_blank" rel="noopener"><x-heroicon-o-arrow-top-right-on-square /></a>
                    </div>
                    <p class="eo-muted" style="margin-top: 8px;">Pendaftaran {{ $event->registration_open ? 'sedang dibuka' : 'ditutup' }}. Bagikan link atau QR ini ke sekolah/peserta.</p>
                    @if ($qr = $overview->registrationQr())
                        <div class="eo-row" style="align-items: center; gap: 16px; margin-top: 12px; flex-wrap: nowrap;">
                            <img src="{{ $qr }}" alt="QR pendaftaran {{ $event->title }}" style="background: #fff; border: 1px solid var(--eo-border); border-radius: 12px; height: 150px; image-rendering: pixelated; padding: 8px; width: 150px;">
                            <div style="display: grid; gap: 8px;">
                                <p class="eo-muted">Pindai QR untuk langsung membuka halaman pendaftaran event ini.</p>
                                <a class="eo-btn" href="{{ $qr }}" download="qr-{{ $event->slug }}.png" style="width: fit-content;"><x-heroicon-o-arrow-down-tray /> Unduh QR</a>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="eo-muted">Link pendaftaran tersedia setelah event dipublish RTIK Pusat.</p>
                @endif
            </div>

            {{-- Tim tutor --}}
            <div class="eo-card eo-pad">
                <p class="eo-h3">Tim tutor ({{ $event->tutors->count() }})</p>
                <div style="display: grid; gap: 8px;">
                    @forelse ($event->tutors as $tutor)
                        <div class="eo-row eo-between" style="flex-wrap: nowrap;">
                            <div style="min-width: 0;">
                                <div style="color: var(--eo-text); font-size: 14px; font-weight: 700;">{{ $tutor->user?->name }}</div>
                                <div class="eo-muted" style="font-size: 12px;">{{ $tutor->user?->email }}{{ $tutor->user?->phone ? ' · ' . $tutor->user->phone : '' }}</div>
                            </div>
                            <span class="eo-pill {{ $tutor->tot_completed ? 'success' : 'warning' }}">{{ $tutor->tot_completed ? 'Lulus ToT' : 'Belum ToT' }}</span>
                        </div>
                    @empty
                        <p class="eo-muted">Belum ada tutor yang ditugaskan.</p>
                    @endforelse
                </div>
            </div>

            {{-- Materi --}}
            <div class="eo-card eo-pad">
                <p class="eo-h3">Modul yang dibawakan</p>
                <div style="display: grid; gap: 8px;">
                    @forelse ($event->meetings->sortBy('order') as $meeting)
                        <div class="eo-row" style="flex-wrap: nowrap;">
                            <span class="eo-count">{{ $loop->iteration }}</span>
                            <span style="color: var(--eo-text); font-size: 14px; font-weight: 600;">{{ $meeting->title }}</span>
                        </div>
                    @empty
                        <p class="eo-muted">Belum ada modul.</p>
                    @endforelse
                </div>
            </div>

            {{-- Termin pembayaran --}}
            <div class="eo-card eo-pad">
                <div class="eo-row eo-between" style="margin-bottom: 12px;">
                    <p class="eo-h3" style="margin: 0;">Termin pembayaran</p>
                    <a class="eo-btn" href="{{ \App\Filament\Pages\FinalReport::getUrl(['event' => $event->id]) }}"><x-heroicon-o-document-check /> Laporan Final</a>
                </div>
                @foreach ([1 => $term1, 2 => $term2] as $term => $payment)
                    @php
                        [$label, $color] = $termLabel($payment);
                        // Termin-2 dicentang otomatis dari data event (foto, absensi, nilai, microsite, ranking).
                        $items = $term === 2
                            ? collect($overview->termTwoChecklist())->map(fn ($item) => ['description' => $item['label'], 'done' => $item['done']])
                            : $checklist->where('term', $term);
                    @endphp
                    <div style="border-top: {{ $term === 2 ? '1px solid var(--eo-border)' : '0' }}; padding-top: {{ $term === 2 ? '10px' : '0' }}; margin-bottom: 10px;">
                        <div class="eo-row eo-between">
                            <span style="color: var(--eo-text); font-size: 14px; font-weight: 800;">Termin-{{ $term }}</span>
                            <span class="eo-pill {{ $color }}">{{ $label }}</span>
                        </div>
                        <p class="eo-muted" style="font-size: 12px;">{{ \App\Support\TorEventTemplate::TERM_NOTES[$term] ?? '' }}</p>
                        <div style="display: grid; gap: 4px; margin-top: 6px;">
                            @foreach ($items as $item)
                                <span class="eo-meta" style="color: {{ ($item['done'] ?? false) ? 'var(--eo-success)' : 'var(--eo-muted)' }};">
                                    @if ($item['done'] ?? false) <x-heroicon-s-check-circle /> @else <x-heroicon-o-minus-circle /> @endif
                                    {{ $item['description'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Rundown --}}
        <div class="eo-card eo-pad">
            <p class="eo-h3">Rundown acara</p>
            <div style="display: grid; gap: 0;">
                @forelse ($rundown as $item)
                    <div class="eo-row" style="flex-wrap: nowrap; align-items: flex-start; border-top: {{ $loop->first ? '0' : '1px solid var(--eo-border)' }}; padding: 10px 0;">
                        <span style="color: var(--eo-primary); flex: 0 0 110px; font-size: 13px; font-weight: 800;">{{ $item['start_time'] ?? '' }} - {{ $item['end_time'] ?? '' }}</span>
                        <div style="min-width: 0;">
                            <div style="color: var(--eo-text); font-size: 14px; font-weight: 700;">{{ $item['activity'] ?? '-' }}</div>
                            <div class="eo-muted" style="font-size: 12px;">{{ collect([$item['pic'] ?? null, $item['notes'] ?? null])->filter()->implode(' · ') }}</div>
                        </div>
                    </div>
                @empty
                    <p class="eo-muted">Rundown belum diisi.</p>
                @endforelse
            </div>
        </div>

        {{-- Mitra --}}
        @if ($event->orderedPartners->isNotEmpty())
            <div class="eo-card eo-pad">
                <p class="eo-h3">Mitra kegiatan</p>
                <div class="eo-row" style="gap: 18px;">
                    @foreach ($event->orderedPartners as $partner)
                        <span class="eo-row" style="gap: 8px;">
                            @if ($partner->logoSource())
                                <img src="{{ $partner->logoSource() }}" alt="{{ $partner->name }}" style="height: 28px; max-width: 90px; object-fit: contain;">
                            @endif
                            <span class="eo-muted" style="color: var(--eo-text); font-weight: 600;">{{ $partner->name }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
