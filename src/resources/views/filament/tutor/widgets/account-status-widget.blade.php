@php
    $status = $this->getStatus();
    $config = match ($status) {
        'verified' => ['color' => 'success', 'label' => 'Akun Anda sudah diverifikasi oleh Admin.'],
        'rejected' => ['color' => 'danger', 'label' => 'Pendaftaran Anda ditolak oleh Admin. Silakan hubungi Admin untuk informasi lebih lanjut.'],
        default => ['color' => 'warning', 'label' => 'Akun Anda masih menunggu verifikasi Admin. Beberapa fitur belum dapat diakses.'],
    };
@endphp

<div>
    @if ($status !== 'verified')
        <x-filament::section>
            <div class="flex items-center gap-3">
                <x-filament::badge :color="$config['color']">
                    {{ ucfirst($status) }}
                </x-filament::badge>
                <span>{{ $config['label'] }}</span>
            </div>
        </x-filament::section>
    @endif
</div>
