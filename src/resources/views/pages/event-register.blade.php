@extends('layouts.app')

@section('title', __('Daftar Event') . ' - ' . $event->title)

@section('content')
@php
    $isGeneralEvent = ($event->audience_type ?? 'school') === 'general';
    $defaultParticipantCategory = old('participant_category', $isGeneralEvent ? 'umum' : 'pelajar');
@endphp
<section class="bg-navy relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
        <div class="max-w-3xl">
            <a href="{{ route('events') }}" class="inline-flex items-center gap-1 text-white/60 hover:text-white text-sm mb-4 transition-colors">
                <span class="material-symbols-outlined text-lg">arrow_back</span> {{ __('Kembali ke Events') }}
            </a>
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white leading-[1.1] mb-4">{{ $event->title }}</h1>
            <div class="flex flex-wrap gap-4 text-sm text-white/70">
                @if($event->date)
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-blue-light text-lg">calendar_today</span> {{ $event->date->translatedFormat('d F Y') }}</span>
                @endif
                @if($event->time_info)
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-blue-light text-lg">schedule</span> {{ $event->time_info }}</span>
                @endif
                @if($event->location)
                <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-blue-light text-lg">location_on</span> {{ $event->location }}</span>
                @endif
            </div>
        </div>
    </div>
</section>

<section class="py-16 lg:py-24">
    <div class="max-w-3xl mx-auto px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-lg border border-grey-100 overflow-hidden">
            <div class="bg-grey-50 px-8 py-6 border-b border-grey-100">
                <h2 class="text-xl font-bold text-navy">{{ __('Formulir Pendaftaran') }}</h2>
                <p class="text-grey-600 text-sm mt-1">{{ __('Isi data berikut untuk mendaftar event ini.') }}</p>
                @if($event->max_participants)
                <div class="mt-3 flex items-center gap-2">
                    <div class="flex-1 bg-grey-200 rounded-full h-2 overflow-hidden">
                        <div class="bg-teal h-full rounded-full transition-all" style="width: {{ min(100, ($registrationCount / $event->max_participants) * 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-grey-500">{{ $registrationCount }}/{{ $event->max_participants }} {{ __('peserta') }}</span>
                </div>
                @endif
            </div>

@php
    $closedMessage = match ($event->registrationStatus()) {
        'past' => __('Pendaftaran ditutup karena tanggal event sudah lewat.'),
        'full' => __('Kuota peserta event ini sudah penuh.'),
        default => __('Pendaftaran event ini belum dibuka atau sudah ditutup.'),
    };
@endphp
@if($viewerMode === 'guest' && ! $event->canRegister())
            <div class="p-8">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 flex items-start gap-3">
                    <span class="material-symbols-outlined text-xl">{{ $event->registrationStatusIcon() }}</span>
                    <div>
                        <p class="font-semibold">{{ __($event->registrationStatusLabel()) }}</p>
                        <p class="mt-1">{{ $closedMessage }}</p>
                    </div>
                </div>
            </div>
