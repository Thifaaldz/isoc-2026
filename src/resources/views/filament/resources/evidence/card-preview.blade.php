{{-- Thumbnail bukti dukung di card: foto/video/YouTube langsung terlihat, dokumen memakai ikon. --}}
@php
    $record = $getRecord();
    $kind = $record->mediaKind();
    $url = $record->fileUrl();
@endphp
<div style="position: relative; aspect-ratio: 16 / 10; width: 100%; overflow: hidden; border-radius: 10px; background: rgba(148, 163, 184, .15); display: flex; align-items: center; justify-content: center;">
    @if ($kind === 'image')
        <img src="{{ $url }}" alt="{{ \App\Models\Evidence::TYPES[$record->type] ?? $record->type }}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
    @elseif ($kind === 'video' || $kind === 'youtube')
        @if ($kind === 'video')
            <video src="{{ $url }}#t=0.5" preload="metadata" muted playsinline style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;"></video>
        @else
            <img src="https://img.youtube.com/vi/{{ $record->youtubeId() }}/hqdefault.jpg" alt="Video YouTube" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
        @endif
        <span style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
            <span style="display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 999px; background: rgba(15, 23, 42, .65); color: #fff;">
                <x-heroicon-s-play style="width: 26px; height: 26px; margin-left: 3px;" />
            </span>
        </span>
    @else
        @php
            [$icon, $label] = match ($kind) {
                'pdf' => ['heroicon-o-document-text', 'PDF'],
                'file' => ['heroicon-o-document', strtoupper(pathinfo($record->file_path, PATHINFO_EXTENSION)) ?: 'Berkas'],
                'link' => ['heroicon-o-link', 'Tautan'],
                default => ['heroicon-o-no-symbol', 'Belum ada berkas'],
            };
        @endphp
        <div style="display: grid; justify-items: center; gap: 6px; color: rgb(100, 116, 139);">
            <x-dynamic-component :component="$icon" style="width: 40px; height: 40px;" />
            <span style="font-size: 12px; font-weight: 700;">{{ $label }}</span>
        </div>
    @endif
</div>
