@extends('layouts.app')

@section('title', 'ISOC Jakarta - Internet Is For Everyone')

@php
    use App\Support\HomePageContent;

    $hero = $content['hero'];
    $about = $content['about'];
    $mission = $content['mission'];
    $board = $content['board'];
    $programs = $content['programs'];
    $eventSection = $content['events'];
    $partners = $content['partners'];
    $heroImage = HomePageContent::image($hero['image'] ?? null);
    $aboutImage = HomePageContent::image($about['image'] ?? null);
    $link = fn (?string $url) => blank($url) ? '#' : (str_starts_with($url, 'http') || str_starts_with($url, '#') || str_starts_with($url, 'mailto:') ? $url : url($url));
@endphp

@section('content')
<div class="bg-[#f8f9fa] text-isoc-ink">
    {{-- Hero --}}
    <section class="relative overflow-hidden">
        @if ($heroImage)
            <div class="absolute inset-0">
                <img src="{{ $heroImage }}" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-[#f8f9fa] via-[#f8f9fa]/90 to-transparent"></div>
            </div>
        @else
            <div class="absolute inset-0" aria-hidden="true">
                <div class="absolute -right-32 -top-32 w-[620px] h-[620px] rounded-full bg-isoc-soft/60 blur-3xl"></div>
                <div class="absolute right-24 bottom-0 w-[360px] h-[360px] rounded-full bg-isoc-container/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.35]" style="background-image: radial-gradient(#075bb1 1px, transparent 1px); background-size: 28px 28px; mask-image: linear-gradient(to left, black, transparent 70%);"></div>
            </div>
        @endif
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-24 md:py-32 lg:py-40">
            <div class="max-w-2xl">
                <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-4 block">{{ $hero['eyebrow'] }}</span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-[1.1] tracking-tight mb-6">
                    {{ $hero['headline_before'] }} <span class="text-isoc">{{ $hero['headline_highlight'] }}</span> {{ $hero['headline_after'] }}
                </h1>
                <p class="text-lg text-isoc-muted leading-relaxed mb-8">{{ $hero['description'] }}</p>
                <div class="flex flex-wrap gap-3">
                    @if ($hero['primary_button_text'])
                        <a href="{{ $link($hero['primary_button_url']) }}" class="inline-flex items-center gap-2 bg-isoc hover:bg-isoc-container text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                            {{ $hero['primary_button_text'] }} <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </a>
                    @endif
                    @if ($hero['secondary_button_text'])
                        <a href="{{ $link($hero['secondary_button_url']) }}" class="inline-flex items-center gap-2 bg-white border border-isoc-line hover:border-isoc text-isoc-ink px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                            {{ $hero['secondary_button_text'] }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Siapa kami --}}
    <section id="tentang" class="scroll-mt-24 py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 grid lg:grid-cols-2 gap-14 items-center">
            <div class="relative">
                <div class="absolute -top-4 -left-4 w-24 h-24 bg-isoc-soft rounded-full opacity-60 -z-0"></div>
                <div class="relative border border-isoc-line/60 p-4 rounded-xl bg-white">
                    <div class="aspect-video rounded-lg overflow-hidden bg-[#f3f4f5] flex items-center justify-center">
                        @if ($aboutImage)
                            <img src="{{ $aboutImage }}" alt="{{ $about['title'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-center px-8">
                                <img src="{{ asset('images/isoc-logo.png') }}" alt="ISOC" class="h-14 w-auto mx-auto mb-4">
                                <p class="text-isoc font-bold">The Internet Is for Everyone</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-2 block">{{ $about['eyebrow'] }}</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-6">{{ $about['title'] }}</h2>
                <p class="text-isoc-muted leading-relaxed mb-4">{{ $about['description'] }}</p>
                @if ($about['vision'])
                    <p class="text-lg font-bold text-isoc leading-relaxed mt-4">{{ $about['vision'] }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Misi --}}
    <section class="bg-[#f3f4f5] py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-2 block">{{ $mission['eyebrow'] }}</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight">{{ $mission['title'] }}</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($mission['pillars'] as $pillar)
                    <div class="bg-white border border-isoc-line/60 rounded-xl p-7 hover:shadow-lg hover:-translate-y-0.5 transition">
                        <div class="w-12 h-12 rounded-lg bg-isoc-soft text-isoc flex items-center justify-center mb-6"><span class="material-symbols-outlined">{{ $pillar['icon'] ?: 'star' }}</span></div>
                        <h3 class="text-xl font-bold mb-3">{{ $pillar['title'] }}</h3>
                        <p class="text-isoc-muted text-sm leading-relaxed">{{ $pillar['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pengurus --}}
    <section id="pengurus" class="scroll-mt-24 py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-2 block">{{ $board['eyebrow'] }}</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight">{{ $board['title'] }}</h2>
            </div>
            <div class="space-y-12">
                @foreach ($board['groups'] as $group)
                    <div>
                        <h3 class="text-center text-lg font-bold text-isoc mb-6">{{ $group['name'] }}</h3>
                        <div class="flex flex-wrap justify-center gap-5">
                            @foreach ($group['members'] ?? [] as $member)
                                <div class="w-[200px] bg-white border border-isoc-line/60 rounded-xl p-5 text-center">
                                    @if ($photo = HomePageContent::image($member['photo'] ?? null))
                                        <img src="{{ $photo }}" alt="{{ $member['name'] }}" class="w-20 h-20 rounded-full object-cover mx-auto mb-4">
                                    @else
                                        <div class="w-20 h-20 rounded-full bg-isoc-soft text-isoc flex items-center justify-center mx-auto mb-4"><span class="material-symbols-outlined text-4xl">person</span></div>
                                    @endif
                                    <p class="font-bold leading-snug">{{ $member['name'] }}</p>
                                    <p class="text-isoc text-sm mt-1">{{ $member['role'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Program --}}
    <section id="program" class="scroll-mt-24 bg-[#f3f4f5] py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-2 block">{{ $programs['eyebrow'] }}</span>
                <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-4">{{ $programs['title'] }}</h2>
                <p class="text-isoc-muted leading-relaxed">{{ $programs['description'] }}</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($programs['items'] as $program)
                    @if ($program['featured'] ?? false)
                        <div class="md:col-span-3 rounded-xl p-8 md:p-10 bg-isoc text-white flex flex-col md:flex-row md:items-center gap-6 justify-between">
                            <div class="max-w-2xl">
                                @if ($program['label'] ?? null)
                                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-white/70 mb-2 block">{{ $program['label'] }}</span>
                                @endif
                                <h3 class="text-2xl font-bold mb-3">{{ $program['title'] }}</h3>
                                <p class="text-white/80 leading-relaxed">{{ $program['description'] }}</p>
                            </div>
                            <span class="material-symbols-outlined text-7xl text-white/30">{{ $program['icon'] ?: 'school' }}</span>
                        </div>
                    @else
                        <div class="bg-white border border-isoc-line/60 rounded-xl p-7 flex flex-col">
                            <div class="w-12 h-12 rounded-lg bg-isoc-soft text-isoc flex items-center justify-center mb-6"><span class="material-symbols-outlined">{{ $program['icon'] ?: 'auto_stories' }}</span></div>
                            <h3 class="text-xl font-bold mb-3">{{ $program['title'] }}</h3>
                            <p class="text-isoc-muted text-sm leading-relaxed flex-1">{{ $program['description'] }}</p>
                            @if (! empty($program['tags']))
                                <div class="flex flex-wrap gap-2 mt-5">
                                    @foreach ($program['tags'] as $tag)
                                        <span class="text-xs font-semibold bg-isoc-soft/60 text-isoc px-3 py-1 rounded-full">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- Event: kartu event terdekat + agenda berikutnya --}}
    @if (($eventSection['enabled'] ?? true) && $events->isNotEmpty())
        @php
            // Judul event berpola "Digital Safety Champions - {Kota}": kota dijadikan judul utama kartu.
            $city = fn ($event) => \Illuminate\Support\Str::contains($event->title, ' - ') ? \Illuminate\Support\Str::afterLast($event->title, ' - ') : $event->title;
            $program = fn ($event) => \Illuminate\Support\Str::contains($event->title, ' - ') ? \Illuminate\Support\Str::beforeLast($event->title, ' - ') : $event->category;
            $countdown = function ($event) {
                if (! $event->date) {
                    return null;
                }
                $days = (int) now()->startOfDay()->diffInDays($event->date->copy()->startOfDay(), false);

                return $days > 0 ? 'H-' . $days : ($days === 0 ? 'Hari ini' : 'Berlangsung');
            };
            $seats = fn ($event) => max(0, (int) $event->max_participants - $event->confirmed_count);
            $fill = fn ($event) => $event->max_participants ? min(100, (int) round($event->confirmed_count / $event->max_participants * 100)) : 0;
            $featured = $events->first();
            $others = $events->slice(1);
        @endphp
        <section id="event" class="scroll-mt-24 py-20 lg:py-28 relative overflow-hidden">
            <div class="absolute -left-40 top-20 w-[420px] h-[420px] rounded-full bg-isoc-soft/50 blur-3xl" aria-hidden="true"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
                <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-12">
                    <div class="max-w-2xl">
                        <span class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-2 block">{{ $eventSection['eyebrow'] }}</span>
                        <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">{{ $eventSection['title'] }}</h2>
                        <p class="text-isoc-muted leading-relaxed">{{ $eventSection['description'] }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-isoc-muted">
                        <span class="inline-flex items-center gap-2 bg-white border border-isoc-line/60 rounded-full px-4 py-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <strong class="text-isoc-ink">{{ $eventsTotal }}</strong> event akan datang
                        </span>
                    </div>
                </div>

                <div class="grid lg:grid-cols-5 gap-6 items-stretch">
                    {{-- Event terdekat --}}
                    <article class="lg:col-span-3 bg-white border border-isoc-line/60 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow flex flex-col">
                        <div class="relative bg-gradient-to-br from-isoc via-isoc to-isoc-container text-white p-8 md:p-10 overflow-hidden">
                            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 22px 22px;" aria-hidden="true"></div>
                            <span class="material-symbols-outlined absolute -right-6 -bottom-10 text-[180px] text-white/10" aria-hidden="true">shield_lock</span>
                            <div class="relative flex flex-wrap items-center gap-2 mb-8">
                                <span class="bg-white/15 backdrop-blur px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Event terdekat</span>
                                @if ($countdown($featured))
                                    <span class="bg-amber-400 text-amber-950 px-3 py-1 rounded-full text-xs font-extrabold">{{ $countdown($featured) }}</span>
                                @endif
                            </div>
                            <p class="relative text-white/80 text-sm font-semibold mb-1">{{ $program($featured) }}</p>
                            <h3 class="relative text-4xl md:text-5xl font-extrabold tracking-tight">{{ $city($featured) }}</h3>
                        </div>
                        <div class="p-8 md:p-10 flex flex-col flex-1">
                            <div class="grid sm:grid-cols-3 gap-5 mb-8">
                                <div class="flex gap-3">
                                    <span class="w-10 h-10 shrink-0 rounded-lg bg-isoc-soft text-isoc flex items-center justify-center"><span class="material-symbols-outlined text-xl">calendar_month</span></span>
                                    <div><p class="text-xs text-isoc-muted">Tanggal</p><p class="font-semibold text-sm">{{ $featured->date?->translatedFormat('l, d M Y') ?? '-' }}</p></div>
                                </div>
                                <div class="flex gap-3">
                                    <span class="w-10 h-10 shrink-0 rounded-lg bg-isoc-soft text-isoc flex items-center justify-center"><span class="material-symbols-outlined text-xl">schedule</span></span>
                                    <div><p class="text-xs text-isoc-muted">Waktu</p><p class="font-semibold text-sm">{{ $featured->time_info ?? '-' }}</p></div>
                                </div>
                                <div class="flex gap-3">
                                    <span class="w-10 h-10 shrink-0 rounded-lg bg-isoc-soft text-isoc flex items-center justify-center"><span class="material-symbols-outlined text-xl">location_on</span></span>
                                    <div class="min-w-0"><p class="text-xs text-isoc-muted">Lokasi</p><p class="font-semibold text-sm">{{ $featured->location ?? '-' }}</p></div>
                                </div>
                            </div>

                            @if ($featured->description)
                                <p class="text-sm text-isoc-muted leading-relaxed mb-8">{{ \Illuminate\Support\Str::limit($featured->description, 180) }}</p>
                            @endif

                            @if ($featured->max_participants)
                                <div class="mb-8">
                                    <div class="flex justify-between text-sm mb-2">
                                        <span class="text-isoc-muted"><strong class="text-isoc-ink">{{ $featured->confirmed_count }}</strong> / {{ $featured->max_participants }} peserta terdaftar</span>
                                        <span class="font-semibold {{ $seats($featured) <= 10 ? 'text-red-600' : 'text-emerald-600' }}">{{ $seats($featured) }} kursi tersisa</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-[#edeeef] overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r from-isoc to-isoc-container" style="width: {{ max(2, $fill($featured)) }}%"></div></div>
                                </div>
                            @endif

                            <div class="mt-auto flex flex-wrap gap-3">
                                <a href="{{ route('event.register', $featured->slug) }}" class="inline-flex items-center gap-2 rounded-full px-7 py-3 text-sm font-semibold {{ $featured->canRegister() ? 'bg-isoc text-white hover:bg-isoc-container' : 'bg-[#edeeef] text-isoc-muted' }} transition-colors">
                                    {{ $featured->canRegister() ? 'Daftar Sekarang' : 'Lihat Detail' }} <span class="material-symbols-outlined text-base">arrow_forward</span>
                                </a>
                                <a href="{{ route('events') }}" class="inline-flex items-center gap-2 rounded-full px-6 py-3 text-sm font-semibold border border-isoc-line hover:border-isoc text-isoc-ink transition-colors">Semua event</a>
                            </div>
                        </div>
                    </article>

                    {{-- Agenda berikutnya --}}
                    <div class="lg:col-span-2 bg-white border border-isoc-line/60 rounded-2xl p-6 flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-lg">Agenda berikutnya</h3>
                            <span class="material-symbols-outlined text-isoc">event_upcoming</span>
                        </div>
                        <div class="flex-1 divide-y divide-isoc-line/40">
                            @forelse ($others as $event)
                                <a href="{{ route('event.register', $event->slug) }}" class="group flex items-center gap-4 py-4 -mx-2 px-2 rounded-xl hover:bg-isoc-soft/30 transition-colors">
                                    <div class="w-14 h-14 shrink-0 rounded-xl border border-isoc-line/60 bg-[#f8f9fa] flex flex-col items-center justify-center leading-none group-hover:border-isoc group-hover:bg-white transition-colors">
                                        <span class="text-[10px] font-bold uppercase text-isoc">{{ $event->date?->translatedFormat('M') }}</span>
                                        <span class="text-xl font-extrabold mt-1">{{ $event->date?->format('d') ?? '--' }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-bold truncate group-hover:text-isoc transition-colors">{{ $city($event) }}</p>
                                        <p class="text-xs text-isoc-muted truncate">{{ $event->date?->translatedFormat('l') }} · {{ $event->time_info }}</p>
                                        <div class="mt-2 h-1.5 rounded-full bg-[#edeeef] overflow-hidden"><div class="h-full bg-isoc/70 rounded-full" style="width: {{ max(2, $fill($event)) }}%"></div></div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        @if ($countdown($event))
                                            <span class="block text-xs font-bold text-isoc bg-isoc-soft/60 rounded-full px-2.5 py-1">{{ $countdown($event) }}</span>
                                        @endif
                                        <span class="material-symbols-outlined text-isoc-line group-hover:text-isoc group-hover:translate-x-0.5 transition mt-1">chevron_right</span>
                                    </div>
                                </a>
                            @empty
                                <p class="py-6 text-sm text-isoc-muted">Belum ada agenda lain. Pantau terus halaman event.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('events') }}" class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-[#f3f4f5] hover:bg-isoc-soft/60 text-isoc font-semibold text-sm py-3 transition-colors">
                            Lihat semua {{ $eventsTotal }} event <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Mitra --}}
    <section id="mitra" class="scroll-mt-24 {{ (($eventSection['enabled'] ?? true) && $events->isNotEmpty()) ? 'bg-[#f3f4f5]' : '' }} py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-12">{{ $partners['title'] }}</h2>
            @foreach ([[$partners['global_label'], $partners['global']], [$partners['national_label'], $partners['national']]] as [$label, $list])
                @if (! empty($list))
                    <p class="text-isoc text-xs font-bold uppercase tracking-[0.2em] mb-5">{{ $label }}</p>
                    <div class="flex flex-wrap justify-center gap-4 mb-12">
                        @foreach ($list as $partner)
                            @php $tag = filled($partner['url'] ?? null) ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if ($tag === 'a') href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer" @endif class="w-[180px] h-32 px-5 py-4 bg-white border border-isoc-line/60 rounded-xl flex flex-col items-center justify-center gap-2 hover:border-isoc hover:shadow-md transition">
                                @if ($logo = HomePageContent::image($partner['logo'] ?? null))
                                    <img src="{{ $logo }}" alt="{{ $partner['name'] }}" class="h-14 max-w-[140px] object-contain">
                                    <span class="text-xs font-semibold text-isoc-muted leading-tight">{{ $partner['name'] }}</span>
                                @else
                                    <span class="w-12 h-12 rounded-full bg-isoc-soft text-isoc flex items-center justify-center"><span class="material-symbols-outlined">handshake</span></span>
                                    <span class="text-sm font-bold text-isoc-ink leading-tight">{{ $partner['name'] }}</span>
                                @endif
                            </{{ $tag }}>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    </section>
</div>
@endsection
