<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Sertifikat Narasumber untuk Fasilitator</x-slot>
        <x-slot name="description">Tersedia untuk fasilitator yang membuat event. Nomor sertifikat dibuat otomatis oleh sistem.</x-slot>

        <div style="display: grid; gap: 20px; max-width: 720px;">
            <label style="display: grid; gap: 6px;">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Event</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="eventId">
                        @foreach ($this->eventOptions() as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            @if ($this->facilitator)
                <dl style="display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));" class="rounded-xl border border-gray-200 p-4 text-sm dark:border-white/10">
                    <div><dt class="text-gray-500">Nama di sertifikat</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $this->facilitator->name }}</dd></div>
                    <div><dt class="text-gray-500">Instansi</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $this->facilitator->school?->name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Sebagai</dt><dd class="font-semibold text-gray-950 dark:text-white">Narasumber</dd></div>
                    <div><dt class="text-gray-500">No. Sertifikat</dt><dd class="font-semibold text-gray-950 dark:text-white">{{ $this->certificateNumber() }}</dd></div>
                </dl>

                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <x-filament::button color="gray" icon="heroicon-o-eye" tag="a" target="_blank" :href="route('facilitator-certificates.preview', $this->eventId)">Preview</x-filament::button>
                    <x-filament::button icon="heroicon-o-arrow-down-tray" tag="a" :href="route('facilitator-certificates.download', $this->eventId)">Download Sertifikat (PDF)</x-filament::button>
                </div>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-300">Event ini tidak dibuat oleh fasilitator, jadi belum ada sertifikat fasilitator.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
