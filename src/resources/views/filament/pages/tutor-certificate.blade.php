<x-filament-panels::page>
    @php
        $tutor = $this->tutor;
        $event = $this->selectedEvent;
    @endphp

    <x-filament::section>
        <x-slot name="heading">Form Sertifikat Tutor</x-slot>
        <x-slot name="description">Pilih event yang kamu dampingi, lalu pratinjau atau unduh sertifikat tutor. Sertifikat bisa langsung dicetak tanpa syarat.</x-slot>

        @if ($this->events->isEmpty())
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Belum ada event yang ditugaskan ke akun kamu. Sertifikat tersedia setelah pendaftaran tutor di event disetujui Admin RTIK Pusat.
                <a href="{{ \App\Filament\Pages\TutorEventCatalog::getUrl() }}" class="font-semibold text-primary-600 hover:underline">Daftar event</a>
            </p>
        @else
            <div style="display: grid; gap: 20px; max-width: 720px;">
                <label style="display: grid; gap: 6px;">
                    <span class="text-sm font-medium text-gray-950 dark:text-white">Event</span>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="eventId">
                            @foreach ($this->events as $option)
                                <option value="{{ $option->id }}">{{ $option->title }}{{ $option->starts_at ? ' · ' . $option->starts_at->translatedFormat('d M Y') : '' }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </label>

                @if ($event)
                    <dl style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));" class="rounded-xl border border-gray-200 p-4 text-sm dark:border-white/10">
                        <div><dt class="text-gray-500">Nama di sertifikat</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $tutor?->user?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Instansi</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $tutor?->institution ?: ($tutor?->school?->name ?? '-') }}</dd></div>
                        <div><dt class="text-gray-500">Sebagai</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ \App\Services\TutorCertificateService::COMPETENCY }}</dd></div>
                        <div><dt class="text-gray-500">No. Sertifikat</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $this->certificateNumber() }}</dd></div>
                        <div><dt class="text-gray-500">Tanggal kegiatan</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $event->starts_at?->translatedFormat('d F Y') ?? '-' }}</dd></div>
                        <div><dt class="text-gray-500">Lokasi</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $event->school?->name ?? '-' }}</dd></div>
                    </dl>

                    <p class="text-xs text-gray-500">Nama atau instansi salah? Perbaiki dulu di <a href="{{ \App\Filament\Pages\Profile::getUrl() }}" class="font-semibold text-primary-600 hover:underline">Profil Saya</a>, lalu cetak ulang.</p>

                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <x-filament::button tag="a" :href="route('tutor-certificates.preview', $event)" target="_blank" icon="heroicon-o-eye" color="gray">
                            Pratinjau
                        </x-filament::button>
                        <x-filament::button tag="a" :href="route('tutor-certificates.download', $event)" icon="heroicon-o-arrow-down-tray">
                            Unduh Sertifikat (PDF)
                        </x-filament::button>
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
