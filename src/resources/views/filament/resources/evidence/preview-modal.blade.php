{{-- Popup preview bukti dukung: foto/video/YouTube/PDF bisa langsung dilihat. --}}
@php
    $kind = $evidence->mediaKind();
    $url = $evidence->fileUrl();
    $statusLabels = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'needs_revision' => 'Perlu revisi', 'rejected' => 'Ditolak'];
    $statusColors = ['approved' => 'success', 'rejected' => 'danger', 'needs_revision' => 'warning'];
@endphp

<div style="display: grid; gap: 16px;">
    <div style="border-radius: 12px; overflow: hidden; background: rgba(15, 23, 42, .92); display: flex; align-items: center; justify-content: center; min-height: 200px;">
        @if ($kind === 'image')
            <img src="{{ $url }}" alt="{{ \App\Models\Evidence::TYPES[$evidence->type] ?? $evidence->type }}" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
        @elseif ($kind === 'video')
            <video src="{{ $url }}" controls preload="metadata" playsinline style="width: 100%; max-height: 70vh;"></video>
        @elseif ($kind === 'youtube')
            <iframe src="https://www.youtube-nocookie.com/embed/{{ $evidence->youtubeId() }}" title="Video YouTube" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen style="width: 100%; aspect-ratio: 16 / 9; border: 0;"></iframe>
        @elseif ($kind === 'pdf')
            <iframe src="{{ $url }}" title="Dokumen PDF" style="width: 100%; height: 70vh; border: 0; background: #fff;"></iframe>
        @else
            <div style="display: grid; justify-items: center; gap: 8px; padding: 40px 16px; color: #cbd5e1; text-align: center;">
                <x-heroicon-o-document style="width: 44px; height: 44px;" />
                <span style="font-size: 13px;">{{ $kind === 'none' ? 'Bukti ini belum memiliki berkas atau tautan.' : 'Preview tidak tersedia untuk jenis berkas ini. Gunakan tombol di bawah untuk membuka.' }}</span>
            </div>
        @endif
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        @if ($url)
            <x-filament::button tag="a" :href="$url" target="_blank" icon="heroicon-o-arrow-top-right-on-square" color="gray" size="sm">Buka di tab baru</x-filament::button>
            <x-filament::button tag="a" :href="$url" download icon="heroicon-o-arrow-down-tray" color="gray" size="sm">Download</x-filament::button>
        @endif
        @if ($evidence->link)
            <x-filament::button tag="a" :href="$evidence->link" target="_blank" icon="heroicon-o-link" color="gray" size="sm">Buka tautan</x-filament::button>
        @endif
    </div>

    <dl style="display: grid; grid-template-columns: max-content 1fr; gap: 8px 16px; font-size: 13px; margin: 0;">
        <dt style="color: rgb(100, 116, 139);">Jenis</dt><dd style="margin: 0;">{{ \App\Models\Evidence::TYPES[$evidence->type] ?? $evidence->type }}</dd>
        <dt style="color: rgb(100, 116, 139);">Event</dt><dd style="margin: 0;">{{ $evidence->learningEvent?->title ?? '-' }}</dd>
        <dt style="color: rgb(100, 116, 139);">Lokasi</dt><dd style="margin: 0;">{{ $evidence->school?->name ?? '-' }}</dd>
        @if ($evidence->session_index)
            <dt style="color: rgb(100, 116, 139);">Sesi</dt><dd style="margin: 0;">Sesi ke-{{ $evidence->session_index }}</dd>
        @endif
        <dt style="color: rgb(100, 116, 139);">Diunggah</dt><dd style="margin: 0;">{{ $evidence->uploader?->name ?? '-' }} · {{ $evidence->created_at?->translatedFormat('d M Y, H:i') }} WIB</dd>
        <dt style="color: rgb(100, 116, 139);">Status</dt>
        <dd style="margin: 0;"><x-filament::badge :color="$statusColors[$evidence->status] ?? 'gray'" style="width: fit-content;">{{ $statusLabels[$evidence->status] ?? $evidence->status }}</x-filament::badge></dd>
        @if ($evidence->review_notes)
            <dt style="color: rgb(100, 116, 139);">Catatan</dt><dd style="margin: 0;">{{ $evidence->review_notes }}</dd>
        @endif
    </dl>
</div>
