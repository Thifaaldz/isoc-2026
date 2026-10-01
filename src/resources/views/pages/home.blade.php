@extends('layouts.app')

@section('title', 'ISOC Indonesia Jakarta Chapter - Internet is for Everyone')

@section('content')
{{-- Hero --}}
@if($s = $sections['hero'] ?? null)
<section class="bg-navy relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 lg:py-32">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="relative z-10">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-[1.1] mb-6">{{ $s->title }}</h1>
                <p class="text-lg text-white/70 mb-8 leading-relaxed max-w-lg">{{ $s->description }}</p>
                <div class="flex flex-wrap gap-4">
                    @if($s->button_text)
                    <a href="{{ $s->button_url ?? route('about') }}" class="bg-blue hover:bg-blue-light text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">{{ $s->button_text }}</a>
                    @endif
                    @if($s->secondary_button_text)
                    <a href="{{ $s->secondary_button_url ?? route('programs') }}" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-7 py-3 rounded-full font-semibold text-sm transition-colors">{{ $s->secondary_button_text }}</a>
                    @endif
                </div>
            </div>
            <div class="relative">
                <img class="w-full rounded-2xl shadow-2xl" alt="ISOC Indonesia" src="{{ $s->image ? asset('storage/' . $s->image) : 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJ2ChLx_U5TridhLE9qBbB7zxLjd4Jv9dEHfWLVrz3MwmltV5vvHVGd6jvVCvZDRuARoMcJ4knlhIsqku_zxjRawAIaEp4zXGal0NtO3YrIE_nV6PU9wivEquyJgXFOmS2FSpe1Nbc6OueRnNyeqoyiUwDmnZj_D7KRx8yKlfpTtB4rhbCenUdPQ7alb0IJMjFTAd-Q1d_nil9vUxhchUct5iTiSVF8JKZ8bdpEFqjKrE08irC15Y8DwBi6jgLUF8DTos483qjALg' }}"/>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Mission --}}
@if($s = $sections['mission'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['mission'] ?? collect())->count())
        <div class="grid md:grid-cols-3 gap-8">
            @foreach($items['mission'] as $item)
            <div class="bg-grey-50 rounded-2xl p-8 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-{{ $item->icon_color ?? 'blue' }}/10 rounded-xl flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-{{ $item->icon_color ?? 'blue' }} text-2xl">{{ $item->icon }}</span>
                </div>
                <h3 class="text-lg font-bold text-navy mb-3">{{ $item->title }}</h3>
                <p class="text-grey-600 text-sm leading-relaxed">{{ $item->description }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Featured Programs --}}
@if($s = $sections['featured_programs'] ?? null)
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
            <div>
                <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
                <h2 class="text-3xl md:text-4xl font-bold text-navy">{{ $s->title }}</h2>
            </div>
            <a href="{{ route('programs') }}" class="text-blue font-semibold text-sm flex items-center gap-1 hover:gap-2 transition-all">
                {{ $s->button_text ?? 'Lihat Semua Program' }} <span class="material-symbols-outlined text-lg">arrow_forward</span>
            </a>
        </div>
        @if(($items['featured_programs'] ?? collect())->count())
        <div class="grid md:grid-cols-2 gap-8">
            @foreach($items['featured_programs'] as $item)
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-lg transition-shadow group">
                @php $imgSrc = $item->image ? asset('storage/' . $item->image) : ($item->extra_data['image_url'] ?? null); @endphp
                @if($imgSrc)
                <div class="h-56 overflow-hidden">
                    <img alt="{{ $item->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ $imgSrc }}"/>
                </div>
                @endif
                <div class="p-8">
                    @if($item->extra_data['category'] ?? null)
                    <span class="text-{{ $item->icon_color ?? 'blue' }} text-xs font-semibold uppercase tracking-wider">{{ $item->extra_data['category'] }}</span>
                    @endif
                    <h3 class="text-xl font-bold text-navy mt-2 mb-3">{{ $item->title }}</h3>
                    <p class="text-grey-600 text-sm leading-relaxed mb-5">{{ $item->description }}</p>
                    <a href="{{ $item->url ?? route('programs') }}" class="text-blue font-semibold text-sm flex items-center gap-1 hover:gap-2 transition-all">Selengkapnya <span class="material-symbols-outlined text-lg">arrow_forward</span></a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Impact Stats --}}
@if($s = $sections['impact_stats'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy">{{ $s->title }}</h2>
        </div>
        @if(($items['impact_stats'] ?? collect())->count())
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            @foreach($items['impact_stats'] as $item)
            <div class="text-center p-6">
                <span class="block text-4xl md:text-5xl font-extrabold text-navy mb-2">{{ $item->extra_data['value'] ?? $item->title }}</span>
                <span class="text-grey-500 text-sm">{{ $item->description }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Closing --}}
@if($s = $sections['closing'] ?? null)
<section class="bg-navy py-20 lg:py-28">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-5xl font-bold text-white mb-6">{{ $s->title }}</h2>
        <p class="text-white/60 text-lg leading-relaxed mb-4">{{ $s->description }}</p>
        @if($s->subtitle)
        <p class="text-white/60 leading-relaxed">{{ $s->subtitle }}</p>
        @endif
    </div>
</section>
@endif
@endsection
