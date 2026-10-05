<x-filament-panels::page>
    @php
        $events = $this->events;
        $practices = $this->practices;
    @endphp

    <style>
        .pm-list { display: grid; gap: 16px; }
        .pm-head { display: flex; gap: 10px; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; }
        .pm-title { font-size: 15px; font-weight: 700; line-height: 1.35; }
        .pm-meta, .pm-help { color: #64748b; font-size: 13px; }
        .pm-form { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
        .pm-input { align-items: stretch; border: 1px solid #d1d5db; border-radius: 8px; display: flex; flex: 1 1 260px; min-height: 40px; min-width: 0; overflow: hidden; background: #fff; }
        .pm-prefix { align-items: center; background: #f1f5f9; border-right: 1px solid #d1d5db; color: #64748b; display: flex; font-size: 13px; font-weight: 700; padding: 0 10px; white-space: nowrap; }
        .pm-input input { border: 0; box-shadow: none; flex: 1; font-size: 14px; min-width: 0; outline: none; padding: 0 12px; background: transparent; }
        .pm-error { color: #dc2626; font-size: 13px; margin-top: 6px; }
        .pm-saved { color: #16a34a; font-size: 13px; margin-top: 10px; }
        .pm-saved a { color: rgb(var(--primary-600)); font-weight: 600; word-break: break-all; }
        .dark .pm-input { background: transparent; border-color: rgba(148, 163, 184, .4); }
        .dark .pm-prefix { background: rgba(148, 163, 184, .15); border-color: rgba(148, 163, 184, .4); color: #cbd5e1; }
    </style>

    @if ($events->isEmpty())
        <x-filament::section>
            <p class="pm-help">Anda belum terdaftar pada event yang sudah dipublish. Daftar event terlebih dahulu dari menu Cari Event.</p>
        </x-filament::section>
    @else
        <div class="pm-list">
            @foreach ($events as $event)
                @php $practice = $practices->get($event->id); @endphp
                <x-filament::section wire:key="pm-{{ $event->id }}" icon="heroicon-o-link">
                    <div class="pm-head">
                        <div>
                            <div class="pm-title">{{ $event->title }}</div>
                            <div class="pm-meta">{{ $event->starts_at?->translatedFormat('d M Y') }} · {{ $event->school?->name ?? '-' }}</div>
                        </div>
                        @if ($practice?->sid_url)
                            <x-filament::badge color="success" icon="heroicon-m-check-circle">Sudah disematkan</x-filament::badge>
                        @else
                            <x-filament::badge color="gray">Belum disematkan</x-filament::badge>
                        @endif
                    </div>

                    @if (! $this->isApproved($event))
                        <p class="pm-help" style="margin-top: 12px;">Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.</p>
                    @else
                        <p class="pm-help" style="margin-top: 12px;">Ketik nama link s.id Anda setelah https://s.id/ (mis. Daftar_Peserta). Link dicek otomatis dan harus bisa dibuka.</p>
                        <form wire:submit="save({{ $event->id }})" class="pm-form">
                            <div class="pm-input">
                                <span class="pm-prefix">https://s.id/</span>
                                <input type="text" wire:model="links.{{ $event->id }}" placeholder="Daftar_Peserta" aria-label="Nama link setelah https://s.id/"
                                    x-on:input="$el.value = $el.value.replace(/^\s*(https?:\/\/)?(www\.)?s\.id\//i, '')">
                            </div>
                            <x-filament::button type="submit" icon="heroicon-o-check" wire:loading.attr="disabled" wire:target="save({{ $event->id }})">
                                Simpan
                            </x-filament::button>
                        </form>
                        @error("links.{$event->id}") <p class="pm-error">{{ $message }}</p> @enderror

                        @if ($practice?->sid_url)
                            <p class="pm-saved">Link tersimpan: <a href="{{ $practice->sid_url }}" target="_blank" rel="noopener">{{ $practice->sid_url }}</a></p>
                        @endif
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
