<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $rows = $this->rows;
    @endphp

    <style>
        .approval-page {
            display: grid;
            gap: 18px;
        }

        .approval-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            overflow: hidden;
        }

        .approval-head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            padding: 20px;
        }

        .approval-title {
            color: #111827;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .approval-text {
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
            margin: 6px 0 0;
        }

        .approval-select {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            color: #111827;
            min-height: 42px;
            min-width: 320px;
            padding: 0 12px;
        }

        .approval-table-wrap {
            overflow-x: auto;
        }

        .approval-table {
            border-collapse: collapse;
            min-width: 1040px;
            width: 100%;
        }

        .approval-table th,
        .approval-table td {
            border-top: 1px solid #eef2f7;
            font-size: 13px;
            padding: 14px 16px;
            text-align: left;
            vertical-align: top;
        }

        .approval-table th {
            background: #f9fafb;
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .approval-name {
            color: #111827;
            font-weight: 800;
        }

        .approval-muted {
            color: #64748b;
            margin-top: 4px;
        }

        .approval-proof {
            align-items: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            display: flex;
            height: 132px;
            justify-content: center;
            overflow: hidden;
            width: 210px;
        }

        .approval-proof img {
            height: 100%;
            object-fit: contain;
            width: 100%;
        }

        .approval-proof-empty {
            color: #94a3b8;
            font-size: 12px;
            padding: 14px;
            text-align: center;
        }

        .approval-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 10px;
        }

        .approval-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .approval-badge.approved {
            background: #dcfce7;
            color: #166534;
        }

        .approval-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .approval-button {
            background: #16a34a;
            border-radius: 8px;
            color: #fff;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            padding: 9px 12px;
        }

        .approval-empty {
            color: #64748b;
            font-size: 14px;
            padding: 32px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .approval-head {
                display: grid;
            }

            .approval-select {
                min-width: 0;
                width: 100%;
            }
        }
    </style>

    <div class="approval-page">
        <section class="approval-card">
            <div class="approval-head">
                <div>
                    <h2 class="approval-title">Approval Peserta</h2>
                    <p class="approval-text">Cek peserta event umum dan preview gambar bukti follow Instagram sebelum approval.</p>
                </div>

                @if ($events->isNotEmpty())
                    <select class="approval-select" wire:model.live="selectedEventId">
                        @foreach ($events as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </section>

        <section class="approval-card">
            @if (! $event)
                <div class="approval-empty">Belum ada event umum yang perlu diapproval untuk akun ini.</div>
            @elseif ($rows->isEmpty())
                <div class="approval-empty">Belum ada peserta terdaftar pada event ini.</div>
            @else
                <div class="approval-table-wrap">
                    <table class="approval-table">
                        <thead>
                            <tr>
                                <th>Peserta</th>
                                <th>Organisasi</th>
                                <th>Join WAG</th>
                                <th>Bukti Follow IG</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php
                                    $participant = $row['participant'];
                                    $status = $row['status'];
                                    $proofSrc = $row['evidence_src'];
                                    $proofUrl = $row['evidence_url'];
                                @endphp
                                <tr>
                                    <td>
                                        <div class="approval-name">{{ $participant->user?->name ?? '-' }}</div>
                                        <div class="approval-muted">{{ $participant->user?->email ?? '-' }}</div>
                                        <div class="approval-muted">{{ $participant->user?->phone ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $participant->organization ?: '-' }}</div>
                                        <div class="approval-muted">{{ $participant->position ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="approval-badge {{ $participant->joined_wag ? 'approved' : 'pending' }}">
                                            {{ $participant->joined_wag ? 'Sudah join' : 'Belum ceklis' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="approval-proof">
                                            @if ($proofSrc)
                                                <a href="{{ $proofUrl ?: $proofSrc }}" target="_blank">
                                                    <img src="{{ $proofSrc }}" alt="Bukti follow IG {{ $participant->user?->name }}">
                                                </a>
                                            @else
                                                <div class="approval-proof-empty">Gambar bukti belum tersedia.</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span class="approval-badge {{ $status }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td>
                                        @if ($status !== 'approved')
                                            <button
                                                type="button"
                                                class="approval-button"
                                                wire:click="approve({{ $event->id }}, {{ $participant->id }})"
                                            >
                                                Approve
                                            </button>
                                        @else
                                            <span class="approval-muted">Sudah approved</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
