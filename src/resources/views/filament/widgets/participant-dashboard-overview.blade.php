@php
    $progress = (int) ($stats['progress'] ?? 0);
    $hasEvent = filled($event);
    $isWebinarEvent = $event?->event_type === 'webinar';
    $approval = $approval ?? ['approved' => true, 'requires_approval' => false, 'status' => 'approved', 'notes' => null];
    $dashboardOpen = (bool) ($approval['approved'] ?? true);
    $statusItems = [
        ['label' => 'Pre-Test', 'done' => (bool) ($stats['pre_done'] ?? false)],
        ['label' => 'Kuis Modul', 'done' => ($stats['quiz_total'] ?? 0) > 0 && ($stats['quiz_done'] ?? 0) >= ($stats['quiz_total'] ?? 0)],
        ['label' => 'Post-Test', 'done' => (bool) ($stats['post_done'] ?? false)],
    ];
@endphp

<x-filament-widgets::widget>
<style>
    .participant-dashboard {
        display: grid;
        gap: 20px;
        width: 100%;
    }

    .participant-dashboard * {
        box-sizing: border-box;
    }

    .pd-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }

    .pd-hero {
        display: flex;
        justify-content: space-between;
        gap: 24px;
        align-items: center;
        padding: 28px;
        background: linear-gradient(180deg, #ffffff 0%, #fff7ed 100%);
    }

    .pd-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }

    .pd-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #c2410c;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 10px;
    }

    .pd-badge.is-active {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .pd-title {
        color: #111827;
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        margin: 0;
    }

    .pd-description {
        color: #64748b;
        font-size: 14px;
        line-height: 1.7;
        margin: 10px 0 0;
        max-width: 720px;
    }

    .pd-event-switcher {
        margin-top: 18px;
        max-width: 540px;
    }

    .pd-event-switcher select {
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        color: #111827;
        font-size: 14px;
        font-weight: 700;
        min-height: 42px;
        padding: 0 12px;
        width: 100%;
    }

    .pd-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        min-width: 220px;
    }

    .pd-button {
        align-items: center;
        border-radius: 8px;
        display: inline-flex;
        font-size: 13px;
        font-weight: 700;
        gap: 8px;
        justify-content: center;
        padding: 10px 14px;
        text-decoration: none;
        white-space: nowrap;
    }

    .pd-button-primary {
        background: #d97706;
        color: #ffffff;
    }

    .pd-button-secondary {
        background: #ffffff;
        border: 1px solid #d1d5db;
        color: #374151;
    }

    .pd-button-success {
        background: #16a34a;
        color: #ffffff;
        margin-top: 14px;
        width: fit-content;
    }

    .pd-button-map {
        background: #0284c7;
        color: #ffffff;
        margin-top: 14px;
        width: fit-content;
    }

    .pd-button-disabled {
        background: #e5e7eb;
        border: 1px solid #d1d5db;
        color: #6b7280;
        cursor: not-allowed;
        pointer-events: none;
    }

    .pd-info-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    }

    .pd-info-card {
        display: flex;
        gap: 14px;
        min-height: 154px;
        padding: 22px;
    }

    .pd-icon {
        align-items: center;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 10px;
        color: #d97706;
        display: flex;
        flex: 0 0 44px;
        height: 44px;
        justify-content: center;
        width: 44px;
    }

    .pd-icon svg {
        height: 20px;
        width: 20px;
    }

    .pd-card-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        margin: 0;
        text-transform: uppercase;
    }

    .pd-card-title {
        color: #111827;
        font-size: 16px;
        font-weight: 800;
        line-height: 1.4;
        margin: 8px 0 0;
    }

    .pd-card-text {
        color: #64748b;
        font-size: 13px;
        line-height: 1.65;
        margin: 8px 0 0;
    }

    .pd-stat-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .pd-stat-card {
        padding: 22px;
    }

    .pd-stat-value {
        color: #111827;
        font-size: 34px;
        font-weight: 850;
        line-height: 1;
        margin-top: 12px;
    }

    .pd-progress-line {
        background: #f1f5f9;
        border-radius: 999px;
        height: 8px;
        margin-top: 18px;
        overflow: hidden;
    }

    .pd-progress-line span {
        background: #d97706;
        border-radius: inherit;
        display: block;
        height: 100%;
    }

    .pd-bottom-grid {
        display: grid;
        gap: 20px;
        grid-template-columns: minmax(260px, 0.8fr) minmax(320px, 1.2fr);
    }

    .pd-progress-card {
        align-items: center;
        display: flex;
        flex-direction: column;
        padding: 26px;
        text-align: center;
    }

    .pd-ring {
        align-items: center;
        background: conic-gradient(#d97706 calc(var(--progress) * 1%), #e5e7eb 0);
        border-radius: 50%;
        display: flex;
        height: 168px;
        justify-content: center;
        padding: 12px;
        width: 168px;
    }

    .pd-ring-inner {
        align-items: center;
        background: #ffffff;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        height: 100%;
        justify-content: center;
        width: 100%;
    }

    .pd-ring-value {
        color: #111827;
        font-size: 36px;
        font-weight: 850;
        line-height: 1;
    }

    .pd-ring-caption {
        color: #64748b;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.06em;
        margin-top: 6px;
        text-transform: uppercase;
    }

    .pd-section-title {
        color: #111827;
        font-size: 18px;
        font-weight: 800;
        margin: 20px 0 0;
    }

    .pd-checklist-card {
        padding: 26px;
    }

    .pd-checklist-head {
        align-items: flex-start;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .pd-percent-chip {
        background: #fff7ed;
        border-radius: 999px;
        color: #c2410c;
        font-size: 13px;
        font-weight: 800;
        padding: 6px 11px;
    }

    .pd-check-list {
        display: grid;
        gap: 12px;
    }

    .pd-check-row {
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 14px 16px;
    }

    .pd-check-left {
        align-items: center;
        display: flex;
        gap: 12px;
    }

    .pd-check-icon {
        align-items: center;
        background: #f1f5f9;
        border-radius: 8px;
        color: #64748b;
        display: flex;
        height: 32px;
        justify-content: center;
        width: 32px;
    }

    .pd-check-icon svg {
        height: 16px;
        width: 16px;
    }

    .pd-check-icon.is-done {
        background: #dcfce7;
        color: #16a34a;
    }

    .pd-check-label {
        color: #111827;
        font-size: 14px;
        font-weight: 800;
    }

    .pd-check-status {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
    }

    .pd-check-status.is-done {
        color: #16a34a;
    }

    .pd-inline-check {
        align-items: center;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        display: inline-flex;
        gap: 9px;
        margin-top: 14px;
        padding: 10px 12px;
    }

    .pd-inline-check input {
        height: 18px;
        width: 18px;
    }

    .pd-inline-check span {
        color: #111827;
        font-size: 13px;
        font-weight: 800;
    }

    @media (max-width: 1100px) {
        .pd-info-grid,
        .pd-stat-grid,
        .pd-bottom-grid {
            grid-template-columns: 1fr 1fr;
        }

        .pd-hero {
            align-items: flex-start;
            flex-direction: column;
        }

        .pd-actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 720px) {
        .pd-hero,
        .pd-info-card,
        .pd-stat-card,
        .pd-progress-card,
        .pd-checklist-card {
            padding: 18px;
        }

        .pd-info-grid,
        .pd-stat-grid,
        .pd-bottom-grid {
            grid-template-columns: 1fr;
        }

        .pd-title {
            font-size: 22px;
        }
    }
</style>
    <div class="participant-dashboard">
        <section class="pd-card pd-hero">
            <div>
                <div class="pd-badges">
                    <span class="pd-badge">Dashboard Peserta</span>
                    <span class="pd-badge {{ $hasEvent ? 'is-active' : '' }}">{{ $hasEvent ? 'Seminar Aktif' : 'Menunggu Seminar' }}</span>
                    @if (($approval['requires_approval'] ?? false) && ! $dashboardOpen)
                        <span class="pd-badge">Menunggu Approval</span>
                    @endif
                </div>
                <h2 class="pd-title">{{ $event?->title ?? 'Belum ada seminar aktif' }}</h2>
                <p class="pd-description">
                    Pantau lokasi kegiatan, akses WhatsApp Group, dan selesaikan modul serta tes dari satu dashboard.
                </p>
                @if (($events ?? collect())->count() > 1)
                    <form class="pd-event-switcher" method="GET" action="{{ url('/peserta') }}">
                        <select name="event" onchange="this.form.submit()" aria-label="Pilih event">
                            @foreach ($events as $availableEvent)
                                <option value="{{ $availableEvent->id }}" @selected($event?->id === $availableEvent->id)>
                                    {{ $availableEvent->title }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <div class="pd-actions">
                <a class="pd-button {{ $dashboardOpen ? 'pd-button-primary' : 'pd-button-disabled' }}" href="{{ $learningUrl }}">
                    <x-heroicon-o-academic-cap style="height: 16px; width: 16px;" />
                    Buka Modul
                </a>
                <a class="pd-button {{ $dashboardOpen ? 'pd-button-secondary' : 'pd-button-disabled' }}" href="{{ $testsUrl }}">
                    <x-heroicon-o-clipboard-document-check style="height: 16px; width: 16px;" />
                    Buka Tes
                </a>
                <a class="pd-button {{ $dashboardOpen ? 'pd-button-secondary' : 'pd-button-disabled' }}" href="{{ $rundownUrl }}">
                    <x-heroicon-o-clock style="height: 16px; width: 16px;" />
                    Cek Rundown
                </a>
            </div>
        </section>

        <section class="pd-info-grid">
            @if (($approval['requires_approval'] ?? false) && ! $dashboardOpen)
                <div class="pd-card pd-info-card">
                    <div class="pd-icon"><x-heroicon-o-shield-check /></div>
                    <div>
                        <p class="pd-card-label">Approval Peserta Umum</p>
                        <p class="pd-card-title">Dashboard lengkap belum terbuka</p>
                        <p class="pd-card-text">
                            Status approval: {{ ucfirst($approval['status'] ?? 'pending') }}.
                            Satu approval dari Admin RTIK Daerah atau Tutor sudah cukup untuk membuka dashboard.
                        </p>
                        @if (! empty($approval['notes']))
                            <p class="pd-card-text">{{ $approval['notes'] }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if (! $isWebinarEvent)
                <div class="pd-card pd-info-card">
                    <div class="pd-icon"><x-heroicon-o-map-pin /></div>
                    <div>
                        <p class="pd-card-label">Lokasi Kegiatan</p>
                        <p class="pd-card-title">{{ $school?->name ?? '-' }}</p>
                        <p class="pd-card-text">{{ $school?->address ?? 'Alamat belum diisi.' }}</p>
                        @if ($school?->maps_url)
                            <a class="pd-button pd-button-map" href="{{ $school->maps_url }}" target="_blank">
                                <x-heroicon-o-map-pin style="height: 16px; width: 16px;" />
                                Buka Maps
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pd-card pd-info-card">
                <div class="pd-icon"><x-heroicon-o-calendar-days /></div>
                <div>
                    <p class="pd-card-label">Jadwal Seminar</p>
                    <p class="pd-card-title">{{ $event?->starts_at?->translatedFormat('d F Y') ?? 'Tanggal belum tersedia' }}</p>
                    <p class="pd-card-text">
                        {{ $event?->starts_at?->translatedFormat('H:i') ?? '--:--' }} - {{ $event?->ends_at?->translatedFormat('H:i') ?? '--:--' }} WIB
                    </p>
                    <p class="pd-card-text">{{ strtoupper($event?->event_type ?? 'offline') }}</p>
                </div>
            </div>

            @if ($isWebinarEvent)
                <div class="pd-card pd-info-card">
                    <div class="pd-icon"><x-heroicon-o-video-camera /></div>
                    <div>
                        <p class="pd-card-label">Link Webinar</p>
                        <p class="pd-card-title">{{ $event?->zoom_url ? 'Zoom tersedia' : 'Belum tersedia' }}</p>
                        <p class="pd-card-text">
                            {{ $event?->zoom_url ? 'Gunakan tombol berikut saat sesi webinar dimulai.' : 'Admin RTIK atau tutor akan mengisi link Zoom.' }}
                        </p>
                        @if ($event?->zoom_url)
                            <a class="pd-button pd-button-map" href="{{ $event->zoom_url }}" target="_blank">
                                <x-heroicon-o-video-camera style="height: 16px; width: 16px;" />
                                Buka Zoom
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="pd-card pd-info-card">
                <div class="pd-icon"><x-heroicon-o-chat-bubble-left-right /></div>
                <div>
                    <p class="pd-card-label">WhatsApp Group</p>
                    <p class="pd-card-title">{{ $wag?->name ?? 'Belum tersedia' }}</p>
                    <p class="pd-card-text">
                        {{ $wag ? (($wag->active_members ?? 0) . ' aktif dari ' . ($wag->member_count ?? 0) . ' anggota') : 'Tautan WAG akan muncul setelah admin mengisi data.' }}
                    </p>
                    @if ($wag?->invite_link)
                        <a class="pd-button pd-button-success" href="{{ $wag->invite_link }}" target="_blank">
                            <x-heroicon-o-arrow-top-right-on-square style="height: 16px; width: 16px;" />
                            Masuk WAG
                        </a>
                    @endif
                    @if ($hasEvent)
                        <label class="pd-inline-check">
                            <input type="checkbox" wire:click="toggleJoinedWag" @checked(auth()->user()?->participant?->joined_wag)>
                            <span>{{ auth()->user()?->participant?->joined_wag ? 'Sudah join WAG' : 'Saya sudah join WAG' }}</span>
                        </label>
                    @endif
                </div>
            </div>
        </section>

        <section class="pd-stat-grid">
            <div class="pd-card pd-stat-card">
                <p class="pd-card-label">Pertemuan</p>
                <div class="pd-stat-value">{{ $stats['meetings'] }}</div>
            </div>
            <div class="pd-card pd-stat-card">
                <p class="pd-card-label">Materi</p>
                <div class="pd-stat-value">{{ $stats['materials'] }}</div>
            </div>
            <div class="pd-card pd-stat-card">
                <p class="pd-card-label">Kuis Modul</p>
                <div class="pd-stat-value">{{ $stats['quiz_done'] }}/{{ $stats['quiz_total'] }}</div>
            </div>
            <div class="pd-card pd-stat-card">
                <p class="pd-card-label">Progress</p>
                <div class="pd-stat-value">{{ $progress }}%</div>
                <div class="pd-progress-line"><span style="width: {{ $progress }}%;"></span></div>
            </div>
        </section>

        <section class="pd-bottom-grid">
            <div class="pd-card pd-progress-card">
                <div class="pd-ring" style="--progress: {{ $progress }};">
                    <div class="pd-ring-inner">
                        <span class="pd-ring-value">{{ $progress }}%</span>
                        <span class="pd-ring-caption">Selesai</span>
                    </div>
                </div>
                <h3 class="pd-section-title">Progress Belajar</h3>
                <p class="pd-card-text">Selesaikan urutan belajar agar sertifikat bisa dicetak.</p>
            </div>

            <div class="pd-card pd-checklist-card">
                <div class="pd-checklist-head">
                    <div>
                        <h3 class="pd-section-title" style="margin-top: 0;">Status Pengerjaan</h3>
                        <p class="pd-card-text">Checklist aktivitas wajib peserta.</p>
                    </div>
                    <span class="pd-percent-chip">{{ $progress }}%</span>
                </div>

                <div class="pd-check-list">
                    @foreach ($statusItems as $item)
                        <div class="pd-check-row">
                            <div class="pd-check-left">
                                <span class="pd-check-icon {{ $item['done'] ? 'is-done' : '' }}">
                                    @if ($item['done'])
                                        <x-heroicon-o-check />
                                    @else
                                        <x-heroicon-o-clock />
                                    @endif
                                </span>
                                <span class="pd-check-label">{{ $item['label'] }}</span>
                            </div>
                            <span class="pd-check-status {{ $item['done'] ? 'is-done' : '' }}">
                                {{ $item['done'] ? 'Selesai' : 'Belum' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-filament-widgets::widget>
