{{-- Kartu event untuk halaman Cari Event (peserta) dan Daftar Event (tutor). --}}
@props([
    'event',
    'statusLabel',
    'statusColor' => 'gray',
    'quotaLabel' => 'peserta',
    'quotaCount' => 0,
    'quotaTarget' => 0,
    'highlight' => false,
    'muted' => false,
])

@php
    $location = match ($event->event_type) {
        'webinar' => 'Webinar / Zoom',
        'hybrid' => $event->school?->name ? $event->school->name . ' + Zoom' : 'Hybrid',
        default => $event->school?->name ?? 'Lokasi belum diatur',
    };
    $time = $event->starts_at && $event->ends_at ? $event->starts_at->format('H.i') . ' - ' . $event->ends_at->format('H.i') . ' WIB' : null;
    $percent = $quotaTarget > 0 ? min(100, (int) round($quotaCount / $quotaTarget * 100)) : null;
@endphp

@once
    <style>
        .ecc-grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); }
        .ecc-card { background: #fff; border: 1px solid rgba(148, 163, 184, .35); border-radius: 16px; display: flex; flex-direction: column; min-width: 0; overflow: hidden; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .ecc-card:hover { border-color: rgb(var(--primary-400)); box-shadow: 0 10px 24px rgba(15, 23, 42, .08); transform: translateY(-1px); }
        .ecc-card.is-highlight { border-color: rgb(var(--success-500)); box-shadow: inset 0 3px 0 rgb(var(--success-500)); }
        .ecc-card.is-muted { opacity: .72; }
        .dark .ecc-card { background: rgba(255, 255, 255, .04); border-color: rgba(255, 255, 255, .1); }
        .ecc-head { align-items: flex-start; display: flex; gap: 14px; padding: 18px 18px 0; }
        .ecc-date { background: rgba(var(--primary-500), .1); border-radius: 12px; color: rgb(var(--primary-700)); flex: 0 0 58px; padding: 8px 0; text-align: center; }
        .dark .ecc-date { background: rgba(var(--primary-500), .18); color: rgb(var(--primary-300)); }
        .ecc-day { display: block; font-size: 22px; font-weight: 800; line-height: 1.1; }
        .ecc-month { display: block; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .ecc-year { display: block; font-size: 11px; font-weight: 600; opacity: .75; }
        .ecc-headtext { display: grid; gap: 8px; min-width: 0; }
        .ecc-badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecc-title { color: rgb(15, 23, 42); display: -webkit-box; font-size: 16px; font-weight: 800; line-height: 1.35; overflow: hidden; overflow-wrap: anywhere; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .dark .ecc-title { color: #fff; }
        .ecc-body { display: grid; gap: 12px; padding: 14px 18px 16px; }
        .ecc-desc { color: rgb(71, 85, 105); display: -webkit-box; font-size: 13px; line-height: 1.55; margin: 0; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
        .dark .ecc-desc { color: rgb(203, 213, 225); }
        .ecc-meta { display: grid; gap: 6px; }
        .ecc-meta div { align-items: center; color: rgb(100, 116, 139); display: flex; font-size: 13px; gap: 8px; min-width: 0; }
        .ecc-meta svg { color: rgb(148, 163, 184); flex: 0 0 16px; height: 16px; width: 16px; }
        .ecc-meta span { overflow-wrap: anywhere; }
        .ecc-quota { display: grid; gap: 6px; }
        .ecc-quota-row { color: rgb(100, 116, 139); display: flex; font-size: 12px; justify-content: space-between; }
        .ecc-quota-row strong { color: rgb(15, 23, 42); }
        .dark .ecc-quota-row strong { color: #fff; }
        .ecc-bar { background: rgba(148, 163, 184, .25); border-radius: 999px; height: 6px; overflow: hidden; }
        .ecc-bar span { background: rgb(var(--success-500)); border-radius: 999px; display: block; height: 100%; }
        .ecc-bar span.is-near { background: rgb(var(--warning-500)); }
        .ecc-bar span.is-full { background: rgb(var(--danger-500)); }
        .ecc-foot { border-top: 1px solid rgba(148, 163, 184, .25); margin-top: auto; padding: 14px 18px; }
        .ecc-foot > * { width: 100%; }
        .ecc-foot .fi-btn { justify-content: center; text-align: center; white-space: normal; width: 100%; }
    </style>
@endonce

<article {{ $attributes->class(['ecc-card', 'is-highlight' => $highlight, 'is-muted' => $muted]) }}>
    <div class="ecc-head">
        <div class="ecc-date">
            <span class="ecc-day">{{ $event->starts_at?->format('d') ?? '--' }}</span>
            <span class="ecc-month">{{ $event->starts_at?->translatedFormat('M') ?? 'TBA' }}</span>
            <span class="ecc-year">{{ $event->starts_at?->format('Y') }}</span>
        </div>
        <div class="ecc-headtext">
            <div class="ecc-badges">
                <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                <x-filament::badge color="gray">{{ ucfirst($event->event_type ?: 'offline') }}</x-filament::badge>
            </div>
            <div class="ecc-title" title="{{ $event->title }}">{{ $event->title }}</div>
        </div>
    </div>

    <div class="ecc-body">
        @if ($event->description)
            <p class="ecc-desc">{{ $event->description }}</p>
        @endif

        <div class="ecc-meta">
            <div><x-heroicon-m-calendar /><span>{{ $event->starts_at?->translatedFormat('l, d F Y') ?? 'Jadwal belum diatur' }}</span></div>
            @if ($time)
                <div><x-heroicon-m-clock /><span>{{ $time }}</span></div>
            @endif
            <div><x-heroicon-m-map-pin /><span>{{ $location }}</span></div>
        </div>

        <div class="ecc-quota">
            <div class="ecc-quota-row">
                <span>Kuota {{ $quotaLabel }}</span>
                <strong>{{ $quotaCount }}{{ $quotaTarget > 0 ? ' / ' . $quotaTarget : '' }}</strong>
            </div>
            @if ($percent !== null)
                <div class="ecc-bar"><span @class(['is-near' => $percent >= 80 && $percent < 100, 'is-full' => $percent >= 100]) style="width: {{ max($percent, 2) }}%;"></span></div>
            @endif
        </div>
    </div>

    <div class="ecc-foot">
        {{ $slot }}
    </div>
</article>
