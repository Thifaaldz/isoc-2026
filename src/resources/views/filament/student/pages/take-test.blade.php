<x-filament-panels::page>
    @if ($attempt?->finished_at)
        <div class="mb-4 rounded-lg bg-emerald-50 text-emerald-700 px-4 py-3 text-sm">
            Anda sudah menyelesaikan {{ $type }} ini dengan skor {{ $attempt->score }}.
        </div>
    @endif

    <form wire:submit="submit">
        {{ $this->form }}

        @unless ($attempt?->finished_at)
            <div class="mt-6">
                <x-filament::button type="submit">
                    Kumpulkan Jawaban
                </x-filament::button>
            </div>
        @endunless
    </form>
</x-filament-panels::page>
