<x-filament-panels::page>
    @php $enrolledIds = $this->enrolledEventIds; @endphp

    <x-filament::section>
        <x-slot name="heading">Event yang tersedia</x-slot>
        <x-slot name="description">Pilih event yang sedang dibuka, lalu klik Ikuti Event. Event yang sudah kamu ikuti bisa langsung dibuka dari sini.</x-slot>

        @if ($this->events->isEmpty())
            <p class="text-sm text-gray-500">Belum ada event yang dipublikasikan.</p>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($this->events as $event)
                    @php
                        $joined = in_array($event->id, $enrolledIds, true);
                        $location = match ($event->event_type) {
                            'webinar' => 'Webinar / Zoom',
                            'hybrid' => ($event->school?->name ? $event->school->name . ' + Zoom' : 'Hybrid'),
                            default => $event->school?->name ?? '-',
                        };
                    @endphp
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($joined)
                                <x-filament::badge color="success" icon="heroicon-m-check-circle">Sudah terdaftar</x-filament::badge>
                            @elseif ($event->registration_open && $this->isFull($event))
                                <x-filament::badge color="warning">Kuota penuh</x-filament::badge>
                            @elseif ($event->registration_open)
                                <x-filament::badge color="info">Pendaftaran dibuka</x-filament::badge>
                            @else
                                <x-filament::badge color="gray">Pendaftaran ditutup</x-filament::badge>
                            @endif
                            <x-filament::badge color="gray">{{ ucfirst($event->event_type ?: 'offline') }}</x-filament::badge>
                        </div>

                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $event->title }}</h3>

                        <dl class="grid gap-1 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 text-gray-400" /><span>{{ $event->starts_at?->translatedFormat('d F Y') ?? '-' }}{{ $event->starts_at && $event->ends_at ? ', ' . $event->starts_at->format('H.i') . ' - ' . $event->ends_at->format('H.i') . ' WIB' : '' }}</span></div>
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-map-pin" class="h-4 w-4 text-gray-400" /><span>{{ $location }}</span></div>
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-user-group" class="h-4 w-4 text-gray-400" /><span>{{ $event->participants_count }}/{{ $event->target_participants ?: '-' }} peserta</span></div>
                        </dl>

                        @if ($event->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($event->description, 180) }}</p>
                        @endif

                        @if ($event->orderedPartners->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-3">
                                @foreach ($event->orderedPartners as $partner)
                                    @if ($partner->logoSource())
                                        <img src="{{ $partner->logoSource() }}" alt="{{ $partner->name }}" title="{{ $partner->name }}" class="h-7 w-auto object-contain">
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-auto flex flex-wrap gap-2 pt-2">
                            @if ($joined)
                                <x-filament::button tag="a" :href="url('/peserta?event=' . $event->id)" icon="heroicon-o-arrow-right-circle" color="success">
                                    Buka Dashboard Event
                                </x-filament::button>
                            @elseif ($event->registration_open && $this->isFull($event))
                                <x-filament::button disabled color="gray">Kuota penuh</x-filament::button>
                            @elseif ($event->registration_open)
                                {{ ($this->joinAction)(['event' => $event->id]) }}
                            @else
                                <x-filament::button disabled color="gray">Pendaftaran ditutup</x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
