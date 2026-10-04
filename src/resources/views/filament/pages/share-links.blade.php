<x-filament-panels::page>
    @php
        $landing = $this->landing;
        $eventLinks = $this->eventLinks;
    @endphp

    <style>
        .sl-hero { display: grid; gap: 28px; grid-template-columns: 240px minmax(0, 1fr); align-items: center; }
        .sl-qr { background: #fff; border: 1px solid rgba(148, 163, 184, .35); border-radius: 18px; padding: 14px; width: 100%; }
        .sl-qr img { display: block; height: auto; image-rendering: pixelated; width: 100%; }
        .sl-label { color: #64748b; font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .sl-url { font-size: 22px; font-weight: 800; margin: 6px 0 4px; word-break: break-all; }
        .sl-help { color: #64748b; font-size: 13px; line-height: 1.6; margin: 0 0 16px; }
        .sl-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .sl-grid { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .sl-card { border: 1px solid rgba(148, 163, 184, .35); border-radius: 14px; display: grid; gap: 10px; padding: 14px; }
        .sl-card .sl-qr { justify-self: center; max-width: 170px; padding: 8px; }
        .sl-title { font-size: 14px; font-weight: 700; line-height: 1.35; }
        .sl-meta { color: #64748b; font-size: 12px; word-break: break-all; }
        @media (max-width: 720px) { .sl-hero { grid-template-columns: 1fr; } .sl-hero .sl-qr { max-width: 240px; } }
    </style>

    <x-filament::section icon="heroicon-o-globe-alt" heading="Landing Page sena" description="Bagikan link atau tampilkan QR ini di banner, slide, atau grup WhatsApp. Saat dipindai, QR membuka landing page.">
        <div class="sl-hero">
            <div class="sl-qr">
                @if ($landing['qr'])
                    <img src="{{ $landing['qr'] }}" alt="QR landing page {{ $landing['url'] }}">
                @endif
            </div>
            <div x-data="{ copied: false }">
                <div class="sl-label">Link landing page</div>
                <div class="sl-url">{{ $landing['url'] }}</div>
                <p class="sl-help">Peserta bisa melihat program, daftar event, dan login ke Portal Peserta dari halaman ini.</p>
                <div class="sl-actions">
                    <x-filament::button tag="a" :href="$landing['url']" target="_blank" icon="heroicon-o-arrow-top-right-on-square">Buka Landing Page</x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-o-clipboard-document"
                        x-on:click="navigator.clipboard.writeText({{ \Illuminate\Support\Js::from($landing['url']) }}); copied = true; setTimeout(() => copied = false, 2000)">
                        <span x-text="copied ? 'Tersalin' : 'Salin Link'">Salin Link</span>
                    </x-filament::button>
                    @if ($landing['qr'])
                        <x-filament::button color="gray" tag="a" :href="$landing['qr']" download="qr-landing-page-sena.png" icon="heroicon-o-arrow-down-tray">Unduh QR</x-filament::button>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section icon="heroicon-o-map-pin" heading="QR Pendaftaran per Lokus" description="QR tiap event yang sudah dipublish, mengarah langsung ke halaman pendaftaran event tersebut.">
        @if ($eventLinks->isEmpty())
            <p class="sl-help">Belum ada event yang dipublish.</p>
        @else
            <div class="sl-grid">
                @foreach ($eventLinks as $item)
                    <div class="sl-card" wire:key="sl-{{ $item['event']->id }}">
                        <div class="sl-title">{{ $item['event']->title }}</div>
                        <div class="sl-meta">{{ $item['event']->starts_at?->translatedFormat('d M Y') }} · {{ $item['event']->school?->name ?? '-' }}</div>
                        @if ($item['qr'])
                            <div class="sl-qr"><img src="{{ $item['qr'] }}" alt="QR pendaftaran {{ $item['event']->title }}"></div>
                        @endif
                        <div class="sl-meta">{{ $item['url'] }}</div>
                        <div class="sl-actions">
                            <x-filament::button size="sm" color="gray" tag="a" :href="$item['url']" target="_blank" icon="heroicon-o-arrow-top-right-on-square">Buka</x-filament::button>
                            @if ($item['qr'])
                                <x-filament::button size="sm" color="gray" tag="a" :href="$item['qr']" download="qr-{{ $item['event']->slug }}.png" icon="heroicon-o-arrow-down-tray">Unduh QR</x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
