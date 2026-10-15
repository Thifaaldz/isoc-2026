<x-filament-panels::page>
    @php $enrolledIds = $this->enrolledEventIds; @endphp

    <x-filament::section>
        <x-slot name="heading">Event yang tersedia</x-slot>
        <x-slot name="description">Pilih event yang sedang dibuka, lalu klik Ikuti Event. Event yang sudah kamu ikuti bisa langsung dibuka dari sini.</x-slot>

        @if ($this->events->isEmpty())
            <div style="align-items: center; color: rgb(100, 116, 139); display: grid; font-size: 14px; gap: 6px; justify-items: center; padding: 28px 12px; text-align: center;">
                <x-heroicon-o-calendar-days style="color: rgb(148, 163, 184); height: 36px; width: 36px;" />
                <span>Belum ada event yang dipublikasikan.</span>
            </div>
        @else
            <div class="ecc-grid">
                @foreach ($this->events as $event)
                    @php
                        $joined = in_array($event->id, $enrolledIds, true);
                        [$statusLabel, $statusColor] = match (true) {
                            $joined => ['Sudah terdaftar', 'success'],
                            $this->isPast($event) => ['Event selesai', 'gray'],
                            ! $event->registration_open => ['Pendaftaran ditutup', 'gray'],
                            $this->isFull($event) => ['Kuota penuh', 'warning'],
                            default => ['Pendaftaran dibuka', 'info'],
                        };
                        $open = ! $joined && $statusLabel === 'Pendaftaran dibuka';
                    @endphp
                    <x-event-catalog-card
                        wire:key="catalog-{{ $event->id }}"
                        :event="$event"
                        :status-label="$statusLabel"
                        :status-color="$statusColor"
                        quota-label="peserta"
                        :quota-count="$event->participants_count"
                        :quota-target="(int) $event->target_participants"
                        :highlight="$joined"
                        :muted="! $joined && ! $open"
                    >
                        @if ($joined)
                            <x-filament::button tag="a" :href="url('/peserta?event=' . $event->id)" icon="heroicon-o-arrow-right-circle" color="success">
                                Buka Dashboard Event
                            </x-filament::button>
                        @elseif ($open)
                            {{ ($this->joinAction)(['event' => $event->id]) }}
                        @else
                            <x-filament::button disabled color="gray">{{ $statusLabel }}</x-filament::button>
                        @endif
                    </x-event-catalog-card>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
