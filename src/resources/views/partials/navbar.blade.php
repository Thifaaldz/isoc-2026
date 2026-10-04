@php
    // Menu mengikuti isoc.id (anchor di halaman utama), ditambah Event dan Portal Peserta.
    $menu = [
        ['label' => __('Tentang Kami'), 'url' => route('home') . '#tentang', 'active' => false],
        ['label' => __('Program'), 'url' => route('home') . '#program', 'active' => false],
        ['label' => __('Pengurus'), 'url' => route('home') . '#pengurus', 'active' => false],
        ['label' => __('Mitra'), 'url' => route('home') . '#mitra', 'active' => false],
        ['label' => __('Event'), 'url' => route('events'), 'active' => request()->routeIs('events', 'event.*')],
    ];
@endphp
<nav class="fixed top-0 w-full z-50 bg-[#f8f9fa]/80 backdrop-blur-md border-b border-isoc-line/40" x-data="{ mobileOpen: false }">
    <div class="flex justify-between items-center px-6 lg:px-8 h-[72px] max-w-7xl mx-auto">
        <a href="{{ route('home') }}" class="shrink-0 inline-flex items-center gap-3">
            <img src="{{ asset('images/isoc-logo.png') }}" alt="ISOC" class="h-10 w-auto">
            <span class="h-8 w-px bg-isoc-line/60" aria-hidden="true"></span>
            <img src="{{ asset('images/sena-logo.png') }}" alt="Sena" class="h-7 w-auto max-w-[110px] object-contain">
        </a>

        <div class="hidden md:flex items-center gap-1">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" class="px-3 lg:px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $item['active'] ? 'text-isoc' : 'text-isoc-muted hover:text-isoc' }}">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('participant.login') }}" class="ml-2 inline-flex items-center gap-1.5 px-5 py-2.5 rounded-full text-sm font-semibold bg-isoc text-white hover:bg-isoc-container transition-colors">
                <span class="material-symbols-outlined text-base">person</span>
                {{ __('Portal Peserta') }}
            </a>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative" x-data="{ langOpen: false }">
                <button @click="langOpen = !langOpen" @click.outside="langOpen = false" class="flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium text-isoc-muted hover:text-isoc transition-colors">
                    <span class="material-symbols-outlined text-base">translate</span>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                </button>
                <div x-show="langOpen" x-cloak x-transition class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-grey-100 py-1 z-50">
                    <a href="{{ route('lang.switch', 'id') }}" class="block px-4 py-2 text-sm {{ app()->getLocale() === 'id' ? 'text-isoc font-semibold' : 'text-grey-700 hover:bg-grey-50' }}">🇮🇩 Indonesia</a>
                    <a href="{{ route('lang.switch', 'en') }}" class="block px-4 py-2 text-sm {{ app()->getLocale() === 'en' ? 'text-isoc font-semibold' : 'text-grey-700 hover:bg-grey-50' }}">🇬🇧 English</a>
                </div>
            </div>
            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-md text-isoc-muted hover:text-isoc" aria-label="Menu">
                <span x-show="!mobileOpen" class="material-symbols-outlined">menu</span>
                <span x-show="mobileOpen" x-cloak class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-cloak x-transition class="md:hidden border-t border-isoc-line/40 bg-white">
        <div class="px-6 py-4 space-y-1">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-sm font-medium {{ $item['active'] ? 'text-isoc bg-isoc-soft/40' : 'text-isoc-muted hover:bg-grey-50' }}">{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('participant.login') }}" class="flex items-center justify-center gap-2 mt-2 px-4 py-3 rounded-full text-sm font-semibold bg-isoc text-white">
                <span class="material-symbols-outlined text-base">person</span> {{ __('Portal Peserta') }}
            </a>
        </div>
    </div>
</nav>
