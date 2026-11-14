<x-filament-panels::page>
    @if ($this->dataHeading())
        <div style="display: grid; gap: 12px;">
            <div>
                <h2 class="text-lg font-bold text-gray-950 dark:text-white">{{ $this->dataHeading() }}</h2>
                @if ($this->dataDescription())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->dataDescription() }}</p>
                @endif
            </div>
            {{ $this->table }}
        </div>
    @endif
</x-filament-panels::page>
