<x-filament-panels::page>
    @php
        $events = $this->events;
        $selected = $this->selectedEvent;
        $checkIns = $this->checkIns;
        $typeLabels = ['offline' => 'Offline', 'webinar' => 'Online / Webinar', 'hybrid' => 'Hybrid'];
    @endphp

    <style>
        .ac-list { display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
        .ac-card { border: 1px solid rgba(148, 163, 184, .35); border-radius: 14px; padding: 16px; display: grid; gap: 12px; cursor: pointer; }
        .ac-card.is-active { border-color: rgb(var(--primary-500)); box-shadow: 0 0 0 1px rgb(var(--primary-500)); }
        .ac-title { font-size: 15px; font-weight: 700; line-height: 1.35; }
        .ac-meta { color: #64748b; font-size: 12px; }
        .ac-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 30px; font-weight: 800; letter-spacing: .3em; text-align: center; padding: 12px; border-radius: 10px; background: rgba(var(--primary-500), .08); color: rgb(var(--primary-600)); }
        .ac-code.is-empty { font-size: 13px; letter-spacing: normal; font-weight: 600; color: #64748b; background: rgba(148, 163, 184, .12); }
        .ac-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .ac-count { font-size: 13px; }
        .ac-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ac-table th, .ac-table td { text-align: left; padding: 10px 8px; border-bottom: 1px solid rgba(148, 163, 184, .25); }
        .ac-table th { color: #64748b; font-weight: 600; }
    </style>

    @if ($events->isEmpty())
        <x-filament::section>
            <p class="ac-meta">Belum ada event untuk Anda.</p>
        </x-filament::section>
    @else
        <x-filament::section :heading="auth()->user()?->role === \App\Enums\UserRole::Admin ? 'Event yang Anda kelola' : 'Event yang Anda dampingi'" description="Generate kode lalu bagikan ke peserta (tampilkan di layar untuk offline, kirim di chat Zoom/WAG untuk online). Peserta memasukkan kode di menu Absensi pada panel peserta." icon="heroicon-o-qr-code">
            <div class="ac-list">
                @foreach ($events as $event)
                    <div wire:key="ac-{{ $event->id }}" wire:click="selectEvent({{ $event->id }})" class="ac-card {{ $selected?->id === $event->id ? 'is-active' : '' }}">
                        <div>
                            <div class="ac-title">{{ $event->title }}</div>
                            <div class="ac-meta">{{ $event->starts_at?->translatedFormat('d M Y, H:i') }} WIB · {{ $event->school?->name ?? '-' }}</div>
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <x-filament::badge color="info">{{ $typeLabels[$event->event_type ?: 'offline'] ?? $event->event_type }}</x-filament::badge>
                            @if ($this->isOpen($event))
                                <x-filament::badge color="success">Absensi dibuka hari ini</x-filament::badge>
                            @elseif ($event->starts_at?->isFuture())
                                <x-filament::badge color="gray">Belum dimulai</x-filament::badge>
                            @else
                                <x-filament::badge color="danger">Selesai</x-filament::badge>
                            @endif
                        </div>
                        <div class="ac-code {{ $event->attendance_code ? '' : 'is-empty' }}">
                            {{ $event->attendance_code ?: 'Kode belum dibuat' }}
                        </div>
                        <div class="ac-foot">
                            <span class="ac-count"><strong>{{ $event->checked_in_count }}</strong> / {{ $event->participants_count }} peserta hadir</span>
                            <x-filament::button size="sm" icon="heroicon-o-arrow-path" wire:click.stop="generate({{ $event->id }})"
                                wire:confirm="{{ $event->attendance_code ? 'Buat kode baru? Kode lama tidak bisa dipakai lagi.' : 'Buat kode absensi untuk event ini?' }}">
                                {{ $event->attendance_code ? 'Generate Ulang' : 'Generate Kode' }}
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        @if ($selected)
            <x-filament::section :heading="'Daftar hadir: ' . $selected->title" icon="heroicon-o-users">
                <x-slot name="headerEnd">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <x-filament::button size="sm" color="gray" tag="a" :href="route('events.attendance.wet', $selected)" target="_blank" icon="heroicon-o-printer">Template Absensi Basah</x-filament::button>
                        <x-filament::button size="sm" tag="a" :href="route('events.attendance.digital', $selected)" target="_blank" icon="heroicon-o-clipboard-document-list">Cetak Absensi Online</x-filament::button>
                    </div>
                </x-slot>
                @if ($checkIns->isEmpty())
                    <p class="ac-meta">Belum ada peserta yang absen dengan kode.</p>
                @else
                    <table class="ac-table">
                        <thead><tr><th>#</th><th>Nama Peserta</th><th>Kehadiran</th><th>Waktu Absen</th></tr></thead>
                        <tbody>
                            @foreach ($checkIns as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->participant?->user?->name ?? '-' }}</td>
                                    <td><x-filament::badge :color="$row->mode === 'online' ? 'info' : 'success'">{{ $row->mode === 'online' ? 'Online' : 'Offline' }}</x-filament::badge></td>
                                    <td>{{ $row->checked_in_at?->format('H:i') }} WIB</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-filament::section>
        @endif
    @endif
</x-filament-panels::page>
