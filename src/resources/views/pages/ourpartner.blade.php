@extends('layouts.app')

@section('title', ($sections['hero']->title ?? __('Mitra Kami')) . ' - ISOC Indonesia Jakarta Chapter')

@section('content')
{{-- Hero --}}
@if($s = $sections['hero'] ?? null)
<section class="bg-navy relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 lg:py-32">
        <div class="max-w-3xl">
            <p class="text-blue-light font-semibold text-sm uppercase tracking-wider mb-4">{{ $s->subtitle }}</p>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white leading-[1.1] mb-6">{{ $s->title }}</h1>
            <p class="text-lg text-white/70 leading-relaxed max-w-2xl">{{ $s->description }}</p>
        </div>
    </div>
</section>
@endif

{{-- International Partners --}}
@if($internationalPartners->count())
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if($s = $sections['international'] ?? null)
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @endif
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @foreach($internationalPartners as $partner)
            <a href="{{ $partner->url ?? '#' }}" target="_blank" rel="noopener noreferrer" class="bg-grey-50 rounded-2xl p-8 flex flex-col items-center text-center hover:shadow-lg transition-shadow group">
                @if($partner->logo)
                <img class="h-16 object-contain mb-4 grayscale group-hover:grayscale-0 transition-all" alt="{{ $partner->name }}" src="{{ asset('storage/' . $partner->logo) }}"/>
                @elseif($partner->logo_url)
                <img class="h-16 object-contain mb-4 grayscale group-hover:grayscale-0 transition-all" alt="{{ $partner->name }}" src="{{ $partner->logo_url }}"/>
                @else
                <div class="h-16 w-16 bg-blue/10 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-blue text-3xl">handshake</span>
                </div>
                @endif
                <h3 class="font-bold text-navy text-sm group-hover:text-blue transition-colors">{{ $partner->name }}</h3>
                @if($partner->subtitle)
                <p class="text-grey-500 text-xs mt-1">{{ $partner->subtitle }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- National Partners --}}
@if($nationalPartners->count())
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if($s = $sections['national'] ?? null)
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @endif
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            @foreach($nationalPartners as $partner)
            <a href="{{ $partner->url ?? '#' }}" target="_blank" rel="noopener noreferrer" class="bg-white rounded-2xl p-8 flex flex-col items-center text-center hover:shadow-lg transition-shadow group">
                @if($partner->logo)
                <img class="h-16 object-contain mb-4 grayscale group-hover:grayscale-0 transition-all" alt="{{ $partner->name }}" src="{{ asset('storage/' . $partner->logo) }}"/>
                @elseif($partner->logo_url)
                <img class="h-16 object-contain mb-4 grayscale group-hover:grayscale-0 transition-all" alt="{{ $partner->name }}" src="{{ $partner->logo_url }}"/>
                @else
                <div class="h-16 w-16 bg-teal/10 rounded-xl flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-teal text-3xl">handshake</span>
                </div>
                @endif
                <h3 class="font-bold text-navy text-sm group-hover:text-blue transition-colors">{{ $partner->name }}</h3>
                @if($partner->subtitle)
                <p class="text-grey-500 text-xs mt-1">{{ $partner->subtitle }}</p>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Collaboration CTA --}}
@if($s = $sections['cta'] ?? null)
<section class="bg-navy py-20 lg:py-28">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">{{ $s->title }}</h2>
        <p class="text-white/60 text-lg leading-relaxed mb-8">{{ $s->description }}</p>
        @if($s->button_text)
        <a href="{{ $s->button_url ?? '#' }}" class="inline-flex items-center gap-2 bg-teal hover:bg-teal-light text-white px-8 py-3 rounded-full font-semibold text-sm transition-colors">
            {{ $s->button_text }} <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>
        @endif
    </div>
</section>
@endif
@endsection
