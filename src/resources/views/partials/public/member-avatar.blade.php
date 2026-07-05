@php
    $isLarge = ($size ?? 'small') === 'large';
    $classes = $isLarge
        ? 'w-24 h-24 mx-auto mb-4'
        : 'w-16 h-16 flex-shrink-0';
@endphp

<div class="{{ $classes }} bg-surface-dim rounded-full flex items-center justify-center overflow-hidden">
    @if ($member->photo_src)
        <img src="{{ $member->photo_src }}" alt="{{ $member->name ?? 'Pengurus' }}" class="w-full h-full object-cover">
    @else
        <span class="material-symbols-outlined {{ $isLarge ? 'text-4xl' : 'text-3xl' }} text-outline">person</span>
    @endif
</div>
