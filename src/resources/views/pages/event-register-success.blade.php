@extends('layouts.app')

@section('title', __('Pendaftaran Berhasil') . ' - ' . $event->title)

@section('content')
{{-- Toast Notification --}}
<div x-data="{ show: true, copied: false }" x-show="show" x-init="setTimeout(() => show = false, 12000)"
     x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-[-100%] opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-[-100%] opacity-0"
     class="fixed top-20 left-1/2 -translate-x-1/2 z-50 w-full max-w-md">
    <div class="bg-white border border-teal/20 shadow-2xl rounded-2xl p-5 flex items-start gap-4">
        <div class="w-10 h-10 bg-teal/10 rounded-full flex items-center justify-center flex-shrink-0">
            <span class="material-symbols-outlined text-teal">check_circle</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-navy text-sm">{{ __('Pendaftaran Berhasil!') }}</p>
            <p class="text-grey-500 text-xs mt-1">{{ __('Kode registrasi Anda:') }}</p>
            <div class="flex items-center gap-2 mt-1">
                <p class="font-mono font-bold text-blue text-lg">{{ $registration->registration_code }}</p>
                <button @click="navigator.clipboard.writeText('{{ $registration->registration_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                    :class="copied ? 'bg-teal/10 text-teal' : 'bg-grey-100 text-grey-500 hover:bg-blue/10 hover:text-blue'">
                    <span class="material-symbols-outlined text-sm" x-text="copied ? 'check' : 'content_copy'"></span>
                    <span x-text="copied ? '{{ __('Tersalin!') }}' : '{{ __('Copy') }}'"></span>
                </button>
            </div>
            <p class="text-grey-400 text-[11px] mt-1">{{ __('Simpan kode ini untuk login ke Portal Peserta') }}</p>
        </div>
        <button @click="show = false" class="text-grey-400 hover:text-grey-600 flex-shrink-0">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>
</div>

<section class="py-20 lg:py-28">
    <div class="max-w-2xl mx-auto px-6 lg:px-8 text-center">
        {{-- Success Icon --}}
        <div class="w-20 h-20 bg-teal/10 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-teal text-4xl">check_circle</span>
        </div>

        <h1 class="text-3xl md:text-4xl font-bold text-navy mb-4">{{ __('Pendaftaran Berhasil!') }}</h1>
        <p class="text-grey-600 text-lg mb-8">{{ __('Terima kasih telah mendaftar untuk event ini.') }}</p>

        {{-- Registration Details Card --}}
        <div class="bg-grey-50 rounded-2xl p-8 text-left mb-8">
            <h3 class="font-bold text-navy mb-4">{{ __('Detail Pendaftaran') }}</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-grey-200" x-data="{ copied: false }">
                    <span class="text-grey-500">{{ __('Kode Registrasi') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-navy bg-blue/10 px-3 py-1 rounded-lg font-mono">{{ $registration->registration_code }}</span>
                        <button @click="navigator.clipboard.writeText('{{ $registration->registration_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold transition-colors"
                            :class="copied ? 'bg-teal/10 text-teal' : 'bg-grey-100 text-grey-500 hover:bg-blue/10 hover:text-blue'">
                            <span class="material-symbols-outlined text-sm" x-text="copied ? 'check' : 'content_copy'"></span>
                        </button>
                    </div>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-grey-200">
                    <span class="text-grey-500">{{ __('Event') }}</span>
                    <span class="font-medium text-navy">{{ $event->title }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-grey-200">
                    <span class="text-grey-500">{{ __('Nama') }}</span>
                    <span class="font-medium text-navy">{{ $registration->name }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-grey-200">
                    <span class="text-grey-500">{{ __('Email') }}</span>
                    <span class="font-medium text-navy">{{ $registration->email }}</span>
                </div>
                @if($event->date)
                <div class="flex justify-between items-center py-2 border-b border-grey-200">
                    <span class="text-grey-500">{{ __('Tanggal Event') }}</span>
                    <span class="font-medium text-navy">{{ $event->date->translatedFormat('d F Y') }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-2">
                    <span class="text-grey-500">{{ __('Status') }}</span>
                    <span class="inline-flex items-center gap-1 text-teal font-semibold">
                        <span class="material-symbols-outlined text-sm">pending</span>
                        {{ __('Menunggu Konfirmasi') }}
                    </span>
                </div>
            </div>
        </div>

        <p class="text-grey-500 text-sm mb-8">{{ __('Simpan kode registrasi Anda. Gunakan email dan kode ini untuk masuk ke Portal Peserta.') }}</p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('participant.login') }}" class="inline-flex items-center justify-center gap-2 bg-blue hover:bg-blue-dark text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                <span class="material-symbols-outlined text-lg">person</span>
                {{ __('Masuk Portal Peserta') }}
            </a>
            <a href="{{ route('events') }}" class="inline-flex items-center justify-center gap-2 border-2 border-blue text-blue hover:bg-blue hover:text-white px-7 py-3 rounded-full font-semibold text-sm transition-colors">
                <span class="material-symbols-outlined text-lg">arrow_back</span>
                {{ __('Kembali ke Events') }}
            </a>
        </div>
    </div>
</section>
@endsection
