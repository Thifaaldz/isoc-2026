@php
    $progress = (int) ($stats['progress'] ?? 0);
    $hasEvent = filled($event);
    $isWebinarEvent = $event?->event_type === 'webinar';
    $approval = $approval ?? ['approved' => true, 'requires_approval' => false, 'status' => 'approved', 'notes' => null];
    $dashboardOpen = (bool) ($approval['approved'] ?? true);
    $hasQuiz = ($stats['quiz_total'] ?? 0) > 0;
    // Urutan sesuai syarat sertifikat; Kuis Modul hanya tampil bila event memiliki kuis.
    $statusItems = array_values(array_filter([
        ['label' => 'Pre-Test', 'done' => (bool) ($stats['pre_done'] ?? false)],
        $hasQuiz ? ['label' => 'Kuis Modul', 'done' => ($stats['quiz_done'] ?? 0) >= ($stats['quiz_total'] ?? 0)] : null,
        ['label' => 'Post-Test', 'done' => (bool) ($stats['post_done'] ?? false)],
        ['label' => 'Link s.id / Microsite', 'done' => (bool) ($stats['microsite_done'] ?? false)],
    ]));
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
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        color: #111827;
        font-size: 14px;
        font-weight: 700;
        min-height: 42px;
        padding: 0 40px 0 12px;
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

    .pd-proof-card {
        grid-column: 1 / -1;
    }

    .pd-proof-card .pd-check-row {
        gap: 10px;
    }

    .pd-upload-form {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .pd-upload-form input[type="file"] {
        color: #374151;
        font-size: 13px;
        max-width: 100%;
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
                    <div class="pd-event-switcher">
                        <select wire:model.live="selectedEventId" aria-label="Pilih event">
                            @foreach ($events as $availableEvent)
                                <option value="{{ $availableEvent->id }}" @selected($event?->id === $availableEvent->id)>
                                    {{ $availableEvent->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                @if ($certificateUrl ?? null)
                    <a class="pd-button pd-button-success" style="margin-top: 0;" href="{{ $certificateUrl }}" target="_blank" rel="noopener">
                        <x-heroicon-o-trophy style="height: 16px; width: 16px;" />
                        Sertifikat
                    </a>
                @endif
            </div>
        </section>

        <section class="pd-info-grid">
            @if (($approval['requires_approval'] ?? false) && ! $dashboardOpen)
                @php
                    $followDone = (bool) ($proof['follow_complete'] ?? false);
                    $wagDone = (bool) ($proof['wag_complete'] ?? false);
                @endphp
                <div class="pd-card pd-info-card pd-proof-card">
                    <div class="pd-icon"><x-heroicon-o-shield-check /></div>
                    <div style="flex: 1; min-width: 0;">
                        <p class="pd-card-label">Bukti Dukung Peserta</p>
                        <p class="pd-card-title">Lengkapi bukti dukung untuk membuka modul, tes, dan rundown</p>
                        <p class="pd-card-text">Follow Instagram ISOC lalu upload screenshot-nya, dan join WhatsApp Group kegiatan. Akses terbuka otomatis setelah keduanya lengkap.</p>

                        <div class="pd-check-list" style="margin-top: 16px;">
                            <div class="pd-check-row" style="align-items: flex-start; flex-direction: column;">
                                <div class="pd-check-left" style="justify-content: space-between; width: 100%;">
                                    <span class="pd-check-label">1. Follow Instagram ISOC</span>
                                    <span class="pd-check-status {{ $followDone ? 'is-done' : '' }}">{{ $followDone ? 'Sudah upload' : 'Belum' }}</span>
                                </div>
                                <a class="pd-button pd-button-map" style="margin-top: 0;" href="{{ \App\Filament\Widgets\ParticipantDashboardOverview::INSTAGRAM_URL }}" target="_blank" rel="noopener noreferrer">
                                    <x-heroicon-o-arrow-top-right-on-square style="height: 16px; width: 16px;" />
                                    Buka Instagram ISOC
                                </a>
                                <form wire:submit="submitInstagramEvidence" class="pd-upload-form">
                                    <input type="file" wire:model="instagramEvidence" accept="image/*">
                                    <button type="submit" class="pd-button pd-button-primary" wire:loading.attr="disabled" wire:target="instagramEvidence,submitInstagramEvidence">
                                        {{ $followDone ? 'Ganti Screenshot' : 'Upload Screenshot' }}
                                    </button>
                                </form>
                                <span wire:loading wire:target="instagramEvidence" class="pd-card-text" style="margin: 0;">Mengunggah...</span>
                                @error('instagramEvidence') <p class="pd-card-text" style="color: #dc2626; margin: 0;">{{ $message }}</p> @enderror
                            </div>

                            <div class="pd-check-row" style="align-items: flex-start; flex-direction: column;">
                                <div class="pd-check-left" style="justify-content: space-between; width: 100%;">
                                    <span class="pd-check-label">2. Join WhatsApp Group kegiatan</span>
                                    <span class="pd-check-status {{ $wagDone ? 'is-done' : '' }}">{{ $wagDone ? 'Sudah join' : 'Belum' }}</span>
                                </div>
                                @if ($wag?->invite_link)
                                    <a class="pd-button pd-button-success" style="margin-top: 0;" href="{{ $wag->invite_link }}" target="_blank" rel="noopener noreferrer">
                                        <x-heroicon-o-arrow-top-right-on-square style="height: 16px; width: 16px;" />
                                        Masuk WAG {{ $wag->name }}
                                    </a>
                                @else
                                    <p class="pd-card-text" style="margin: 0;">Link WAG belum tersedia. Admin akan melengkapi link WAG kegiatan.</p>
                                @endif
                                <label class="pd-inline-check" style="margin-top: 0;">
                                    <input type="checkbox" wire:click="toggleJoinedWag" @checked($wagDone)>
                                    <span>Saya sudah join WhatsApp Group</span>
                                </label>
                            </div>
                        </div>
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
                    @if ($hasEvent && $dashboardOpen)
                        <label class="pd-inline-check">
                            <input type="checkbox" wire:click="toggleJoinedWag" @checked(auth()->user()?->participant?->joined_wag)>
                            <span>{{ auth()->user()?->participant?->joined_wag ? 'Sudah join WAG' : 'Saya sudah join WAG' }}</span>
                        </label>
                    @endif
                </div>
            </div>
        </section>

        @if ($hasEvent && $dashboardOpen)
            @php
                $score = fn ($value) => $value === null ? null : rtrim(rtrim(number_format((float) $value, 1, ',', ''), '0'), ',');
                $testCards = [
                    ['label' => 'Pre-Test', 'available' => $stats['pre_available'], 'done' => $stats['pre_done'], 'score' => $score($stats['pre_score']), 'unlocked' => true,
                        'hint' => 'Kerjakan sebelum membuka modul.'],
                    ['label' => 'Post-Test', 'available' => $stats['post_available'], 'done' => $stats['post_done'], 'score' => $score($stats['post_score']), 'unlocked' => $stats['post_unlocked'],
                        'hint' => $stats['post_unlocked'] ? 'Kerjakan setelah seluruh modul selesai.' : ($hasQuiz ? 'Terbuka setelah pre-test dan semua kuis modul selesai.' : 'Terbuka setelah pre-test selesai.')],
                ];
            @endphp
            <section class="pd-info-grid">
                @php
                    // Post-test belum lulus: tampilkan status dan tombol ulang selama kesempatan masih ada.
                    $postFailed = $stats['post_done'] && ! $stats['post_passed'];
                    $testCards[1]['failed'] = $postFailed;
                    $testCards[1]['retakes'] = $stats['post_retakes_left'];
                @endphp
                @foreach ($testCards as $test)
                    <div class="pd-card pd-info-card">
                        <div class="pd-icon"><x-heroicon-o-clipboard-document-check /></div>
                        <div>
                            <p class="pd-card-label">{{ $test['label'] }}</p>
                            <p class="pd-card-title">
                                @if (! $test['available'])
                                    Belum tersedia
                                @elseif ($test['failed'] ?? false)
                                    Belum lulus · Nilai {{ $test['score'] ?? '-' }}
                                @elseif ($test['done'])
                                    Selesai · Nilai {{ $test['score'] ?? '-' }}
                                @else
                                    {{ $test['unlocked'] ? 'Siap dikerjakan' : 'Terkunci' }}
                                @endif
                            </p>
                            <p class="pd-card-text">
                                @if ($test['failed'] ?? false)
                                    Nilai minimal {{ $stats['post_passing'] + 0 }}. {{ $test['retakes'] > 0 ? 'Anda bisa mengulang, sisa ' . $test['retakes'] . ' kesempatan.' : 'Kesempatan mengulang sudah habis.' }}
                                @else
                                    {{ $test['done'] ? 'Terima kasih, jawaban Anda sudah tersimpan.' : $test['hint'] }}
                                @endif
                            </p>
                            @if (($test['failed'] ?? false) && $test['retakes'] > 0)
                                <a class="pd-button pd-button-primary" style="margin-top: 14px; width: fit-content;" href="{{ $testsUrl }}">
                                    <x-heroicon-o-arrow-path style="height: 16px; width: 16px;" />
                                    Ulangi Post-Test
                                </a>
                            @endif
                            @if ($test['available'] && ! $test['done'])
                                <a class="pd-button {{ $test['unlocked'] ? 'pd-button-primary' : 'pd-button-disabled' }}" style="margin-top: 14px; width: fit-content;" href="{{ $testsUrl }}">
                                    <x-heroicon-o-pencil-square style="height: 16px; width: 16px;" />
                                    Kerjakan {{ $test['label'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="pd-card pd-info-card">
                    <div class="pd-icon"><x-heroicon-o-link /></div>
                    <div style="flex: 1; min-width: 0;">
                        <p class="pd-card-label">Link Microsite</p>
                        <p class="pd-card-title">{{ $stats['microsite_done'] ? 'Sudah disematkan' : 'Sematkan link microsite' }}</p>
                        <p class="pd-card-text">Ketik link s.id / microsite Anda tanpa https:// (mis. s.id/ISOC_Champion). Link dicek otomatis dan harus bisa dibuka.</p>
                        <form wire:submit="saveMicrosite" class="pd-upload-form" style="margin-top: 14px;">
                            <div style="align-items: stretch; border: 1px solid #d1d5db; border-radius: 8px; display: flex; flex: 1; min-height: 40px; min-width: 0; overflow: hidden;">
                                <span style="align-items: center; background: #f1f5f9; border-right: 1px solid #d1d5db; color: #64748b; display: flex; font-size: 13px; font-weight: 700; padding: 0 10px;">https://</span>
                                <input type="text" wire:model="micrositeUrl" placeholder="s.id/ISOC_Champion" aria-label="Link microsite tanpa https://"
                                    x-on:input="$el.value = $el.value.replace(/^\s*https?:\/\//i, '')"
                                    style="border: 0; box-shadow: none; flex: 1; font-size: 13px; min-width: 0; outline: none; padding: 0 12px;">
                            </div>
                            <button type="submit" class="pd-button pd-button-primary" wire:loading.attr="disabled" wire:target="saveMicrosite">Simpan</button>
                        </form>
                        @error('micrositeUrl') <p class="pd-card-text" style="color: #dc2626;">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        @endif

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
                @if ($hasQuiz)
                    <p class="pd-card-label">Kuis Modul</p>
                    <div class="pd-stat-value">{{ $stats['quiz_done'] }}/{{ $stats['quiz_total'] }}</div>
                @else
                    <p class="pd-card-label">Pre &amp; Post-Test</p>
                    <div class="pd-stat-value">{{ (int) ($stats['pre_done'] ?? false) + (int) ($stats['post_done'] ?? false) }}/2</div>
                @endif
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
