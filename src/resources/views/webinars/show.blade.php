@extends('layouts.public')

@section('title', $webinar->title)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <a href="{{ route('webinars.index') }}" class="text-sm text-indigo-600">&larr; Kembali ke daftar webinar</a>

    <h1 class="text-3xl font-bold mt-4 mb-2">{{ $webinar->title }}</h1>
    <p class="text-gray-500 mb-6">Oleh {{ $webinar->tutor?->name ?? 'Tutor ISOC' }}</p>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="font-semibold mb-2">Deskripsi</h2>
        <p class="text-gray-700 whitespace-pre-line">{{ $webinar->description }}</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Jadwal</h2>
            <p class="text-gray-700">Mulai: {{ optional($webinar->start_at)->format('d F Y, H:i') ?? '-' }}</p>
            <p class="text-gray-700">Selesai: {{ optional($webinar->end_at)->format('d F Y, H:i') ?? '-' }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Sesi</h2>
            @forelse ($webinar->sessions as $session)
                <p class="text-gray-700 text-sm">{{ $session->session_number }}. {{ $session->title }} - {{ optional($session->start_at)->format('d M Y H:i') }}</p>
            @empty
                <p class="text-gray-400 text-sm">Belum ada sesi.</p>
            @endforelse
        </div>
    </div>

    @if ($webinar->mitraUtama || $webinar->participatingMitras->isNotEmpty())
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="font-semibold mb-3">Didukung Oleh</h2>
            <div class="flex flex-wrap gap-6 items-center">
                @if ($webinar->mitraUtama?->logo_path)
                    <img src="{{ asset('storage/' . $webinar->mitraUtama->logo_path) }}" class="h-10" alt="{{ $webinar->mitraUtama->name }}">
                @endif
                @foreach ($webinar->participatingMitras as $mitra)
                    @if ($mitra->logo_path)
                        <img src="{{ asset('storage/' . $mitra->logo_path) }}" class="h-10" alt="{{ $mitra->name }}">
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ url('/student/register') }}" class="inline-block px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium">
        Daftar Sebagai Student
    </a>
</div>
@endsection
