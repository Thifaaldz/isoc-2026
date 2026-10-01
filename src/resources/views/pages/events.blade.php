@extends('layouts.app')

@section('title', ($sections['hero']->title ?? __('Events')) . ' - ISOC Indonesia Jakarta Chapter')

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

{{-- Featured Event --}}
@if($featuredEvent)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                @if($featuredEvent->image)
                <img class="w-full rounded-2xl shadow-xl" alt="{{ $featuredEvent->title }}" src="{{ asset('storage/' . $featuredEvent->image) }}"/>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-teal/10 text-teal text-xs font-semibold px-3 py-1 rounded-full uppercase">{{ __('Featured Event') }}</span>
                    @if($featuredEvent->category)
                    <span class="bg-blue/10 text-blue text-xs font-semibold px-3 py-1 rounded-full">{{ $featuredEvent->category }}</span>
                    @endif
                </div>
                <h2 class="text-3xl md:text-4xl font-bold text-navy mb-4">{{ $featuredEvent->title }}</h2>
                <div class="space-y-3 mb-6 text-sm text-grey-600">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue text-lg">calendar_today</span>
                        {{ $featuredEvent->date->translatedFormat('d F Y') }}
                    </div>
                    @if($featuredEvent->time_info)
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue text-lg">schedule</span>
                        {{ $featuredEvent->time_info }}
                    </div>
                    @endif
                    @if($featuredEvent->location)
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue text-lg">location_on</span>
                        {{ $featuredEvent->location }}
                    </div>
                    @endif
                    @if($featuredEvent->capacity_info)
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue text-lg">group</span>
                        {{ $featuredEvent->capacity_info }}
                    </div>
                    @endif
                </div>
                <p class="text-grey-600 leading-relaxed mb-6">{{ $featuredEvent->description }}</p>
                <div class="flex flex-wrap gap-3">
                    @if($featuredEvent->registration_open && $featuredEvent->canRegister())
                    <a href="{{ route('event.register', $featuredEvent) }}" class="inline-flex items-center gap-2 bg-blue hover:bg-blue-dark text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                        <span class="material-symbols-outlined text-lg">how_to_reg</span> {{ __('Daftar Sekarang') }}
                    </a>
                    @elseif($featuredEvent->registration_open && $featuredEvent->isFull())
                    <span class="inline-flex items-center gap-2 bg-grey-300 text-grey-600 px-7 py-3 rounded-full font-semibold text-sm cursor-not-allowed">
                        <span class="material-symbols-outlined text-lg">group_off</span> {{ __('Kuota Penuh') }}
                    </span>
                    @endif
                    @if($featuredEvent->registration_url)
                    <a href="{{ $featuredEvent->registration_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 border-2 border-blue text-blue hover:bg-blue hover:text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                        {{ __('Link Eksternal') }} <span class="material-symbols-outlined text-lg">open_in_new</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endif

{{-- More Events --}}
@if($events->count())
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if($s = $sections['more_events'] ?? null)
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
        </div>
        @endif
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($events as $event)
            <div class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col">
                {{-- Image --}}
                @if($event->image)
                <div class="h-52 overflow-hidden relative">
                    <img alt="{{ $event->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ asset('storage/' . $event->image) }}"/>
                    @if($event->registration_open && $event->canRegister())
                    <span class="absolute top-3 right-3 bg-teal text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">{{ __('Open') }}</span>
                    @elseif($event->registration_open && $event->isFull())
                    <span class="absolute top-3 right-3 bg-red-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">{{ __('Penuh') }}</span>
                    @endif
                </div>
                @endif

                {{-- Content --}}
                <div class="p-6 flex flex-col flex-1">
                    {{-- Badges --}}
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if($event->category)
                        <span class="bg-blue/10 text-blue text-xs font-semibold px-3 py-1 rounded-full">{{ $event->category }}</span>
                        @endif
                        @if($event->location_type)
                        <span class="bg-grey-100 text-grey-600 text-xs font-medium px-3 py-1 rounded-full capitalize">{{ $event->location_type }}</span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h3 class="text-lg font-bold text-navy mb-3 group-hover:text-blue transition-colors leading-snug">{{ $event->title }}</h3>

                    {{-- Meta Info --}}
                    <div class="space-y-2 mb-4 text-sm text-grey-500">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue/60 text-base">calendar_today</span>
                            {{ $event->date->translatedFormat('d F Y') }}
                        </div>
                        @if($event->time_info)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue/60 text-base">schedule</span>
                            {{ $event->time_info }}
                        </div>
                        @endif
                        @if($event->location)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue/60 text-base">location_on</span>
                            {{ $event->location }}
                        </div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="text-grey-600 text-sm leading-relaxed line-clamp-2 mb-5">{{ $event->description }}</p>

                    {{-- Actions (pushed to bottom) --}}
                    <div class="mt-auto flex items-center gap-3 pt-4 border-t border-grey-100">
                        @if($event->registration_open && $event->canRegister())
                        <a href="{{ route('event.register', $event) }}" class="inline-flex items-center gap-1.5 bg-blue hover:bg-blue-dark text-white px-5 py-2.5 rounded-full font-semibold text-xs transition-colors">
                            <span class="material-symbols-outlined text-base">how_to_reg</span> {{ __('Daftar Sekarang') }}
                        </a>
                        @elseif($event->registration_open && $event->isFull())
                        <span class="inline-flex items-center gap-1.5 bg-grey-200 text-grey-500 px-5 py-2.5 rounded-full font-semibold text-xs cursor-not-allowed">
                            <span class="material-symbols-outlined text-base">group_off</span> {{ __('Kuota Penuh') }}
                        </span>
                        @endif
                        @if($event->registration_url)
                        <a href="{{ $event->registration_url }}" target="_blank" rel="noopener noreferrer" class="text-blue font-semibold text-sm flex items-center gap-1 hover:gap-2 transition-all">
                            {{ __('Detail') }} <span class="material-symbols-outlined text-lg">arrow_forward</span>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Upcoming Timeline --}}
@if($upcomingEvents->count())
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if($s = $sections['upcoming'] ?? null)
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
        </div>
        @endif
        <div class="max-w-3xl mx-auto space-y-6">
            @foreach($upcomingEvents as $event)
            <div class="flex gap-6 items-start">
                <div class="shrink-0 w-16 text-center">
                    <span class="block text-2xl font-bold text-navy">{{ $event->date->format('d') }}</span>
                    <span class="block text-sm text-grey-500 uppercase">{{ $event->date->translatedFormat('M') }}</span>
                </div>
                <div class="flex-1 bg-grey-50 rounded-2xl p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-2 mb-2">
                        @if($event->category)
                        <span class="bg-blue/10 text-blue text-xs font-semibold px-3 py-1 rounded-full">{{ $event->category }}</span>
                        @endif
                        @if($event->location_type)
                        <span class="bg-grey-100 text-grey-600 text-xs font-medium px-3 py-1 rounded-full capitalize">{{ $event->location_type }}</span>
                        @endif
                    </div>
                    <h3 class="font-bold text-navy mb-1">{{ $event->title }}</h3>
                    @if($event->location)
                    <p class="text-grey-500 text-sm">{{ $event->location }}</p>
                    @endif
                    @if($event->registration_open && $event->canRegister())
                    <a href="{{ route('event.register', $event) }}" class="mt-2 inline-flex items-center gap-1 text-blue font-semibold text-sm hover:gap-2 transition-all">
                        <span class="material-symbols-outlined text-base">how_to_reg</span> {{ __('Daftar') }}
                    </a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
