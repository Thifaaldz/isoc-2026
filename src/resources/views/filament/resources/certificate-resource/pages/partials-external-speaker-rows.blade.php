{{-- Baris sertifikat narasumber yang belum terdaftar di sistem (input manual Admin RTIK Pusat). --}}
@foreach ($this->externalRows() as $row)
    <tr class="border-b border-gray-100 dark:border-white/5" wire:key="external-{{ $row['entry']['id'] }}">
        <td class="px-3 py-2 font-semibold text-gray-950 dark:text-white">
            {{ $row['entry']['name'] }}
            <x-filament::badge color="warning" size="sm" style="display: inline-flex; margin-left: 6px;">Belum terdaftar</x-filament::badge>
        </td>
        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ filled($row['entry']['institution'] ?? null) ? $row['entry']['institution'] : '-' }}</td>
        @if ($withRole ?? false)
            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">Narasumber</td>
        @endif
        <td class="px-3 py-2 text-gray-600 dark:text-gray-300" style="white-space: nowrap;">{{ $row['number'] }}</td>
        <td class="px-3 py-2">
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <x-filament::button size="sm" color="gray" icon="heroicon-o-eye" tag="a" target="_blank" :href="route('speaker-certificates.external-preview', [$this->eventId, $row['entry']['id']])">Preview</x-filament::button>
                <x-filament::button size="sm" icon="heroicon-o-arrow-down-tray" tag="a" :href="route('speaker-certificates.external-download', [$this->eventId, $row['entry']['id']])">Download</x-filament::button>
                {{ ($this->deleteManualAction)(['id' => $row['entry']['id']]) }}
            </div>
        </td>
    </tr>
@endforeach
