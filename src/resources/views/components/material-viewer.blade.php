@props(['material', 'height' => '560px'])
@php $viewer = \App\Support\MaterialViewer::for($material); @endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-950']) }}>
    @if (! $viewer['url'])
        <div class="p-6 text-sm text-gray-500">Materi belum memiliki file atau tautan.</div>
    @elseif ($viewer['type'] === 'video' && str_contains((string) $viewer['embed_url'], 'youtube.com/embed'))
        <div style="position: relative; padding-top: 56.25%;">
            <iframe src="{{ $viewer['embed_url'] }}" style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
        </div>
    @elseif ($viewer['type'] === 'video')
        <video controls preload="metadata" style="display: block; width: 100%; max-height: {{ $height }}; background: #000;">
            <source src="{{ $viewer['url'] }}">
        </video>
    @else
        <iframe src="{{ $viewer['embed_url'] }}" style="display: block; width: 100%; height: {{ $height }}; border: 0;" allowfullscreen></iframe>
    @endif
    @if ($viewer['url'])
        <div class="flex flex-wrap justify-end border-t border-gray-200 bg-white px-3 py-2 text-xs dark:border-white/10 dark:bg-gray-900" style="gap: 1rem;">
            @if ($viewer['type'] === 'ppt')
                <a href="{{ $viewer['embed_url'] }}" target="_blank" rel="noopener" class="font-medium text-primary-600 hover:underline">Buka slide di tab baru</a>
                <a href="{{ $viewer['url'] }}" class="font-medium text-primary-600 hover:underline">Unduh file PPT asli</a>
            @else
                <a href="{{ $viewer['url'] }}" target="_blank" rel="noopener" class="font-medium text-primary-600 hover:underline">Buka di tab baru</a>
            @endif
        </div>
    @endif
</div>
