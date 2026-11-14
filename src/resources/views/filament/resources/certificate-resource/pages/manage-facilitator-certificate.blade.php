<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Sertifikat Narasumber untuk Fasilitator</x-slot>
        <x-slot name="description">Tersedia untuk fasilitator yang membuat event, serta fasilitator yang ditambahkan oleh Admin RTIK Pusat. Nomor sertifikat dibuat otomatis oleh sistem.</x-slot>

        <div style="display: grid; gap: 20px;">
            <label style="display: grid; gap: 6px; max-width: 720px;">
                <span class="text-sm font-medium text-gray-950 dark:text-white">Event</span>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="eventId">
                        @foreach ($this->eventOptions() as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>

            @if ($this->rows()->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-300">Event ini tidak dibuat oleh fasilitator, jadi belum ada sertifikat fasilitator.</p>
            @else
                <div style="overflow-x: auto;">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10">
                                <th class="px-3 py-2 font-medium">Nama di sertifikat</th>
                                <th class="px-3 py-2 font-medium">Instansi</th>
                                <th class="px-3 py-2 font-medium">Sebagai</th>
                                <th class="px-3 py-2 font-medium">No. Sertifikat</th>
                                <th class="px-3 py-2 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->rows() as $row)
                                <tr class="border-b border-gray-100 dark:border-white/5" wire:key="facilitator-{{ $row['user']->id }}">
                                    <td class="px-3 py-2 font-semibold text-gray-950 dark:text-white">
                                        {{ $row['user']->name }}
                                        @if ($row['manual'])
                                            <x-filament::badge color="info" size="sm" style="display: inline-flex; margin-left: 6px;">Tambahan</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $row['user']->school?->name ?? '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Narasumber</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300" style="white-space: nowrap;">{{ $row['number'] }}</td>
                                    <td class="px-3 py-2">
                                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                            <x-filament::button size="sm" color="gray" icon="heroicon-o-eye" tag="a" target="_blank" :href="route('facilitator-certificates.user-preview', [$this->eventId, $row['user']->id])">Preview</x-filament::button>
                                            <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" tag="a" :href="route('facilitator-certificates.user-download', [$this->eventId, $row['user']->id])">Download Sertifikat (PDF)</x-filament::button>
                                            @if ($row['manual'])
                                                {{ ($this->deleteManualAction)(['id' => $row['manual']['id']]) }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-panels::page>
