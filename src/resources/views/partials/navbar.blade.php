<nav class="fixed top-0 w-full z-50 bg-white border-b border-grey-200" x-data="{ mobileOpen: false }">
    <div class="flex justify-between items-center px-6 lg:px-8 h-[72px] max-w-7xl mx-auto">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="shrink-0">
            <img src="{{ asset('images/isoc-logo.png') }}" alt="ISOC Indonesia Jakarta Chapter" class="h-9">
        </a>

        {{-- Desktop Nav --}}
        <div class="hidden md:flex items-center gap-1">
            <a class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('about') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:text-blue hover:bg-grey-50' }} transition-colors" href="{{ route('about') }}">{{ __('Tentang Kami') }}</a>
            <a class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('programs') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:text-blue hover:bg-grey-50' }} transition-colors" href="{{ route('programs') }}">{{ __('Program') }}</a>
            <a class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('events') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:text-blue hover:bg-grey-50' }} transition-colors" href="{{ route('events') }}">{{ __('Events') }}</a>
            <a class="px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('our-partner') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:text-blue hover:bg-grey-50' }} transition-colors" href="{{ route('our-partner') }}">{{ __('Mitra Kami') }}</a>
            <a class="ml-2 inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-medium bg-blue text-white hover:bg-blue-dark transition-colors" href="{{ route('participant.login') }}">
                <span class="material-symbols-outlined text-base">person</span>
                {{ __('Portal Peserta') }}
            </a>
        </div>

        {{-- Right side: Lang switcher + Mobile burger --}}
        <div class="flex items-center gap-3">
            {{-- Language Switcher --}}
            <div class="relative" x-data="{ langOpen: false }">
                <button @click="langOpen = !langOpen" @click.outside="langOpen = false" class="flex items-center gap-1 px-3 py-1.5 rounded-md text-sm font-medium text-grey-600 hover:text-blue hover:bg-grey-50 transition-colors">
                    <span class="material-symbols-outlined text-base">translate</span>
                    <span class="uppercase">{{ app()->getLocale() }}</span>
                    <span class="material-symbols-outlined text-base">expand_more</span>
                </button>
                <div x-show="langOpen" x-cloak x-transition class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-grey-100 py-1 z-50">
                    <a href="{{ route('lang.switch', 'id') }}" class="flex items-center gap-2 px-4 py-2 text-sm {{ app()->getLocale() === 'id' ? 'text-blue font-semibold bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">
                        🇮🇩 Indonesia
                    </a>
                    <a href="{{ route('lang.switch', 'en') }}" class="flex items-center gap-2 px-4 py-2 text-sm {{ app()->getLocale() === 'en' ? 'text-blue font-semibold bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">
                        🇬🇧 English
                    </a>
                </div>
            </div>

            {{-- Mobile hamburger --}}
            <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 rounded-md text-grey-600 hover:text-blue hover:bg-grey-50 transition-colors">
                <span x-show="!mobileOpen" class="material-symbols-outlined">menu</span>
                <span x-show="mobileOpen" x-cloak class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="md:hidden border-t border-grey-100 bg-white">
        <div class="px-6 py-4 space-y-1">
            <a href="{{ route('about') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('about') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">{{ __('Tentang Kami') }}</a>
            <a href="{{ route('programs') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('programs') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">{{ __('Program') }}</a>
            <a href="{{ route('events') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('events') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">{{ __('Events') }}</a>
            <a href="{{ route('our-partner') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('our-partner') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }}">{{ __('Mitra Kami') }}</a>
            <a href="{{ route('participant.login') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('participant.*') ? 'text-blue bg-blue/5' : 'text-grey-700 hover:bg-grey-50' }} flex items-center gap-2">
                <span class="material-symbols-outlined text-base">person</span>
                {{ __('Portal Peserta') }}
            </a>
        </div>
    </div>
</nav>
