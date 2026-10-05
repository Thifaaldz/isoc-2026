<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $modeOptions = $this->modeOptions;
        $attendance = $this->attendanceForSelected();
        $code = $this->codeForSelected();
        $history = $this->history;
        $typeLabels = ['offline' => 'Offline', 'webinar' => 'Online / Webinar', 'hybrid' => 'Hybrid (Offline & Online)'];
    @endphp

    <style>
        .att-grid { display: grid; gap: 18px; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); }
        .att-field { display: grid; gap: 6px; margin-bottom: 16px; }
        .att-label { font-size: 13px; font-weight: 600; }
        .att-help { color: #64748b; font-size: 12px; }
        .att-error { color: #dc2626; font-size: 12px; }
        .att-code-value { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 26px; font-weight: 800; letter-spacing: .35em; text-align: center; padding: 10px; border-radius: 10px; background: rgba(var(--primary-500), .08); color: rgb(var(--primary-600)); }
        .att-info { display: grid; gap: 10px; }
        .att-row { display: flex; gap: 10px; justify-content: space-between; font-size: 13px; border-bottom: 1px dashed rgba(148, 163, 184, .35); padding-bottom: 8px; }
        .att-row span:first-child { color: #64748b; }
        .att-done { border: 1px solid #86efac; background: rgba(34, 197, 94, .08); border-radius: 12px; padding: 14px 16px; font-size: 13px; }
        .att-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .att-table th, .att-table td { text-align: left; padding: 10px 8px; border-bottom: 1px solid rgba(148, 163, 184, .25); }
        .att-table th { color: #64748b; font-weight: 600; }
        @media (max-width: 1024px) { .att-grid { grid-template-columns: 1fr; } }
    </style>

    @if ($events->isEmpty())
        <x-filament::section>
            <p class="att-help">Anda belum terdaftar pada event yang sudah dipublish. Daftar event terlebih dahulu dari menu Cari Event.</p>
        </x-filament::section>
    @else
        <div class="att-grid">
            <x-filament::section icon="heroicon-o-clipboard-document-check" heading="Form Absensi" description="Kode absensi dibuat otomatis pada hari pelaksanaan. Absensi wajib diisi sebelum mengerjakan post-test.">
                <form wire:submit="submit">
                    <div class="att-field">
                        <label class="att-label" for="att-event">Event</label>
                        <x-filament::input.wrapper :valid="! $errors->has('eventId')">
                            <x-filament::input.select id="att-event" wire:model.live="eventId">
                                @foreach ($events as $item)
                                    <option value="{{ $item->id }}">{{ $item->title }} — {{ $item->starts_at?->translatedFormat('d M Y') }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('eventId') <span class="att-error">{{ $message }}</span> @enderror
                    </div>

                    @if ($attendance)
                        <div class="att-done">
                            <strong>Anda sudah absen di event ini.</strong><br>
                            Hadir {{ $attendance->mode === 'online' ? 'online' : 'offline' }} pada {{ $attendance->checked_in_at?->translatedFormat('d F Y, H:i') }} WIB.
                        </div>
                    @elseif (! $code)
                        <p class="att-help">
                            {{ $event?->starts_at?->isFuture()
                                ? 'Kode absensi muncul otomatis pada hari pelaksanaan (' . $event->starts_at->translatedFormat('d F Y') . ').'
                                : 'Absensi untuk event ini sudah ditutup.' }}
                        </p>
                    @else
                        <div class="att-field">
                            <label class="att-label" for="att-mode">Jenis Kehadiran</label>
                            <x-filament::input.wrapper :valid="! $errors->has('mode')" :disabled="count($modeOptions) < 2">
                                <x-filament::input.select id="att-mode" wire:model="mode" :disabled="count($modeOptions) < 2">
                                    @foreach ($modeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </x-filament::input.select>
                            </x-filament::input.wrapper>
                            <span class="att-help">Event hybrid dapat memilih offline atau online; event offline/webinar mengikuti tipe event.</span>
                            @error('mode') <span class="att-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="att-field">
                            <span class="att-label">Kode Absensi</span>
                            <div class="att-code-value">{{ $code }}</div>
                        </div>

                        <x-filament::button type="submit" icon="heroicon-o-check-circle" wire:loading.attr="disabled">
                            Absen Sekarang
                        </x-filament::button>
                    @endif
                </form>
            </x-filament::section>

            @if ($event)
                <x-filament::section icon="heroicon-o-calendar-days" heading="Detail Event">
                    <div class="att-info">
                        <div class="att-row"><span>Event</span><strong>{{ $event->title }}</strong></div>
                        <div class="att-row"><span>Tanggal</span><span>{{ $event->starts_at?->translatedFormat('l, d F Y') }}</span></div>
                        <div class="att-row"><span>Waktu</span><span>{{ $event->starts_at?->format('H:i') }} - {{ $event->ends_at?->format('H:i') }} WIB</span></div>
                        <div class="att-row"><span>Lokasi</span><span>{{ $event->school?->name ?? '-' }}</span></div>
                        <div class="att-row"><span>Tipe Event</span><span>{{ $typeLabels[$event->event_type ?: 'offline'] ?? $event->event_type }}</span></div>
                        <div class="att-row">
                            <span>Status Absensi</span>
                            @if ($this->isOpen($event))
                                <x-filament::badge color="success">Dibuka hari ini</x-filament::badge>
                            @elseif ($event->starts_at?->isFuture())
                                <x-filament::badge color="gray">Dibuka {{ $event->starts_at->translatedFormat('d M Y') }}</x-filament::badge>
                            @else
                                <x-filament::badge color="danger">Ditutup</x-filament::badge>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endif
        </div>

        <x-filament::section heading="Riwayat Absensi" icon="heroicon-o-clock">
            @if ($history->isEmpty())
                <p class="att-help">Belum ada absensi.</p>
            @else
                <table class="att-table">
                    <thead><tr><th>Event</th><th>Kehadiran</th><th>Waktu Absen</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($history as $row)
                            <tr>
                                <td>{{ $row->learningEvent?->title }}</td>
                                <td>{{ $row->mode === 'online' ? 'Online' : 'Offline' }}</td>
                                <td>{{ $row->checked_in_at?->translatedFormat('d M Y, H:i') }} WIB</td>
                                <td><x-filament::badge color="success">{{ ucfirst($row->status) }}</x-filament::badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
