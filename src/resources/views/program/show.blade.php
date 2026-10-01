@extends('layouts.public')

@section('title', 'Digital Safety Champions')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <a href="{{ route('participants.register') }}" class="text-sm text-indigo-600">&larr; Kembali ke pendaftaran</a>

    <h1 class="text-3xl font-bold mt-4 mb-2">Digital Safety Champions</h1>
    <p class="text-gray-500 mb-6">Program literasi keamanan digital ISOC Indonesia Chapter Jakarta.</p>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="font-semibold mb-2">Deskripsi</h2>
        <p class="text-gray-700 whitespace-pre-line">Digital Safety Champions membantu peserta memahami ancaman daring, menjaga perangkat dan akun, mengenali hoaks, menangani insiden digital, serta menyiapkan praktik microsite dan e-Certificate.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Jadwal Sekolah</h2>
            @forelse ($schools as $school)
                <p class="text-gray-700 text-sm">{{ $school->name }} - {{ $school->training_date ? \Illuminate\Support\Carbon::parse($school->training_date)->format('d F Y') : 'Jadwal menyusul' }}</p>
            @empty
                <p class="text-gray-400 text-sm">Belum ada sekolah aktif.</p>
            @endforelse
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Modul</h2>
            @forelse ($modules as $module)
                <p class="text-gray-700 text-sm">{{ $module->number }}. {{ $module->title }}</p>
            @empty
                <p class="text-gray-400 text-sm">Belum ada modul.</p>
            @endforelse
        </div>
    </div>

    <a href="{{ route('participants.register') }}#form-pendaftaran" class="inline-block px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium">
        Daftar Sebagai Peserta
    </a>
</div>
@endsection
