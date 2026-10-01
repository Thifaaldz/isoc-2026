@extends('layouts.app')

@section('title', ($sections['hero']->title ?? __('Program')) . ' - ISOC Indonesia Jakarta Chapter')

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
        @if(($items['hero'] ?? collect())->count())
        <div class="mt-12 grid md:grid-cols-2 gap-6">
            @foreach($items['hero'] as $item)
            <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
                @if($item->icon)
                <div class="w-10 h-10 bg-teal/20 rounded-lg flex items-center justify-center mb-4">
                    <span class="material-symbols-outlined text-teal-light text-xl">{{ $item->icon }}</span>
                </div>
                @endif
                <h3 class="text-white font-bold text-lg mb-2">{{ $item->title }}</h3>
                <p class="text-white/60 text-sm leading-relaxed">{{ $item->description }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Global Programs --}}
@if($s = $sections['global_programs'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['global_programs'] ?? collect())->count())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($items['global_programs'] as $item)
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

{{-- Featured Program --}}
@if($s = $sections['featured_program'] ?? null)
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            @if($s->image)
            <div>
                <img class="w-full rounded-2xl shadow-xl" alt="{{ $s->title }}" src="{{ asset('storage/' . $s->image) }}"/>
            </div>
            @endif
            <div>
                <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
                <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
                <div class="text-grey-600 leading-relaxed space-y-4">
                    {!! nl2br(e($s->description)) !!}
                </div>
                @if($s->button_text)
                <a href="{{ $s->button_url ?? '#' }}" class="mt-6 inline-flex items-center gap-2 bg-blue hover:bg-blue-light text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                    {{ $s->button_text }} <span class="material-symbols-outlined text-lg">arrow_forward</span>
                </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- Local Programs --}}
@if($s = $sections['local_programs'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['local_programs'] ?? collect())->count())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($items['local_programs'] as $item)
            <div class="bg-grey-50 rounded-2xl p-8 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-{{ $item->icon_color ?? 'teal' }}/10 rounded-xl flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-{{ $item->icon_color ?? 'teal' }} text-2xl">{{ $item->icon }}</span>
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

{{-- Collaboration --}}
@if($s = $sections['collaboration'] ?? null)
<section class="bg-navy py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue-light font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">{{ $s->title }}</h2>
            <p class="text-white/70 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['collaboration'] ?? collect())->count())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($items['collaboration'] as $item)
            <div class="bg-white/5 border border-white/10 rounded-2xl p-8">
                <div class="w-12 h-12 bg-teal/20 rounded-xl flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-teal-light text-2xl">{{ $item->icon }}</span>
                </div>
                <h3 class="text-lg font-bold text-white mb-3">{{ $item->title }}</h3>
                <p class="text-white/60 text-sm leading-relaxed">{{ $item->description }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Impact Multipliers --}}
@if($s = $sections['impact_multipliers'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['impact_multipliers'] ?? collect())->count())
        <div class="grid md:grid-cols-2 gap-8">
            @foreach($items['impact_multipliers'] as $item)
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
@endsection
