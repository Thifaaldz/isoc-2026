<x-filament-panels::page>
    @php $statuses = $this->statuses; @endphp

    <x-filament::section>
        <x-slot name="heading">Event yang membuka pendaftaran tutor</x-slot>
        <x-slot name="description">Pilih event lalu klik Daftar Jadi Tutor. Pendaftaran divalidasi Admin RTIK Pusat; ToT untuk event yang didaftarkan bisa dikerjakan dari menu ToT Awal.</x-slot>

        @if ($this->events->isEmpty())
            <div style="align-items: center; color: rgb(100, 116, 139); display: grid; font-size: 14px; gap: 6px; justify-items: center; padding: 28px 12px; text-align: center;">
                <x-heroicon-o-calendar-days style="color: rgb(148, 163, 184); height: 36px; width: 36px;" />
                <span>Belum ada event yang dipublikasikan.</span>
            </div>
        @else
            <div class="ecc-grid">
                @foreach ($this->events as $event)
                    @php
                        $status = $statuses[$event->id] ?? null;
                        $reason = $this->closedReason($event);
                        $registered = in_array($status, [\App\Models\TutorRegistration::ASSIGNED, \App\Models\TutorRegistration::PENDING], true);
                        [$statusLabel, $statusColor] = match (true) {
                            $status === \App\Models\TutorRegistration::ASSIGNED => ['Tutor event ini', 'success'],
                            $status === \App\Models\TutorRegistration::PENDING => ['Menunggu validasi', 'warning'],
                            $status === \App\Models\TutorRegistration::REJECTED && ! $reason => ['Ditolak, bisa daftar ulang', 'danger'],
                            (bool) $reason => ['Pendaftaran ditutup', 'gray'],
                            default => ['Pendaftaran dibuka', 'info'],
                        };
                    @endphp
                    <x-event-catalog-card
                        wire:key="tutor-catalog-{{ $event->id }}"
                        :event="$event"
                        :status-label="$statusLabel"
                        :status-color="$statusColor"
                        quota-label="tutor"
                        :quota-count="$event->tutors_count"
                        :quota-target="(int) $event->target_tutors"
                        :highlight="$registered"
                        :muted="! $registered && (bool) $reason"
                    >
                        @if ($registered)
                            <x-filament::button tag="a" :href="\App\Filament\Pages\TutorTraining::getUrl(['event' => $event->id])" icon="heroicon-o-academic-cap" color="success">
                                Kerjakan ToT Event
                            </x-filament::button>
                        @elseif ($reason)
                            <x-filament::button disabled color="gray">{{ $reason }}</x-filament::button>
                        @else
                            {{ ($this->joinAction)(['event' => $event->id]) }}
                        @endif
                    </x-event-catalog-card>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
