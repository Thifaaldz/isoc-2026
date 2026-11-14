@php
    $user = $getRecord();
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $palette = ['#0ea5e9', '#14b8a6', '#6366f1', '#f97316', '#e11d48', '#8b5cf6', '#0d9488', '#d97706'];
    $color = $palette[crc32($user->email) % count($palette)];
    $avatar = $user->getFilamentAvatarUrl();
@endphp
<div style="display:flex;align-items:center;gap:.75rem;padding:.25rem 0;">
    @if ($avatar)
        <img src="{{ $avatar }}" alt="" style="width:2.25rem;height:2.25rem;border-radius:9999px;object-fit:cover;flex:none;">
    @else
        <span style="width:2.25rem;height:2.25rem;border-radius:9999px;flex:none;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;background:linear-gradient(135deg, {{ $color }}, #1e293b);">{{ $initials ?: '?' }}</span>
    @endif
    <div style="min-width:0;">
        <div class="text-sm font-semibold text-gray-950 dark:text-white" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->name }}</div>
        <div class="text-xs text-gray-500 dark:text-gray-400" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->email }}</div>
    </div>
</div>