@elseif($viewerMode === 'guest')
            <form
                action="{{ route('event.register.store', $event) }}"
                method="POST"
                enctype="multipart/form-data"
                class="p-8 space-y-6"
                x-data="{
                    category: @js($defaultParticipantCategory),
                    identityLabel() {
                        return { pelajar: 'NISN', mahasiswa: 'NIM' }[this.category] || 'Nomor Identitas';
                    },
                    identityPlaceholder() {
                        return { pelajar: 'Nomor Induk Siswa Nasional', mahasiswa: 'Nomor Induk Mahasiswa' }[this.category] || 'Nomor Identitas';
                    },
                    needsIdentity() {
                        return ['pelajar', 'mahasiswa'].includes(this.category);
                    },
                    requiresSchool() {
                        return this.category === 'pelajar';
                    },
                    needsOrganization() {
                        return true;
                    },
                    organizationLabel() {
                        return this.category === 'pelajar' ? 'Nama Sekolah' : 'Organisasi / Instansi';
                    },
                    organizationPlaceholder() {
                        return this.category === 'pelajar' ? 'Contoh: SMK Negeri 1 Jakarta' : 'Opsional';
                    },
                }"
            >
                @csrf

                <div class="rounded-xl border border-blue/20 bg-blue/5 p-4 text-sm text-navy">
                    {{ __('Sudah punya akun peserta?') }}
                    <a class="font-semibold text-blue hover:underline" href="{{ route('event.register.login', $event) }}">{{ __('Login untuk mengikuti event ini') }}</a>
                    {{ __('tanpa membuat akun baru.') }}
                </div>

                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-navy mb-2" for="name">{{ __('Nama Lengkap') }} <span class="text-red-500">*</span></label>
                    <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="name" name="name" required type="text" value="{{ old('name') }}" placeholder="{{ __('Masukkan nama lengkap Anda') }}"/>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="email">{{ __('Email') }} <span class="text-red-500">*</span></label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="email" name="email" required type="email" value="{{ old('email') }}" placeholder="{{ __('contoh@email.com') }}"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="phone">{{ __('Nomor Telepon') }} <span class="text-red-500">*</span></label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="phone" name="phone" required type="tel" value="{{ old('phone') }}" placeholder="{{ __('08xxxxxxxxxx') }}"/>
                    </div>
                </div>

                <div class="rounded-2xl border border-grey-200 bg-grey-50 p-5">
                    <label class="block text-sm font-semibold text-navy mb-2" for="participant_category">{{ __('Kategori Peserta') }} <span class="text-red-500">*</span></label>
                    <select
                        class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm bg-white"
                        id="participant_category"
                        name="participant_category"
                        required
                        x-model="category"
                    >
                        <option value="pelajar">{{ __('Pelajar') }}</option>
                        <option value="mahasiswa">{{ __('Mahasiswa') }}</option>
                        <option value="umum">{{ __('Umum') }}</option>
                        <option value="karyawan">{{ __('Karyawan') }}</option>
                    </select>
                    <p class="text-grey-500 text-xs mt-2">{{ __('Semua kategori dapat mengisi NIK. Pelajar juga dapat mengisi NISN, Mahasiswa juga dapat mengisi NIM.') }}</p>
                    @error('participant_category')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="nik">{{ __('NIK') }}</label>
                        <input
                            class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm"
                            id="nik"
                            name="nik"
                            type="text"
                            value="{{ old('nik') }}"
                            placeholder="{{ __('Nomor Induk Kependudukan') }}"
                        />
                        @error('nik')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="needsIdentity()" x-cloak>
                        <label class="block text-sm font-semibold text-navy mb-2" for="nis"><span x-text="identityLabel()"></span></label>
                        <input
                            class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm"
                            id="nis"
                            name="nis"
                            type="text"
                            value="{{ old('nis') }}"
                            :placeholder="identityPlaceholder()"
                            :disabled="! needsIdentity()"
                        />
                        @error('nis')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div x-show="requiresSchool()" x-cloak>
                        <label class="block text-sm font-semibold text-navy mb-2" for="grade">{{ __('Kelas') }} <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="grade" name="grade" :required="requiresSchool()">
                            <option value="">{{ __('Pilih') }}</option>
                            @foreach($gradeOptions as $grade => $gradeLabel)
                            <option value="{{ $grade }}" @selected(old('grade') === $grade)>{{ $gradeLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="needsOrganization()" x-cloak>
                        <label class="block text-sm font-semibold text-navy mb-2" for="organization"><span x-text="organizationLabel()"></span> <span x-show="requiresSchool()" class="text-red-500">*</span></label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="organization" name="organization" type="text" value="{{ old('organization') }}" :required="requiresSchool()" :placeholder="organizationPlaceholder()"/>
                    </div>
                    <div x-show="needsOrganization()" x-cloak>
                        <label class="block text-sm font-semibold text-navy mb-2" for="position">{{ __('Jabatan / Peran') }}</label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="position" name="position" type="text" value="{{ old('position') }}" placeholder="{{ __('Opsional') }}"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="gender">{{ __('Jenis Kelamin') }} <span class="text-red-500">*</span></label>
                        <select class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="gender" name="gender" required>
                            <option value="">{{ __('Pilih') }}</option>
                            <option value="L" @selected(old('gender') === 'L')>{{ __('Laki-laki') }}</option>
                            <option value="P" @selected(old('gender') === 'P')>{{ __('Perempuan') }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-navy mb-2" for="birth_date">{{ __('Tanggal Lahir') }}</label>
                    <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}"/>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="password">{{ __('Password Panel Peserta') }} <span class="text-red-500">*</span></label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="password" name="password" required type="password" placeholder="{{ __('Minimal 8 karakter') }}"/>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-navy mb-2" for="password_confirmation">{{ __('Konfirmasi Password') }} <span class="text-red-500">*</span></label>
                        <input class="w-full px-4 py-3 rounded-xl border border-grey-200 focus:border-blue focus:ring-2 focus:ring-blue/20 outline-none transition text-sm" id="password_confirmation" name="password_confirmation" required type="password" placeholder="{{ __('Ulangi password') }}"/>
                    </div>
                </div>

                <div class="rounded-2xl border border-blue/20 bg-blue/5 p-5 text-sm text-grey-600">
                    <h3 class="font-bold text-navy flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-blue text-lg">verified_user</span>
                        {{ __('Bukti Dukung Peserta') }}
                    </h3>
                    <p class="text-xs mt-1">{{ __('Setelah terdaftar, lengkapi bukti follow Instagram ISOC dan join WhatsApp Group kegiatan di Dashboard peserta. Modul, tes, dan rundown terbuka setelah bukti dukung lengkap.') }}</p>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-grey-200 p-4 text-sm text-grey-600">
                    <input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-grey-300 text-blue focus:ring-blue">
                    <span>{{ __('Saya menyetujui pemrosesan data pendaftaran dan bersedia mengikuti ketentuan kegiatan ISOC.') }}</span>
                </label>

                <div class="pt-2">
                    <button class="w-full bg-blue hover:bg-blue-dark text-white py-3.5 px-8 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2" type="submit">
                        <span class="material-symbols-outlined text-lg">how_to_reg</span>
                        {{ __('Daftar Sekarang') }}
                    </button>
                </div>
            </form>
            @elseif($viewerMode === 'peserta')
            <div class="p-8 space-y-6">
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($alreadyJoined)
                    <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-800">
                        <p class="font-semibold">{{ __('Kamu sudah terdaftar di event ini.') }}</p>
                        <a class="mt-3 inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-green-700" href="{{ url('/peserta?event=' . $learningEvent?->id) }}">
                            <span class="material-symbols-outlined text-base">dashboard</span>
                            {{ __('Buka Dashboard Peserta') }}
                        </a>
                    </div>
                @elseif(! $learningEvent || ! $event->canRegister())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                        <p class="font-semibold">{{ $closedMessage }}</p>
                    </div>
                @else
                    <form action="{{ route('event.join', $event) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div class="rounded-xl border border-grey-200 bg-grey-50 p-4 text-sm text-grey-700">
                            {{ __('Masuk sebagai') }} <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).
                            {{ __('Data profil peserta kamu akan dipakai untuk event ini.') }}
                        </div>
                <div class="rounded-2xl border border-blue/20 bg-blue/5 p-5 text-sm text-grey-600">
                    <h3 class="font-bold text-navy flex items-center gap-2 text-sm">
                        <span class="material-symbols-outlined text-blue text-lg">verified_user</span>
                        {{ __('Bukti Dukung Peserta') }}
                    </h3>
                    <p class="text-xs mt-1">{{ __('Setelah terdaftar, lengkapi bukti follow Instagram ISOC dan join WhatsApp Group kegiatan di Dashboard peserta. Modul, tes, dan rundown terbuka setelah bukti dukung lengkap.') }}</p>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-grey-200 p-4 text-sm text-grey-600">
                    <input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-grey-300 text-blue focus:ring-blue">
                    <span>{{ __('Saya menyetujui pemrosesan data pendaftaran dan bersedia mengikuti ketentuan kegiatan ISOC.') }}</span>
                </label>

                        <div class="pt-2">
                            <button class="w-full bg-blue hover:bg-blue-dark text-white py-3.5 px-8 rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2" type="submit">
                                <span class="material-symbols-outlined text-lg">how_to_reg</span>
                                {{ __('Ikuti Event') }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
            @else
            <div class="p-8">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    <p class="font-semibold">{{ __('Kamu sedang login sebagai admin/tutor.') }}</p>
                    <p class="mt-1">{{ __('Pendaftaran event hanya untuk akun peserta. Logout terlebih dahulu untuk mendaftarkan peserta baru.') }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="mt-8 bg-grey-50 rounded-2xl p-8">
            <h3 class="font-bold text-navy mb-4">{{ __('Detail Event') }}</h3>
            <div class="space-y-3 text-sm text-grey-600">
                @if($event->date)
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue text-lg mt-0.5">calendar_today</span>
                    <div>
                        <p class="font-medium text-navy">{{ __('Tanggal') }}</p>
                        <p>{{ $event->date->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                @endif
                @if($event->time_info)
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue text-lg mt-0.5">schedule</span>
                    <div>
                        <p class="font-medium text-navy">{{ __('Waktu') }}</p>
                        <p>{{ $event->time_info }}</p>
                    </div>
                </div>
                @endif
                @if($event->location)
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue text-lg mt-0.5">location_on</span>
                    <div>
                        <p class="font-medium text-navy">{{ __('Lokasi') }}</p>
                        <p>{{ $event->location }}</p>
                    </div>
                </div>
                @endif
                @if($event->description)
                <div class="pt-3 border-t border-grey-200">
                    <p class="font-medium text-navy mb-2">{{ __('Deskripsi') }}</p>
                    <p class="leading-relaxed">{{ $event->description }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
