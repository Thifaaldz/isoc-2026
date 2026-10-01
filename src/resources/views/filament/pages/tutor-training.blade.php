<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->tutor?->tot_completed)
            <x-filament::section>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">ToT Tutor Sudah Lulus</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Akun tutor ini sudah eligible untuk mendampingi pelatihan lapangan.</p>
                    </div>
                    <x-filament::badge color="success">Nilai sempurna</x-filament::badge>
                </div>
            </x-filament::section>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-6">
                <x-filament::section>
                    <x-slot name="heading">TOR Pelatihan Tutor</x-slot>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Tujuan</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Menyiapkan tutor/fasilitator lokal agar mampu menjalankan 6 modul OTS, pre-test, post-test, kuis modul, absensi, dokumentasi, dan pendampingan WAG mentoring sesuai standar DSC.</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Kriteria Lulus</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Tutor wajib memahami TOR, menyelesaikan LMS ToT, dan mendapatkan nilai 100 pada cek pemahaman awal sebelum event lapangan dilanjutkan.</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Saat Pelatihan</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Memimpin ice breaking, menyampaikan 6 modul, mengelola pre/post-test, memandu microsite s.id, simulasi kasus, role-play, open mic, dan refleksi.</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Setelah Pelatihan</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Melengkapi foto per sesi, video slogan, absensi basah, bukti dukung, WAG mentoring, dan monitoring KPI peserta bersama admin lokal.</p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">LMS Modul ToT</x-slot>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($this->modules as $module)
                            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $module['title'] }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $module['summary'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <x-slot name="heading">Cek Pemahaman</x-slot>
                <form wire:submit.prevent="submitTraining" class="space-y-5">
                    @foreach ($this->questions as $index => $question)
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-semibold text-gray-950 dark:text-white">{{ $index + 1 }}. {{ $question['question'] }}</legend>
                            <div class="space-y-2">
                                @foreach ($question['options'] as $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:border-primary-400 dark:border-white/10 dark:text-gray-200">
                                        <input
                                            type="radio"
                                            wire:model="answers.{{ $index }}"
                                            value="{{ $option }}"
                                            class="mt-1 border-gray-300 text-primary-600 focus:ring-primary-600"
                                        >
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <x-filament::button type="submit" icon="heroicon-o-check-circle" class="w-full justify-center">
                        Simpan Kelulusan ToT
                    </x-filament::button>
                </form>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
