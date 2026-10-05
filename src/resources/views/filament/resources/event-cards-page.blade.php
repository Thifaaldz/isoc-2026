{{-- Halaman resource dengan card per event (trait HasEventCards). --}}
<x-filament-panels::page>
    <style>
        .ec-grid { display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); }
        .ec-card { background: #fff; border: 1px solid rgba(148, 163, 184, .35); border-radius: 14px; cursor: pointer; display: grid; gap: 10px; min-width: 0; padding: 16px; text-align: left; transition: border-color .15s, box-shadow .15s; width: 100%; align-content: start; }
        .ec-card:hover { border-color: rgb(var(--primary-500)); box-shadow: 0 4px 14px rgba(15, 23, 42, .08); }
        .dark .ec-card { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .1); }
        .ec-title { font-size: 15px; font-weight: 800; line-height: 1.35; overflow-wrap: anywhere; }
        .ec-meta { align-items: center; color: rgb(100, 116, 139); display: flex; font-size: 13px; gap: 6px; min-width: 0; }
        .ec-meta svg { flex: 0 0 15px; height: 15px; width: 15px; }
        .ec-meta span { overflow-wrap: anywhere; }
        .ec-badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .ec-bar { background: rgba(148, 163, 184, .25); border-radius: 999px; height: 6px; overflow: hidden; }
        .ec-bar span { background: rgb(22, 163, 74); display: block; height: 100%; }
        .ec-toolbar { align-items: center; display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; }
        .ec-search { max-width: 360px; width: 100%; }
    </style>

    @if (! $this->selectedEvent)
        <div class="ec-toolbar">
            <p style="color: rgb(100, 116, 139); font-size: 14px; margin: 0;">{{ $this->eventCardsHint() }}</p>
            <div class="ec-search">
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
            <div class="ec-grid">
                @foreach ($this->eventCards as $card)
                    @php $event = $card['event']; $progress = $card['progress']; @endphp
                    <button type="button" class="ec-card" wire:key="ec-{{ $event->id }}" wire:click="openEvent({{ $event->id }})">
                        <div class="ec-title">{{ $event->title }}</div>
                        <div class="ec-meta"><x-heroicon-m-map-pin /><span>{{ $event->school?->name ?? 'Lokasi belum diatur' }}</span></div>
                        <div class="ec-meta"><x-heroicon-m-calendar-days /><span>{{ $event->starts_at?->translatedFormat('d M Y') ?? 'Jadwal belum diatur' }}</span></div>
                        @if ($card['badges'])
                            <div class="ec-badges">
                                @foreach ($card['badges'] as [$label, $color])
                                    <x-filament::badge :color="$color">{{ $label }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                        @if ($progress)
                            <div style="display: grid; gap: 4px;">
                                <div class="ec-meta" style="justify-content: space-between;"><span>{{ $progress['label'] }}</span><strong>{{ $progress['done'] }}/{{ $progress['total'] }}</strong></div>
                                <div class="ec-bar"><span style="width: {{ $progress['total'] ? min(100, round($progress['done'] / $progress['total'] * 100)) : 0 }}%;"></span></div>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="ec-toolbar">
            <div style="min-width: 0;">
                <div class="ec-title" style="font-size: 18px;">{{ $this->selectedEvent->title }}</div>
                <div class="ec-meta"><x-heroicon-m-map-pin /><span>{{ $this->selectedEvent->school?->name ?? 'Lokasi belum diatur' }}</span></div>
            </div>
            <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="closeEvent">Kembali ke daftar event</x-filament::button>
        </div>

        {{ $this->table }}
    @endif
</x-filament-panels::page>
