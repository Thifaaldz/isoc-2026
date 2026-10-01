@extends('layouts.app')

@section('title', ($sections['hero']->title ?? __('Tentang Kami')) . ' - ISOC Indonesia Jakarta Chapter')

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

{{-- History --}}
@if($s = $sections['history'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
                <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
                <div class="prose prose-grey max-w-none text-grey-600 leading-relaxed space-y-4">
                    {!! nl2br(e($s->description)) !!}
                </div>
            </div>
            @if($s->image)
            <div>
                <img class="w-full rounded-2xl shadow-xl" alt="{{ $s->title }}" src="{{ asset('storage/' . $s->image) }}"/>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- Community --}}
@if($s = $sections['community'] ?? null)
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['community'] ?? collect())->count())
        <div class="grid md:grid-cols-3 gap-8">
            @foreach($items['community'] as $item)
            <div class="bg-white rounded-2xl p-8 hover:shadow-lg transition-shadow">
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

{{-- Ecosystem --}}
@if($s = $sections['ecosystem'] ?? null)
<section class="py-20 lg:py-28">
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
            </div>
        </div>
    </div>
</section>
@endif

{{-- Focus Areas --}}
@if($s = $sections['focus_areas'] ?? null)
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['focus_areas'] ?? collect())->count())
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($items['focus_areas'] as $item)
            <div class="bg-white rounded-2xl p-8 hover:shadow-lg transition-shadow">
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

{{-- Vision & Mission --}}
@if($s = $sections['vision_mission'] ?? null)
<section class="bg-navy py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue-light font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">{{ $s->title }}</h2>
            <p class="text-white/70 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['vision_mission'] ?? collect())->count())
        <div class="grid md:grid-cols-2 gap-8">
            @foreach($items['vision_mission'] as $item)
            <div class="bg-white/5 border border-white/10 rounded-2xl p-8">
                <div class="w-12 h-12 bg-teal/20 rounded-xl flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-teal-light text-2xl">{{ $item->icon }}</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">{{ $item->title }}</h3>
                <p class="text-white/60 leading-relaxed">{{ $item->description }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Resources --}}
@if($s = $sections['resources'] ?? null)
<section class="py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @if(($items['resources'] ?? collect())->count())
        <div class="grid md:grid-cols-3 gap-8">
            @foreach($items['resources'] as $item)
            <a href="{{ $item->url ?? '#' }}" class="bg-grey-50 rounded-2xl p-8 hover:shadow-lg transition-shadow group block">
                <div class="w-12 h-12 bg-{{ $item->icon_color ?? 'blue' }}/10 rounded-xl flex items-center justify-center mb-5">
                    <span class="material-symbols-outlined text-{{ $item->icon_color ?? 'blue' }} text-2xl">{{ $item->icon }}</span>
                </div>
                <h3 class="text-lg font-bold text-navy mb-3 group-hover:text-blue transition-colors">{{ $item->title }}</h3>
                <p class="text-grey-600 text-sm leading-relaxed">{{ $item->description }}</p>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- Team --}}
@if($teamMembers->count())
<section class="bg-grey-50 py-20 lg:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if($s = $sections['team'] ?? null)
        <div class="max-w-3xl mx-auto text-center mb-16">
            <p class="text-blue font-semibold text-sm uppercase tracking-wider mb-3">{{ $s->subtitle }}</p>
            <h2 class="text-3xl md:text-4xl font-bold text-navy mb-6">{{ $s->title }}</h2>
            <p class="text-grey-600 text-lg leading-relaxed">{{ $s->description }}</p>
        </div>
        @endif
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
            @foreach($teamMembers as $member)
            <div class="text-center group">
                <div class="w-32 h-32 mx-auto rounded-full overflow-hidden bg-grey-200 mb-4 shadow-md">
                    @if($member->photo)
                    <img class="w-full h-full object-cover" alt="{{ $member->name }}" src="{{ asset('storage/' . $member->photo) }}"/>
                    @else
                    <div class="w-full h-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-grey-400 text-4xl">person</span>
                    </div>
                    @endif
                </div>
                <h4 class="font-bold text-navy text-sm">{{ $member->name }}</h4>
                <p class="text-grey-500 text-xs mt-1">{{ $member->position }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
