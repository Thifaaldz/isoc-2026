<x-filament-panels::page>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Pendaftaran tutor dari halaman <a href="{{ route('tutors.register') }}" target="_blank" class="font-semibold text-primary-600 hover:underline">Registrasi Tutor</a>.
        Tutor yang disetujui langsung aktif dan ditugaskan ke event yang dipilihnya.
    </p>

    {{ $this->table }}
</x-filament-panels::page>
