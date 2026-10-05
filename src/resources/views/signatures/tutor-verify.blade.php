@extends('layouts.app')

@section('title', __('Verifikasi Tanda Tangan Tutor') . ' - ' . config('app.name', 'sena'))

@section('content')
<section class="py-16 lg:py-24 bg-grey-50">
    <div class="max-w-2xl mx-auto px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg border border-grey-100 p-8 text-center">
            @if ($valid)
                <span class="material-symbols-outlined text-5xl text-emerald-600">verified</span>
                <div class="text-emerald-600 text-sm font-semibold tracking-wide mt-2 mb-2">{{ __('TANDA TANGAN ELEKTRONIK SAH') }}</div>
                <h1 class="text-2xl font-bold text-navy mb-1">{{ $tutor->user?->name ?? '-' }}</h1>
                <p class="text-grey-500 mb-6">{{ __('Tutor') }} {{ $event->title }}</p>

                <dl class="text-left grid grid-cols-1 gap-3 border-t border-grey-100 pt-5 text-sm">
                    <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Peran') }}</dt><dd class="font-medium text-right">{{ __('Tutor / Fasilitator') }}</dd></div>
                    @if ($tutor->institution)
                        <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Instansi') }}</dt><dd class="font-medium text-right">{{ $tutor->institution }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Kegiatan') }}</dt><dd class="font-medium text-right">{{ $event->title }}</dd></div>
                    @if ($event->school?->name)
                        <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Lokasi') }}</dt><dd class="font-medium text-right">{{ $event->school->name }}</dd></div>
                    @endif
                    @if ($event->starts_at)
                        <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Tanggal kegiatan') }}</dt><dd class="font-medium text-right">{{ $event->starts_at->translatedFormat('d F Y') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-6"><dt class="text-grey-500">{{ __('Status ToT') }}</dt><dd class="font-medium text-right">{{ $tutor->tot_completed ? __('Lulus ToT') : __('Belum ToT') }}</dd></div>
                </dl>
                <p class="text-grey-500 text-xs mt-6">{{ __('Tanda tangan ini diterbitkan sistem pada Laporan Kegiatan dan hanya sah untuk kegiatan di atas.') }}</p>
            @else
                <span class="material-symbols-outlined text-5xl text-red-500">gpp_bad</span>
                <div class="text-red-600 text-sm font-semibold tracking-wide mt-2 mb-2">{{ __('TANDA TANGAN TIDAK VALID') }}</div>
                <p class="text-grey-600">{{ __('Kode QR tidak dikenali, sudah diubah, atau tutor tidak lagi terdaftar pada kegiatan ini.') }}</p>
                <p class="text-grey-500 text-sm mt-3">{{ __('Pindai ulang kode QR pada laporan asli atau hubungi penyelenggara.') }}</p>
            @endif
        </div>
    </div>
</section>
@endsection
