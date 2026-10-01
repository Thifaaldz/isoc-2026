@extends('layouts.public')

@section('title', 'Pendaftaran Peserta DSC')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <a href="{{ route('program.show') }}" class="text-sm text-indigo-600">&larr; Lihat detail program</a>

    <h1 class="text-3xl font-bold mt-4 mb-2">Pendaftaran Peserta Digital Safety Champions</h1>
    <p class="text-gray-500 mb-6">Lengkapi akun dan profil peserta untuk masuk ke panel peserta.</p>

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="font-semibold mb-2">Deskripsi</h2>
        <p class="text-gray-700 whitespace-pre-line">Form ini membuat akun pengguna dengan role peserta sekaligus melengkapi data partisipan program Digital Safety Champions. Setelah pendaftaran berhasil, peserta otomatis diarahkan ke panel peserta.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Sekolah Aktif</h2>
            <p class="text-gray-700">{{ $schoolCount }} sekolah tersedia untuk pendaftaran.</p>
            <p class="text-gray-500 text-sm mt-2">Pilih sekolah asal pada form pendaftaran.</p>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="font-semibold mb-2">Modul Program</h2>
            <p class="text-gray-700">{{ $moduleCount }} modul OTS tersedia.</p>
            <p class="text-gray-500 text-sm mt-2">Materi, praktik, dan e-Certificate tersedia di panel peserta.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 mb-6" id="form-pendaftaran">
        <h2 class="font-semibold mb-2">Form Pendaftaran</h2>
        <p class="text-gray-500 text-sm mb-6">Email dan password yang diisi akan menjadi akses login ke panel peserta.</p>

        <form method="POST" action="{{ route('participants.register.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            @csrf

            @if ($errors->any())
                <div class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    Periksa kembali data pendaftaran yang ditandai.
                </div>
            @endif

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="school_id" class="block text-sm font-medium text-gray-700 mb-1">Sekolah</label>
                <select id="school_id" name="school_id" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih sekolah</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" @selected((string) old('school_id') === (string) $school->id)>
                            {{ $school->name }}{{ $school->city ? ' - ' . $school->city : '' }}
                        </option>
                    @endforeach
                </select>
                @error('school_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nis" class="block text-sm font-medium text-gray-700 mb-1">NIS</label>
                <input id="nis" name="nis" value="{{ old('nis') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('nis') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="grade" class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                <select id="grade" name="grade" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih kelas</option>
                    @foreach (['X', 'XI', 'XII'] as $grade)
                        <option value="{{ $grade }}" @selected(old('grade') === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
                @error('grade') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                <select id="gender" name="gender" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                </select>
                @error('gender') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('birth_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                <label class="flex gap-3 text-sm text-gray-600">
                    <input type="checkbox" name="followed_instagram" value="1" @checked(old('followed_instagram')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Sudah follow Instagram program.</span>
                </label>
                <label class="flex gap-3 text-sm text-gray-600">
                    <input type="checkbox" name="joined_wag" value="1" @checked(old('joined_wag')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Siap bergabung di WAG mentoring sekolah.</span>
                </label>
                <label class="flex gap-3 text-sm text-gray-600">
                    <input type="checkbox" name="consent" value="1" required @checked(old('consent')) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Saya menyetujui penggunaan data untuk administrasi program Digital Safety Champions.</span>
                </label>
                @error('consent') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center gap-3">
                <button type="submit" class="inline-block px-6 py-3 rounded-lg bg-indigo-600 text-white font-medium">
                    Daftar Sebagai Peserta
                </button>
                <a href="{{ url('/peserta/login') }}" class="text-sm text-indigo-600">Sudah punya akun? Login peserta</a>
            </div>
        </form>
    </div>
</div>
@endsection
