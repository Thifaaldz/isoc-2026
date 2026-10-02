<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="updateProfile" class="space-y-6">
            {{ $this->profileForm }}

            <div class="flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Simpan Profil
                </x-filament::button>
            </div>
        </form>

        <div class="space-y-6">
            @if (auth()->user()?->role === \App\Enums\UserRole::Peserta)
                <form wire:submit="updateMicrosite" class="space-y-6">
                    {{ $this->micrositeForm }}

                    <div class="flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-link">
                            Simpan Link s.id
                        </x-filament::button>
                    </div>
                </form>
            @endif

            <form wire:submit="updatePassword" class="space-y-6">
                {{ $this->passwordForm }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-key">
                        Ganti Password
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
