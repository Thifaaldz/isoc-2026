<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $this->selectedEvent;
        $data = $this->completeness;
    @endphp

    <style>
        .pc-page { display: grid; gap: 18px; }
        .pc-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
            padding: 20px;
        }
        .pc-head {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
        }
        .pc-title { color: #111827; font-size: 22px; font-weight: 850; margin: 0; }
        .pc-text { color: #64748b; font-size: 13px; line-height: 1.6; margin: 6px 0 0; }
        .pc-select {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            color: #111827;
            min-height: 42px;
            min-width: 320px;
            padding: 0 12px;
        }
        .pc-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .pc-proof {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .pc-proof-head {
            align-items: center;
            display: flex;
            gap: 10px;
            justify-content: space-between;
        }
        .pc-proof-title { color: #111827; font-size: 15px; font-weight: 800; margin: 0; }
        .pc-badge {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 10px;
        }
        .pc-badge.ok { background: #dcfce7; color: #166534; }
        .pc-badge.wait { background: #fef3c7; color: #92400e; }
        .pc-button {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            margin-top: 14px;
            padding: 9px 12px;
            text-decoration: none;
        }
        .pc-button.blue { background: #2563eb; color: #fff; }
        .pc-button.green { background: #16a34a; color: #fff; }
        .pc-button.orange { background: #e57200; color: #fff; }
        .pc-proof-image {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            display: flex;
            height: 180px;
            justify-content: center;
            margin-top: 14px;
            overflow: hidden;
        }
        .pc-proof-image img { height: 100%; object-fit: contain; width: 100%; }
        .pc-empty { color: #94a3b8; font-size: 13px; padding: 14px; text-align: center; }
        .pc-check {
            align-items: center;
            color: #334155;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 8px;
            margin-top: 14px;
        }
        @media (max-width: 900px) {
            .pc-head { display: grid; }
            .pc-select { min-width: 0; width: 100%; }
            .pc-grid { grid-template-columns: 1fr; }
        }
    </style>

    <div class="pc-page">
        <section class="pc-card">
            <div class="pc-head">
                <div>
                    <h2 class="pc-title">Bukti Kelengkapan</h2>
                    <p class="pc-text">Pantau bukti follow Instagram, join WhatsApp Group, approval awal, dan link s.id untuk event yang kamu ikuti.</p>
                </div>

                @if ($events->isNotEmpty())
                    <select class="pc-select" wire:model.live="selectedEventId">
                        @foreach ($events as $item)
                            <option value="{{ $item->id }}">{{ $item->title }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
        </section>

        @if (! $event)
            <section class="pc-card">
                <div class="pc-empty">Belum ada event yang terhubung dengan akun peserta ini.</div>
            </section>
        @else
            <section class="pc-grid">
                <div class="pc-proof">
                    <div class="pc-proof-head">
                        <h3 class="pc-proof-title">Bukti Follow Instagram</h3>
                        <span class="pc-badge {{ $data['follow_complete'] ? 'ok' : 'wait' }}">{{ $data['follow_complete'] ? 'Lengkap' : 'Belum lengkap' }}</span>
                    </div>
                    <p class="pc-text">Screenshot yang diupload dari Dashboard peserta.</p>
                    <div class="pc-proof-image">
                        @if ($data['follow_src'])
                            <a href="{{ $data['follow_url'] ?: $data['follow_src'] }}" target="_blank">
                                <img src="{{ $data['follow_src'] }}" alt="Bukti follow Instagram">
                            </a>
                        @else
                            <div class="pc-empty">Bukti gambar belum tersedia.</div>
                        @endif
                    </div>
                </div>

                <div class="pc-proof">
                    <div class="pc-proof-head">
                        <h3 class="pc-proof-title">WhatsApp Group</h3>
                        <span class="pc-badge {{ $data['wag_complete'] ? 'ok' : 'wait' }}">{{ $data['wag_complete'] ? 'Sudah join' : 'Belum ceklis' }}</span>
                    </div>
                    <p class="pc-text">{{ $data['wag']?->name ?? 'Link WAG belum tersedia dari admin.' }}</p>
                    @if ($data['wag']?->invite_link)
                        <a class="pc-button green" href="{{ $data['wag']->invite_link }}" target="_blank">Buka Link WAG</a>
                    @endif
                    <label class="pc-check">
                        <input type="checkbox" wire:click="toggleJoinedWag" @checked(auth()->user()?->participant?->joined_wag)>
                        <span>{{ $data['wag_complete'] ? 'Saya sudah join WAG' : 'Ceklis jika sudah join WAG' }}</span>
                    </label>
                </div>

                <div class="pc-proof">
                    <div class="pc-proof-head">
                        <h3 class="pc-proof-title">Approval Awal</h3>
                        <span class="pc-badge {{ $data['initial_approved'] ? 'ok' : 'wait' }}">{{ $data['initial_approved'] ? 'Approved' : 'Menunggu' }}</span>
                    </div>
                    <p class="pc-text">Otomatis approved jika bukti follow Instagram dan checklist WAG sudah lengkap.</p>
                </div>

                <div class="pc-proof">
                    <div class="pc-proof-head">
                        <h3 class="pc-proof-title">Form s.id / Microsite</h3>
                        <span class="pc-badge {{ $data['microsite_complete'] ? 'ok' : 'wait' }}">{{ $data['microsite_complete'] ? 'Approved' : 'Belum terisi' }}</span>
                    </div>
                    <p class="pc-text">
                        @if ($data['microsite']?->sid_url)
                            {{ $data['microsite']->sid_url }}
                        @else
                            Lengkapi link s.id di halaman Profil Saya.
                        @endif
                    </p>
                    @if ($data['microsite']?->sid_url)
                        <a class="pc-button blue" href="{{ $data['microsite']->sid_url }}" target="_blank">Buka s.id</a>
                    @else
                        <a class="pc-button orange" href="{{ \App\Filament\Pages\Profile::getUrl() }}">Isi di Profil</a>
                    @endif
                </div>
            </section>

            <section class="pc-card">
                <div class="pc-proof-head">
                    <div>
                        <h3 class="pc-proof-title">Status Akhir Kelengkapan</h3>
                        <p class="pc-text">Status ini menjadi ringkasan bukti peserta untuk event yang dipilih.</p>
                    </div>
                    <span class="pc-badge {{ $data['all_complete'] ? 'ok' : 'wait' }}">{{ $data['all_complete'] ? 'Lengkap' : 'Belum lengkap' }}</span>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
