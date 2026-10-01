@extends('layouts.public')

@section('content')
<div class="max-w-2xl mx-auto py-16 px-4">
    <div class="bg-white rounded-xl shadow p-8 text-center">
        @if ($certificate)
            <div class="text-emerald-600 text-sm font-semibold mb-2">SERTIFIKAT VALID</div>
            <h1 class="text-2xl font-bold mb-1">{{ $certificate->participant?->user?->name ?? '-' }}</h1>
            <p class="text-gray-500 mb-6">Peserta Digital Safety Champions</p>

            <dl class="text-left grid grid-cols-1 gap-2 border-t pt-4">
                <div class="flex justify-between"><dt class="text-gray-500">Program</dt><dd class="font-medium">Digital Safety Champions</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">No. Sertifikat</dt><dd class="font-medium">{{ $certificate->number }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Diterbitkan</dt><dd class="font-medium">{{ optional($certificate->issued_at)->format('d F Y') }}</dd></div>
            </dl>

            @if ($certificate->file_path)
                <a href="{{ asset('storage/' . $certificate->file_path) }}" target="_blank"
                   class="inline-block mt-6 px-5 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium">
                    Unduh Sertifikat
                </a>
            @endif
        @endif
    </div>
</div>
@endsection
