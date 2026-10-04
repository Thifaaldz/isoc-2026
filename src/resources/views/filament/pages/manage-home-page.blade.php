<x-filament-panels::page>
    <form wire:submit="save" class="grid gap-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check">Simpan Perubahan</x-filament::button>
            <span class="text-sm text-gray-500 dark:text-gray-400">Perubahan langsung tampil di halaman utama setelah disimpan.</span>
        </div>
    </form>
</x-filament-panels::page>
