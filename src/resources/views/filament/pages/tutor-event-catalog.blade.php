<x-filament-panels::page>
    @php $statuses = $this->statuses; @endphp

    <x-filament::section>
        <x-slot name="heading">Event yang membuka pendaftaran tutor</x-slot>
        <x-slot name="description">Pilih event lalu klik Daftar Jadi Tutor. Pendaftaran divalidasi Admin RTIK Pusat; ToT untuk event yang didaftarkan bisa dikerjakan dari menu ToT Awal.</x-slot>

        @if ($this->events->isEmpty())
            <p class="text-sm text-gray-500">Belum ada event yang dipublikasikan.</p>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($this->events as $event)
                    @php
                        $status = $statuses[$event->id] ?? null;
                        $reason = $this->closedReason($event);
                        $location = match ($event->event_type) {
                            'webinar' => 'Webinar / Zoom',
                            'hybrid' => ($event->school?->name ? $event->school->name . ' + Zoom' : 'Hybrid'),
                            default => $event->school?->name ?? '-',
                        };
                    @endphp
                    <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($status === \App\Models\TutorRegistration::ASSIGNED)
                                <x-filament::badge color="success" icon="heroicon-m-check-circle">Tutor event ini</x-filament::badge>
                            @elseif ($status === \App\Models\TutorRegistration::PENDING)
                                <x-filament::badge color="warning" icon="heroicon-m-clock">Menunggu validasi</x-filament::badge>
                            @elseif ($status === \App\Models\TutorRegistration::REJECTED)
                                <x-filament::badge color="danger">Ditolak, bisa daftar ulang</x-filament::badge>
                            @elseif ($reason)
                                <x-filament::badge color="gray">Pendaftaran ditutup</x-filament::badge>
                            @else
                                <x-filament::badge color="info">Pendaftaran dibuka</x-filament::badge>
                            @endif
                            <x-filament::badge color="gray">{{ ucfirst($event->event_type ?: 'offline') }}</x-filament::badge>
                        </div>

                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $event->title }}</h3>

                        <dl class="grid gap-1 text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-calendar" class="h-4 w-4 text-gray-400" /><span>{{ $event->starts_at?->translatedFormat('d F Y') ?? '-' }}{{ $event->starts_at && $event->ends_at ? ', ' . $event->starts_at->format('H.i') . ' - ' . $event->ends_at->format('H.i') . ' WIB' : '' }}</span></div>
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-map-pin" class="h-4 w-4 text-gray-400" /><span>{{ $location }}</span></div>
                            <div class="flex gap-2"><x-filament::icon icon="heroicon-m-user-group" class="h-4 w-4 text-gray-400" /><span>{{ $event->tutors_count }}/{{ $event->target_tutors ?: '-' }} tutor</span></div>
                        </dl>

                        @if ($event->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($event->description, 180) }}</p>
                        @endif

                        <div class="mt-auto flex flex-wrap gap-2 pt-2">
                            @if (in_array($status, [\App\Models\TutorRegistration::ASSIGNED, \App\Models\TutorRegistration::PENDING], true))
                                <x-filament::button tag="a" :href="\App\Filament\Pages\TutorTraining::getUrl(['event' => $event->id])" icon="heroicon-o-academic-cap" color="success">
                                    Kerjakan ToT Event
                                </x-filament::button>
                            @elseif ($reason)
                                <x-filament::button disabled color="gray">{{ $reason }}</x-filament::button>
                            @else
                                {{ ($this->joinAction)(['event' => $event->id]) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
