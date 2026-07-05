@php
    $site = $content['site'];
    $nav = $content['navigation'] ?? [];
    $hero = $content['hero'];
    $about = $content['about'];
    $pillars = $content['pillars'];
    $management = $content['management'];
    $programs = $content['programs'];
    $partners = $content['partners'];
    $contact = $content['contact'];
    $socials = $content['socials'] ?? [];
    $legalLinks = $content['legal_links'] ?? [];
    $heroTitle = $hero['title'] ?? '';
    $highlight = $hero['highlight'] ?? '';
    $heroParts = $highlight !== '' ? explode($highlight, $heroTitle, 2) : [$heroTitle];
    $media = fn (?string $path, ?string $url = null): ?string => filled($path) ? asset('storage/' . $path) : $url;
@endphp

<div>
    <header
        x-data="{ compact: false, mobileOpen: false }"
        x-init="compact = window.scrollY > 20; window.addEventListener('scroll', () => compact = window.scrollY > 20)"
        :class="compact ? 'h-16 shadow-sm bg-surface/95' : 'h-20 bg-surface/80'"
        class="backdrop-blur-md sticky top-0 z-50 transition-all duration-300 border-b border-outline-variant/30"
    >
        <nav class="flex justify-between items-center w-full px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto h-full">
            <a href="/" class="flex items-center" aria-label="ISOC Jakarta">
                <img alt="ISOC Logo" class="h-10 md:h-12 w-auto object-contain" src="{{ $media($site['logo_path'] ?? null, $site['logo_url'] ?? null) }}">
            </a>
            <div class="hidden md:flex items-center gap-8">
                @foreach ($nav as $item)
                    <a class="text-on-surface-variant hover:text-primary transition-colors text-label-md font-label-md" href="{{ $item['url'] ?? '#' }}">
                        {{ $item['label'] ?? '' }}
                    </a>
                @endforeach
            </div>
            <button
                type="button"
                class="md:hidden w-10 h-10 inline-flex items-center justify-center rounded-lg border border-outline-variant/40 text-primary"
                aria-label="Buka menu"
                x-on:click="mobileOpen = ! mobileOpen"
            >
                <span class="material-symbols-outlined">menu</span>
            </button>
        </nav>
        <div
            x-cloak
            x-show="mobileOpen"
            x-transition
            class="md:hidden bg-surface border-t border-outline-variant/30 px-margin-mobile py-4 space-y-3"
        >
            @foreach ($nav as $item)
                <a class="block text-label-md text-on-surface-variant hover:text-primary" href="{{ $item['url'] ?? '#' }}" x-on:click="mobileOpen = false">
                    {{ $item['label'] ?? '' }}
                </a>
            @endforeach
        </div>
    </header>

    <main>
        <section class="relative min-h-[70vh] flex items-center overflow-hidden">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-r from-background via-background/90 to-transparent z-10"></div>
                <img alt="Background Digital Connection" class="w-full h-full object-cover" src="{{ $media($hero['background_path'] ?? null, $hero['background_url'] ?? null) }}">
            </div>
            <div class="relative z-20 px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto w-full">
                <div class="max-w-3xl py-20">
                    <span class="text-primary font-label-sm text-label-sm uppercase tracking-widest mb-4 block">{{ $hero['eyebrow'] }}</span>
                    <h1 class="text-display-lg-mobile md:text-display-lg font-display-lg mb-6 leading-tight">
                        @if ($highlight !== '' && count($heroParts) === 2)
                            {{ $heroParts[0] }}<span class="text-primary">{{ $highlight }}</span>{{ $heroParts[1] }}
                        @else
                            {{ $heroTitle }}
                        @endif
                    </h1>
                    <p class="text-body-lg font-body-lg text-on-surface-variant mb-4 leading-relaxed">{{ $hero['description'] }}</p>
                </div>
            </div>
        </section>

        <section class="py-section-gap-lg px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto" id="tentang">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div class="relative">
                    <div class="absolute -top-4 -left-4 w-24 h-24 bg-secondary-container rounded-full opacity-50 -z-10"></div>
                    <div class="border border-outline-variant p-4 rounded-xl bg-surface">
                        <div class="bg-surface-container-low aspect-video rounded-lg flex items-center justify-center overflow-hidden">
                            <img alt="{{ $about['title'] }}" class="w-full h-full object-cover" src="{{ $media($about['image_path'] ?? null, $about['image_url'] ?? null) }}">
                        </div>
                    </div>
                </div>
                <div>
                    <span class="text-primary font-label-sm text-label-sm uppercase tracking-widest mb-2 block">{{ $about['eyebrow'] }}</span>
                    <h2 class="text-headline-md font-headline-md mb-6">{{ $about['title'] }}</h2>
                    <p class="text-body-md font-body-md text-on-surface-variant mb-6 leading-relaxed">{{ $about['description'] }}</p>
                    <p class="text-body-lg font-bold text-primary leading-relaxed mt-4">{{ $about['vision'] }}</p>
                </div>
            </div>
        </section>

        <section class="bg-surface-container-low py-section-gap-lg" id="program-pillars">
            <div class="px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto text-center mb-16">
                <span class="text-primary font-label-sm text-label-sm uppercase tracking-widest mb-2 block">{{ $pillars['eyebrow'] }}</span>
                <h2 class="text-headline-md font-headline-md max-w-3xl mx-auto">{{ $pillars['title'] }}</h2>
            </div>
            <div class="px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto grid grid-cols-1 md:grid-cols-3 gap-gutter">
                @foreach ($pillars['items'] ?? [] as $pillar)
                    <article class="bg-surface p-8 rounded-xl border border-outline-variant hover:border-primary transition-all group">
                        <div class="w-14 h-14 bg-secondary-container rounded-full flex items-center justify-center mb-6 text-primary group-hover:bg-primary group-hover:text-on-primary transition-all">
                            <span class="material-symbols-outlined text-3xl">{{ $pillar['icon'] ?? 'circle' }}</span>
                        </div>
                        <h3 class="text-headline-sm font-headline-sm mb-4">{{ $pillar['title'] ?? '' }}</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant">{{ $pillar['description'] ?? '' }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="py-section-gap-lg bg-surface" id="pengurus">
            <div class="px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto">
                <div class="text-center mb-16">
                    <span class="text-primary font-label-sm text-label-sm uppercase tracking-widest mb-2 block">{{ $management['eyebrow'] }}</span>
                    <h2 class="text-headline-md font-headline-md">{{ $management['title'] }}</h2>
                </div>

                @foreach ($managementGroups as $group)
                    @if ($group->layout === 'grid')
                        <div class="mb-16">
                            <h3 class="text-headline-sm font-headline-sm mb-8 text-center text-secondary">{{ $group->name }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-gutter">
                                @foreach ($group->activeMembers as $member)
                                    <article class="text-center bg-surface-container-low p-6 rounded-2xl border border-outline-variant/30">
                                        @include('partials.public.member-avatar', ['member' => $member, 'size' => 'large'])
                                        <h4 class="font-bold text-body-md">{{ $member->name }}</h4>
                                        <p class="text-label-md text-tertiary">{{ $member->position }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="grid grid-cols-1 md:grid-cols-2 gap-gutter">
                    @foreach ($managementGroups as $group)
                        @if ($group->layout !== 'grid')
                            <section class="bg-surface-container-low p-8 rounded-2xl border border-outline-variant/30">
                                <h3 class="text-headline-sm font-headline-sm mb-6 text-primary">{{ $group->name }}</h3>
                                <div class="space-y-6">
                                    @foreach ($group->activeMembers as $member)
                                        <article class="flex items-center gap-6">
                                            @include('partials.public.member-avatar', ['member' => $member, 'size' => 'small'])
                                            <div>
                                                <h4 class="font-bold text-body-lg">{{ $member->name }}</h4>
                                                <p class="text-label-md text-tertiary">{{ $member->position }}</p>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-section-gap-lg px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto" id="program">
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-6">
                <div class="max-w-2xl">
                    <span class="text-primary font-label-sm text-label-sm uppercase tracking-widest mb-2 block">{{ $programs['eyebrow'] }}</span>
                    <h2 class="text-headline-md font-headline-md mb-4">{{ $programs['title'] }}</h2>
                    <p class="text-body-md text-on-surface-variant">{{ $programs['description'] }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">
                @foreach ($programs['items'] ?? [] as $program)
                    @php($style = $program['style'] ?? 'compact')
                    <article @class([
                        'rounded-xl p-8 transition-all',
                        'md:col-span-8 bg-surface border border-outline-variant group hover:shadow-lg relative overflow-hidden' => $style === 'wide',
                        'md:col-span-4 bg-primary text-on-primary flex flex-col justify-between' => $style === 'primary',
                        'md:col-span-4 bg-surface-container flex flex-col justify-between border border-outline-variant' => $style === 'compact',
                        'md:col-span-8 bg-surface border border-outline-variant flex flex-col md:flex-row gap-8 items-center' => $style === 'wide-icon',
                    ])>
                        @if ($style === 'wide')
                            <div class="absolute top-0 right-0 p-8">
                                <span class="material-symbols-outlined text-6xl text-primary/10">{{ $program['icon'] ?? 'auto_stories' }}</span>
                            </div>
                        @endif

                        <div @class(['flex-1' => $style === 'wide-icon'])>
                            @if (! empty($program['badge']))
                                <div class="inline-block px-4 py-1 bg-primary/10 text-primary rounded-full text-label-sm font-bold mb-4">{{ $program['badge'] }}</div>
                            @endif
                            @if ($style !== 'wide')
                                <span @class([
                                    'material-symbols-outlined text-4xl mb-6 block',
                                    'text-primary' => $style !== 'primary',
                                ])>{{ $program['icon'] ?? 'circle' }}</span>
                            @endif
                            <h3 class="text-headline-sm font-headline-sm mb-4">{{ $program['title'] ?? '' }}</h3>
                            <p @class([
                                'text-body-md text-on-surface-variant mb-6' => $style === 'wide',
                                'text-label-md' => $style === 'primary',
                                'text-label-md text-on-surface-variant' => $style === 'compact',
                                'text-body-md text-on-surface-variant' => $style === 'wide-icon',
                            ])>{{ $program['description'] ?? '' }}</p>
                            @if (! empty($program['tags']))
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($program['tags'] as $tag)
                                        <span class="px-3 py-1 bg-secondary-container text-on-secondary-container rounded-full text-label-sm">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @if ($style === 'wide-icon')
                            <div class="w-32 h-32 bg-background rounded-2xl flex items-center justify-center shadow-inner">
                                <span class="material-symbols-outlined text-5xl text-primary">{{ $program['icon'] ?? 'school' }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section class="py-section-gap-lg bg-surface-container-low" id="mitra">
            <div class="px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-headline-sm font-headline-sm uppercase tracking-widest text-on-surface-variant">{{ $partners['title'] }}</h2>
                </div>
                @foreach ($partnerGroups as $group)
                    <div class="mb-12">
                        <p class="text-center font-bold text-label-md mb-8 text-primary">{{ $group->name }}</p>
                        <div @class([
                            'grid gap-4' => $group->activePartners->count() > 1,
                            'grid-cols-2 md:grid-cols-3 lg:grid-cols-6' => $group->activePartners->count() > 1,
                            'flex justify-center' => $group->activePartners->count() === 1,
                        ])>
                            @foreach ($group->activePartners as $partner)
                                <a href="{{ $partner->url ?: '#' }}" class="bg-surface min-h-24 rounded-lg flex flex-col items-center justify-center gap-3 p-4 border border-outline-variant/20 {{ $group->activePartners->count() === 1 ? 'w-64 min-h-32 rounded-xl p-8' : '' }}">
                                    @if ($partner->logo_src)
                                        <img src="{{ $partner->logo_src }}" alt="{{ $partner->name }}" class="max-h-12 max-w-full object-contain">
                                    @endif
                                    <span class="font-bold text-label-sm text-center {{ $group->activePartners->count() === 1 ? 'text-headline-sm text-outline' : '' }}">{{ $partner->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="bg-surface-container-low border-t border-outline-variant/30">
        <div class="w-full py-section-gap-md px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto grid grid-cols-1 md:grid-cols-4 gap-gutter">
            <div class="md:col-span-2">
                <div class="flex items-center gap-4 mb-6">
                    <img alt="ISOC Logo" class="h-10 w-auto" src="{{ $media($site['logo_path'] ?? null, $site['logo_url'] ?? null) }}">
                </div>
                <p class="text-body-md text-on-surface-variant max-w-sm mb-8">{{ $site['footer_description'] }}</p>
                <div class="flex gap-4">
                    @foreach ($socials as $social)
                        <a class="w-10 h-10 bg-surface rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-on-primary transition-all shadow-sm border border-outline-variant/20" href="{{ $social['url'] ?? '#' }}" target="_blank" rel="noopener">
                            @if (filled($media($social['image_path'] ?? null, $social['image_url'] ?? null)))
                                <img alt="{{ $social['label'] ?? 'Social' }}" class="w-5 h-5 opacity-70" src="{{ $media($social['image_path'] ?? null, $social['image_url'] ?? null) }}">
                            @else
                                <span class="material-symbols-outlined text-lg">{{ $social['icon'] ?? 'public' }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
            <div>
                <h4 class="font-bold text-body-md mb-6">Tautan Cepat</h4>
                <ul class="space-y-4">
                    @foreach ($nav as $item)
                        <li><a class="text-label-sm text-on-surface-variant hover:text-primary transition-colors" href="{{ $item['url'] ?? '#' }}">{{ $item['label'] ?? '' }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-body-md mb-6">{{ $contact['title'] }}</h4>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-lg">mail</span>
                        <a class="text-label-sm text-on-surface-variant hover:text-primary" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-lg">share</span>
                        <a class="text-label-sm text-on-surface-variant hover:text-primary" href="{{ $contact['instagram_url'] }}" target="_blank" rel="noopener">{{ $contact['instagram'] }}</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary text-lg">location_on</span>
                        <span class="text-label-sm text-on-surface-variant">{{ $contact['address'] }}</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-outline-variant/30 px-margin-mobile md:px-margin-desktop max-w-container-max mx-auto py-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-label-sm text-on-surface-variant">{{ $site['copyright'] }}</p>
            <div class="flex gap-6 text-label-sm text-on-surface-variant">
                @foreach ($legalLinks as $link)
                    <a class="hover:text-primary" href="{{ $link['url'] ?? '#' }}">{{ $link['label'] ?? '' }}</a>
                @endforeach
            </div>
        </div>
    </footer>
</div>
