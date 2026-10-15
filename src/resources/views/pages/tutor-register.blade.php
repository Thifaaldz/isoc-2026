@extends('layouts.app')

@section('title', __('Registrasi Tutor'))

@section('content')
@php
    $input = 'w-full px-4 py-2.5 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm';
    $label = 'block text-sm font-semibold text-navy mb-1.5';
@endphp
<section class="bg-navy relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-10 lg:py-12">
        <a href="{{ route('events') }}" class="inline-flex items-center gap-1 text-white/60 hover:text-white text-sm mb-3 transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span> {{ __('Kembali ke Events') }}
        </a>
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
            <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">{{ __('Registrasi Tutor') }}</h1>
            <p class="text-white/70 text-sm max-w-xl">{{ __('Pilih event yang ingin kamu dampingi sebagai tutor/fasilitator. Pendaftaran divalidasi Admin RTIK Pusat sebelum akun tutor aktif.') }}</p>
        </div>
    </div>
</section>

<section class="py-10 lg:py-14">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 space-y-6">
        @if(session('tutor_registered'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-start gap-3">
            <span class="material-symbols-outlined text-xl">check_circle</span>
            <p>{{ session('tutor_registered') }}</p>
        </div>
        @endif

        @if($viewerMode === 'other')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            <p class="font-semibold">{{ __('Kamu sedang login bukan sebagai tutor.') }}</p>
            <p class="mt-1">{{ __('Logout terlebih dahulu untuk mendaftar sebagai tutor baru.') }}</p>
        </div>
        @elseif($events->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            <p class="font-semibold">{{ __('Belum ada event yang membuka pendaftaran tutor.') }}</p>
            <p class="mt-1">{{ __('Silakan cek kembali halaman ini nanti.') }}</p>
        </div>
        @else
        <form action="{{ $viewerMode === 'tutor' ? route('tutors.register.join') : route('tutors.register.store') }}" method="POST" class="grid lg:grid-cols-12 gap-6 items-start">
            @csrf

            {{-- Kiri: pilih event --}}
            <div class="lg:col-span-5 bg-white rounded-2xl shadow-lg border border-grey-100 overflow-hidden lg:sticky lg:top-24">
                <div class="bg-grey-50 px-6 py-4 border-b border-grey-100">
                    <h2 class="text-base font-bold text-navy flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue text-white text-xs">1</span>
                        {{ __('Pilih event') }} <span class="text-red-500">*</span>
                    </h2>
                </div>
                <fieldset class="p-4 space-y-2 lg:max-h-[32rem] overflow-y-auto">
                    <legend class="sr-only">{{ __('Event yang didaftarkan') }}</legend>
                    @foreach($events as $event)
                    @php $closed = $closedReasons[$event->id] ?? null; @endphp
                    <label class="flex items-start gap-3 rounded-xl border p-3 text-sm transition {{ $closed ? 'border-grey-200 bg-grey-50 cursor-not-allowed' : 'border-grey-200 hover:border-blue cursor-pointer has-[:checked]:border-blue has-[:checked]:bg-blue/5' }}">
                        <input type="radio" name="learning_event_id" value="{{ $event->id }}" required class="mt-1 text-blue focus:ring-blue" @checked($selectedEventId === $event->id && ! $closed) @disabled($closed)>
                        <span class="min-w-0">
                            <span class="block font-semibold {{ $closed ? 'text-grey-400' : 'text-navy' }}">{{ $event->title }}</span>
                            <span class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-grey-500">
                                @if($event->starts_at)
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">calendar_today</span> {{ $event->starts_at->translatedFormat('d M Y') }}</span>
                                @endif
                                @if($event->school)
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">location_on</span> {{ $event->school->name }}</span>
                                @endif
                                <span class="inline-flex items-center gap-1"><span class="material-symbols-outlined text-sm">groups</span> {{ $event->tutors_count }}/{{ $event->target_tutors ?: '-' }} {{ __('tutor') }}</span>
                            </span>
                            @if($closed)
                            <span class="mt-1 block text-xs text-amber-700">{{ $closed }}</span>
                            @endif
                        </span>
                    </label>
                    @endforeach
                </fieldset>
            </div>

            {{-- Kanan: data tutor --}}
            <div class="lg:col-span-7 bg-white rounded-2xl shadow-lg border border-grey-100 overflow-hidden">
                <div class="bg-grey-50 px-6 py-4 border-b border-grey-100">
                    <h2 class="text-base font-bold text-navy flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue text-white text-xs">2</span>
                        {{ $viewerMode === 'guest' ? __('Data tutor') : __('Konfirmasi pendaftaran') }}
                    </h2>
                </div>

                <div class="p-6 space-y-5">
                    @if($viewerMode === 'guest')
                    <div class="rounded-xl border border-blue/20 bg-blue/5 px-4 py-3 text-sm text-navy">
                        {{ __('Sudah punya akun tutor?') }}
                        <a class="font-semibold text-blue hover:underline" href="{{ route('login') }}">{{ __('Login') }}</a>
                        {{ __('lalu buka kembali halaman ini untuk mendaftar ke event lain.') }}
                    </div>
                    @else
                    <div class="rounded-xl border border-blue/20 bg-blue/5 px-4 py-3 text-sm text-navy">
                        {{ __('Masuk sebagai') }} <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
                        {{ __('Data profil tutor kamu akan dipakai untuk event ini.') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if($viewerMode === 'guest')
                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="{{ $label }}" for="name">{{ __('Nama Lengkap') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="name" name="name" required type="text" value="{{ old('name') }}" placeholder="{{ __('Nama sesuai identitas') }}"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="email">{{ __('Email') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="email" name="email" required type="email" value="{{ old('email') }}" placeholder="nama@email.com"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="phone">{{ __('No. WhatsApp') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="phone" name="phone" required type="text" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="institution">{{ __('Lembaga / Instansi') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="institution" name="institution" required type="text" value="{{ old('institution') }}" placeholder="{{ __('Contoh: RTIK Kota Bekasi') }}"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="nik">{{ __('NIK') }}</label>
                            <input class="{{ $input }}" id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" value="{{ old('nik') }}" placeholder="{{ __('16 digit (opsional)') }}"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="password">{{ __('Password Panel Tutor') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="password" name="password" required type="password" placeholder="{{ __('Minimal 8 karakter') }}"/>
                        </div>
                        <div>
                            <label class="{{ $label }}" for="password_confirmation">{{ __('Konfirmasi Password') }} <span class="text-red-500">*</span></label>
                            <input class="{{ $input }}" id="password_confirmation" name="password_confirmation" required type="password" placeholder="{{ __('Ulangi password') }}"/>
                        </div>
                    </div>

                    <details class="rounded-xl border border-grey-200 px-4 py-3 group" @if(old('npwp') || old('bank_name') || old('bank_account_number')) open @endif>
                        <summary class="cursor-pointer text-sm font-semibold text-navy flex items-center justify-between">
                            {{ __('Data honor (opsional, bisa dilengkapi nanti di profil)') }}
                            <span class="material-symbols-outlined text-lg transition group-open:rotate-180">expand_more</span>
                        </summary>
                        <div class="grid md:grid-cols-3 gap-3 mt-3">
                            <input class="{{ $input }}" name="npwp" type="text" value="{{ old('npwp') }}" placeholder="{{ __('NPWP') }}" aria-label="NPWP"/>
                            <input class="{{ $input }}" name="bank_name" type="text" value="{{ old('bank_name') }}" placeholder="{{ __('Nama bank') }}" aria-label="{{ __('Nama bank') }}"/>
                            <input class="{{ $input }}" name="bank_account_number" type="text" value="{{ old('bank_account_number') }}" placeholder="{{ __('No. rekening') }}" aria-label="{{ __('No. rekening') }}"/>
                        </div>
                    </details>
                    @endif

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-blue/20 bg-blue/5 p-4 text-xs text-grey-600">
                            <p class="font-bold text-navy flex items-center gap-1.5 text-sm">
                                <span class="material-symbols-outlined text-blue text-lg">verified_user</span>
                                {{ __('Validasi Admin RTIK Pusat') }}
                            </p>
                            <p class="mt-1">{{ __('Setelah disetujui, akun tutor aktif dan kamu ditugaskan ke event. Selanjutnya selesaikan ToT Awal di panel tutor.') }}</p>
                        </div>
                        <label class="flex items-start gap-3 rounded-xl border border-grey-200 p-4 text-xs text-grey-600">
                            <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-grey-300 text-blue focus:ring-blue">
                            <span>{{ __('Saya menyetujui pemrosesan data pendaftaran dan bersedia mengikuti ketentuan kegiatan ISOC sebagai tutor.') }}</span>
                        </label>
                    </div>

                    <button class="w-full bg-blue hover:bg-blue-dark text-white py-3 px-8 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2" type="submit">
                        <span class="material-symbols-outlined text-lg">how_to_reg</span>
                        {{ __('Daftar sebagai Tutor') }}
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
</section>
@endsection
