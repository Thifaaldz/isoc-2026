<x-filament-panels::page>
    <style>
        .ev-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); }
        .ev-card { background: #fff; border: 1px solid rgba(148, 163, 184, .35); border-radius: 14px; cursor: pointer; display: flex; flex-direction: column; overflow: hidden; text-align: left; transition: border-color .15s, box-shadow .15s; width: 100%; min-width: 0; }
        .ev-card:hover { border-color: rgb(var(--primary-500)); box-shadow: 0 4px 14px rgba(15, 23, 42, .08); }
        .dark .ev-card { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .1); }
        .ev-mosaic { aspect-ratio: 16 / 9; background: rgba(148, 163, 184, .15); display: grid; gap: 2px; grid-template-columns: repeat(2, 1fr); grid-template-rows: repeat(2, 1fr); position: relative; }
        .ev-mosaic.n1 > * { grid-column: 1 / -1; grid-row: 1 / -1; }
        .ev-mosaic.n2 > * { grid-row: 1 / -1; }
        .ev-mosaic.n3 > :first-child { grid-row: 1 / -1; }
        .ev-mosaic img, .ev-mosaic video { height: 100%; object-fit: cover; width: 100%; display: block; }
        .ev-tile { position: relative; overflow: hidden; min-height: 0; }
        .ev-play { align-items: center; background: rgba(15, 23, 42, .6); border-radius: 999px; color: #fff; display: flex; height: 34px; justify-content: center; left: 50%; position: absolute; top: 50%; transform: translate(-50%, -50%); width: 34px; }
        .ev-play svg { height: 18px; margin-left: 2px; width: 18px; }
        .ev-empty { align-items: center; color: rgb(100, 116, 139); display: grid; font-size: 12px; font-weight: 700; gap: 6px; grid-column: 1 / -1; grid-row: 1 / -1; justify-items: center; align-content: center; }
        .ev-empty svg { height: 34px; width: 34px; }
        .ev-body { display: grid; gap: 8px; padding: 14px 16px 16px; min-width: 0; }
        .ev-title { font-size: 15px; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
        .ev-meta { align-items: center; color: rgb(100, 116, 139); display: flex; font-size: 13px; gap: 6px; min-width: 0; }
        .ev-meta svg { flex: 0 0 15px; height: 15px; width: 15px; }
        .ev-meta span { overflow-wrap: anywhere; }
        .ev-badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .ev-bar { background: rgba(148, 163, 184, .25); border-radius: 999px; height: 6px; overflow: hidden; }
        .ev-bar span { background: rgb(22, 163, 74); display: block; height: 100%; }
        .ev-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between; }
        .ev-search { max-width: 360px; width: 100%; }
    </style>

    @if (! $this->selectedEvent)
        <div class="ev-toolbar">
            <p style="color: rgb(100, 116, 139); font-size: 14px; margin: 0;">Pilih event untuk melihat dan memverifikasi bukti dukungnya.</p>
            <div class="ev-search">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="search" wire:model.live.debounce.400ms="eventSearch" placeholder="Cari event atau lokasi" />
                </x-filament::input.wrapper>
            </div>
        </div>

        @if ($this->eventCards->isEmpty())
            <x-filament::section>
                <p style="color: rgb(100, 116, 139); font-size: 14px; margin: 0;">Tidak ada event yang cocok.</p>
            </x-filament::section>
        @else
            <div class="ev-grid">
                @foreach ($this->eventCards as $card)
                    @php
                        $event = $card['event'];
                        $thumbs = $card['thumbs'];
                    @endphp
                    <button type="button" class="ev-card" wire:key="ev-{{ $event->id }}" wire:click="openEvent({{ $event->id }})">
                        <div class="ev-mosaic n{{ min(4, max(1, $thumbs->count())) }}" style="width: 100%;">
                            @forelse ($thumbs as $thumb)
                                <div class="ev-tile">
                                    @if ($thumb->mediaKind() === 'image')
                                        <img src="{{ $thumb->fileUrl() }}" alt="" loading="lazy">
                                    @elseif ($thumb->mediaKind() === 'video')
                                        <video src="{{ $thumb->fileUrl() }}#t=0.5" preload="metadata" muted playsinline style="pointer-events: none;"></video>
                                        <span class="ev-play"><x-heroicon-s-play /></span>
                                    @else
                                        <img src="https://img.youtube.com/vi/{{ $thumb->youtubeId() }}/mqdefault.jpg" alt="" loading="lazy">
                                        <span class="ev-play"><x-heroicon-s-play /></span>
                                    @endif
                                </div>
                            @empty
                                <div class="ev-empty">
                                    <x-heroicon-o-photo />
                                    {{ $card['total'] ? $card['total'] . ' berkas dokumen' : 'Belum ada bukti dukung' }}
                                </div>
                            @endforelse
                        </div>
                        <div class="ev-body">
                            <div class="ev-title">{{ $event->title }}</div>
                            <div class="ev-meta"><x-heroicon-m-map-pin /><span>{{ $event->school?->name ?? 'Lokasi belum diatur' }}</span></div>
                            <div class="ev-meta"><x-heroicon-m-calendar-days /><span>{{ $event->starts_at?->translatedFormat('d M Y') ?? 'Jadwal belum diatur' }}</span></div>
                            <div class="ev-badges">
                                <x-filament::badge color="gray">{{ $card['total'] }} bukti</x-filament::badge>
                                @if ($card['pending'])
                                    <x-filament::badge color="warning">{{ $card['pending'] }} menunggu</x-filament::badge>
                                @endif
                                @if ($card['approved'])
                                    <x-filament::badge color="success">{{ $card['approved'] }} disetujui</x-filament::badge>
                                @endif
                                @if ($card['revision'])
                                    <x-filament::badge color="warning">{{ $card['revision'] }} perlu revisi</x-filament::badge>
                                @endif
                                @if ($card['rejected'])
                                    <x-filament::badge color="danger">{{ $card['rejected'] }} ditolak</x-filament::badge>
                                @endif
                            </div>
                            <div style="display: grid; gap: 4px;">
                                <div class="ev-meta" style="justify-content: space-between;"><span>Foto wajib terpenuhi</span><strong>{{ $card['photos_done'] }}/{{ $card['photos_total'] }}</strong></div>
                                <div class="ev-bar"><span style="width: {{ $card['photos_total'] ? round($card['photos_done'] / $card['photos_total'] * 100) : 0 }}%;"></span></div>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="ev-toolbar">
            <div style="min-width: 0;">
                <div class="ev-title" style="font-size: 18px;">{{ $this->selectedEvent->title }}</div>
                <div class="ev-meta"><x-heroicon-m-map-pin /><span>{{ $this->selectedEvent->school?->name ?? 'Lokasi belum diatur' }}</span></div>
            </div>
            <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="closeEvent">Kembali ke daftar event</x-filament::button>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
