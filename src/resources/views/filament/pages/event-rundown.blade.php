<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $approved = $this->selectedEventApproved();
        $rundownItems = $this->rundownItems;
    @endphp

    <style>
        .rundown-page {
            display: grid;
            gap: 18px;
        }

        .rundown-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .rundown-hero {
            align-items: flex-start;
            display: flex;
            gap: 18px;
            justify-content: space-between;
            padding: 20px;
        }

        .rundown-title {
            color: #111827;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.3;
            margin: 0;
        }

        .rundown-text {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            margin: 6px 0 0;
        }

        .rundown-select {
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            color: #111827;
            min-height: 42px;
            min-width: 280px;
            padding: 0 40px 0 12px;
        }

        .rundown-info {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 0 20px 20px;
        }

        .rundown-info-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
        }

        .rundown-label {
            color: #64748b;
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .rundown-value {
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.4;
        }

        .rundown-table-wrap {
            overflow-x: auto;
        }

        .rundown-table {
            border-collapse: collapse;
            min-width: 820px;
            width: 100%;
        }

        .rundown-table th,
        .rundown-table td {
            border-bottom: 1px solid #eef2f7;
            font-size: 13px;
            padding: 13px 16px;
            text-align: left;
            vertical-align: top;
        }

        .rundown-table th {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .rundown-table td {
            color: #111827;
        }

        .rundown-time {
            color: #c2410c;
            font-weight: 800;
            white-space: nowrap;
        }

        .rundown-empty {
            color: #64748b;
            font-size: 14px;
            padding: 28px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .rundown-hero {
                display: grid;
            }

            .rundown-select {
                min-width: 0;
                width: 100%;
            }

            .rundown-info {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="rundown-page">
        <section class="rundown-card">
            <div class="rundown-hero">
                <div>
                    <h2 class="rundown-title">{{ $event?->title ?? 'Belum ada event' }}</h2>
                    <p class="rundown-text">Cek alur kegiatan, jam pelaksanaan, PIC, dan catatan seminar yang sudah disusun admin.</p>
                </div>

                @if ($events->isNotEmpty())
                    <select class="rundown-select" wire:model.live="selectedEventId">
                        @foreach ($events as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            @if ($event)
                <div class="rundown-info">
                    <div class="rundown-info-item">
                        <span class="rundown-label">Lokasi</span>
                        <div class="rundown-value">{{ $event->school?->name ?? '-' }}</div>
                    </div>
                    <div class="rundown-info-item">
                        <span class="rundown-label">Jadwal</span>
                        <div class="rundown-value">{{ $event->starts_at?->translatedFormat('d F Y') ?? '-' }}</div>
                    </div>
                    <div class="rundown-info-item">
                        <span class="rundown-label">Materi Event</span>
                        <div class="rundown-value">{{ $event->moduleTemplate?->name ?? '-' }}</div>
                    </div>
                </div>
            @endif
        </section>

        <section class="rundown-card">
            @if ($event && ! $approved)
                <div class="rundown-empty">Rundown event umum akan terbuka setelah satu approval dari Admin RTIK Daerah atau Tutor selesai.</div>
            @elseif ($event && $rundownItems !== [])
                <div class="rundown-table-wrap">
                    <table class="rundown-table">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Agenda</th>
                                <th>PIC</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rundownItems as $item)
                                <tr>
                                    <td class="rundown-time">{{ $item['start_time'] ?? '--:--' }} - {{ $item['end_time'] ?? '--:--' }}</td>
                                    <td>{{ $item['activity'] ?? '-' }}</td>
                                    <td>{{ $item['pic'] ?? '-' }}</td>
                                    <td>{{ $item['notes'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($event)
                <div class="rundown-empty">Rundown acara belum diisi oleh admin.</div>
            @else
                <div class="rundown-empty">Belum ada event yang terhubung ke akun ini.</div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
