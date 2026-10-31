<x-filament-panels::page>
    @php
        $role = auth()->user()?->role;
        $certificates = $this->myCertificates();
    @endphp

    <x-filament::section icon="heroicon-o-trophy">
        <x-slot name="heading">Sertifikat Saya</x-slot>

        @if ($role === \App\Enums\UserRole::SuperAdmin)
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Akun Admin RTIK Pusat tidak menerima sertifikat. Sertifikat peserta, tutor, dan fasilitator dicetak dari menu
                <a href="{{ \App\Filament\Resources\CertificateResource::getUrl() }}" class="font-semibold text-primary-600 hover:underline">e-Certificate</a>.
            </p>
        @elseif (empty($certificates))
            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ $role === \App\Enums\UserRole::Peserta ? 'Belum ada sertifikat yang terbit. Sertifikat terbit setelah Pre-Test, Post-Test, dan link s.id selesai.' : 'Belum ada event untuk sertifikat Anda.' }}
            </p>
        @else
            <div style="overflow-x: auto;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-white/10">
                            <th class="px-3 py-2 font-medium">Event</th>
                            <th class="px-3 py-2 font-medium">Sebagai</th>
                            <th class="px-3 py-2 font-medium">Nomor Sertifikat</th>
                            <th class="px-3 py-2 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($certificates as $certificate)
                            <tr class="border-b border-gray-100 dark:border-white/5" wire:key="certificate-{{ $loop->index }}">
                                <td class="px-3 py-2">
                                    <div class="font-semibold text-gray-950 dark:text-white">{{ $certificate['event'] }}</div>
                                    @if ($certificate['date'])
                                        <div class="text-xs text-gray-500">{{ $certificate['date'] }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $certificate['label'] }}</td>
                                <td class="px-3 py-2 text-gray-600 dark:text-gray-300" style="white-space: nowrap;">{{ $certificate['number'] }}</td>
                                <td class="px-3 py-2">
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <x-filament::button size="sm" color="gray" icon="heroicon-o-eye" tag="a" target="_blank" :href="$certificate['preview']">Preview</x-filament::button>
                                        <x-filament::button size="sm" icon="heroicon-o-printer" tag="a" :href="$certificate['download']">Cetak (PDF)</x-filament::button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

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
