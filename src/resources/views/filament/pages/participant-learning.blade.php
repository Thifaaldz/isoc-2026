<x-filament-panels::page>
    <style>
        .learning-page {
            display: grid;
            gap: 22px;
            width: 100%;
        }

        .learning-page * {
            box-sizing: border-box;
        }

        .learning-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .learning-header {
            padding: 28px;
        }

        .learning-title {
            color: #111827;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .learning-description {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            margin: 10px 0 20px;
            max-width: 760px;
        }

        .learning-select-wrap {
            position: relative;
            width: 100%;
        }

        .learning-select {
            appearance: none;
            -moz-appearance: none;
            -webkit-appearance: none;
            background: #ffffff;
            background-image: none !important;
            background-repeat: no-repeat !important;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            color: #111827;
            font-size: 14px;
            min-height: 44px;
            padding: 0 46px 0 14px;
            width: 100%;
        }

        .learning-select::-ms-expand {
            display: none;
        }

        .learning-select-chevron {
            align-items: center;
            color: #64748b;
            display: flex;
            height: 100%;
            pointer-events: none;
            position: absolute;
            right: 14px;
            top: 0;
        }

        .learning-select-chevron svg {
            height: 20px;
            width: 20px;
        }

        .module-list {
            display: grid;
            gap: 16px;
        }

        .module-card {
            overflow: hidden;
        }

        .module-summary {
            display: grid;
            gap: 20px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 24px;
        }

        .module-card.is-active {
            border-color: #d97706;
            box-shadow: 0 0 0 1px rgba(217, 119, 6, 0.22), 0 8px 22px rgba(15, 23, 42, 0.08);
        }

        .module-eyebrow {
            color: #d97706;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin: 0;
            text-transform: uppercase;
        }

        .module-title {
            color: #111827;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.35;
            margin: 8px 0 0;
        }

        .module-text {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
            margin: 10px 0 0;
            max-width: 860px;
        }

        .module-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .module-badge {
            align-items: center;
            border: 1px solid #e5e7eb;
            border-radius: 999px;
            color: #94a3b8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 7px;
            min-height: 32px;
            padding: 0 12px;
        }

        .module-badge.is-ready {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .module-badge svg,
        .learning-button svg,
        .material-tab svg {
            height: 16px;
            width: 16px;
        }

        .module-action {
            align-items: flex-start;
            display: flex;
            justify-content: flex-end;
            min-width: 148px;
        }

        .learning-button {
            align-items: center;
            border: 0;
            border-radius: 9px;
            cursor: pointer;
            display: inline-flex;
            font-size: 14px;
            font-weight: 800;
            gap: 8px;
            justify-content: center;
            min-height: 42px;
            padding: 0 16px;
            text-decoration: none;
            white-space: nowrap;
        }

        .learning-button-primary {
            background: #d97706;
            color: #ffffff;
        }

        .learning-button-secondary {
            background: #ffffff;
            border: 1px solid #d1d5db;
            color: #374151;
        }

        .module-panel {
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            padding: 24px;
        }

        .panel-head {
            align-items: flex-start;
            display: flex;
            gap: 18px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .panel-title {
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.4;
            margin: 4px 0 0;
        }

        .material-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .material-tab {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 9px;
            color: #374151;
            cursor: pointer;
            display: inline-flex;
            font-size: 13px;
            font-weight: 800;
            gap: 8px;
            min-height: 40px;
            padding: 0 13px;
        }

        .material-tab.is-active {
            background: #fff7ed;
            border-color: #d97706;
            color: #c2410c;
        }

        .material-type {
            color: #d97706;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .viewer-empty {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            color: #64748b;
            font-size: 14px;
            padding: 28px;
        }

        .viewer-frame,
        .viewer-video {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
            width: 100%;
        }

        .viewer-frame {
            height: 78vh;
            min-height: 720px;
        }

        .viewer-video {
            min-height: 620px;
        }

        @media (max-width: 900px) {
            .module-summary,
            .panel-head {
                grid-template-columns: 1fr;
            }

            .module-action {
                justify-content: flex-start;
            }

            .panel-head {
                flex-direction: column;
            }
        }

        @media (max-width: 640px) {
            .learning-header,
            .module-summary,
            .module-panel {
                padding: 18px;
            }

            .learning-title {
                font-size: 22px;
            }

            .viewer-frame {
                height: 70vh;
                min-height: 520px;
            }

            .viewer-video {
                min-height: 360px;
            }
        }
    </style>

    @if(! $this->participant)
        <div class="learning-card learning-header">
            <div class="learning-description">Akun ini belum terhubung dengan data peserta.</div>
        </div>
    @elseif($this->events->isEmpty())
        <div class="learning-card learning-header">
            <div class="learning-description">Belum ada event pembelajaran yang terhubung dengan akun peserta ini.</div>
        </div>
    @else
        <div class="learning-page">
            <section class="learning-card learning-header">
                <h2 class="learning-title">Modul Pembelajaran</h2>
                <p class="learning-description">
                    Pelajari materi melalui modul PDF, slide presentasi, video pembelajaran, dan latihan soal.
                </p>
                <div class="learning-select-wrap">
                    <select wire:model.live="selectedEventId" class="learning-select">
                        @foreach($this->events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                    <span class="learning-select-chevron">
                        <x-heroicon-o-chevron-down />
                    </span>
                </div>
            </section>

            @if($this->selectedEvent)
                <section class="module-list">
                    @forelse($this->meetings as $meeting)
                        @php
                            $hasPdf = $meeting->materials->contains('type', 'pdf');
                            $hasPpt = $meeting->materials->contains('type', 'ppt');
                            $hasVideo = $meeting->materials->contains('type', 'video');
                            $hasQuiz = filled($this->quizForMeeting($meeting));
                            $hasTask = filled($meeting->task_title) || filled($meeting->task_description);
                            $isActive = $this->activeMeeting?->id === $meeting->id;
                            $canOpenMeeting = $this->canAccessMeeting($meeting);
                            $viewer = $isActive ? $this->materialViewer($this->activeMaterial) : null;
                        @endphp

                        <article class="learning-card module-card {{ $isActive ? 'is-active' : '' }}">
                            <div class="module-summary">
                                <div>
                                    <p class="module-eyebrow">Pertemuan {{ $meeting->order }}</p>
                                    <h3 class="module-title">{{ $meeting->title }}</h3>
                                    <p class="module-text">
                                        {{ $meeting->description ?: 'Memahami materi inti, contoh kasus, langkah pencegahan, serta latihan soal untuk menguji pemahaman peserta.' }}
                                    </p>

                                    <div class="module-badges">
                                        <span class="module-badge {{ $hasPdf ? 'is-ready' : '' }}">
                                            <x-heroicon-o-document-text />
                                            PDF
                                        </span>
                                        <span class="module-badge {{ $hasPpt ? 'is-ready' : '' }}">
                                            <x-heroicon-o-presentation-chart-bar />
                                            PPT
                                        </span>
                                        <span class="module-badge {{ $hasVideo ? 'is-ready' : '' }}">
                                            <x-heroicon-o-video-camera />
                                            Video
                                        </span>
                                        <span class="module-badge {{ $hasQuiz ? 'is-ready' : '' }}">
                                            <x-heroicon-o-clipboard-document-check />
                                            Quiz
                                        </span>
                                        <span class="module-badge {{ $hasTask ? 'is-ready' : '' }}">
                                            <x-heroicon-o-pencil-square />
                                            Tugas
                                        </span>
                                    </div>
                                </div>

                                <div class="module-action">
                                    @if($isActive)
                                        <button type="button" wire:click="backToMeetingList" class="learning-button learning-button-secondary">
                                            <x-heroicon-o-chevron-up />
                                            Tutup Modul
                                        </button>
                                    @else
                                        <button type="button" wire:click="openMeeting({{ $meeting->id }})" class="learning-button {{ $canOpenMeeting ? 'learning-button-primary' : 'learning-button-secondary' }}">
                                            <x-heroicon-o-chevron-down />
                                            {{ $canOpenMeeting ? 'Mulai Belajar' : 'Terkunci' }}
                                        </button>
                                        @if(! $canOpenMeeting)
                                            <p class="module-text" style="margin-top: 8px; max-width: 240px;">
                                                {{ $this->meetingLockReason($meeting) }}
                                            </p>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if($isActive)
                                <div class="module-panel">
                                    <div class="panel-head">
                                        <div>
                                            <p class="module-eyebrow">Materi Pertemuan {{ $meeting->order }}</p>
                                            <h4 class="panel-title">
                                                {{ $this->activeMaterial ? 'Preview: ' . $this->activeMaterial->title : 'Preview Materi' }}
                                            </h4>
                                        </div>
                                        <a href="{{ \App\Filament\Pages\ParticipantTests::getUrl() }}" class="learning-button learning-button-primary">
                                            <x-heroicon-o-clipboard-document-check />
                                            Buka Tes
                                        </a>
                                    </div>

                                    <div class="material-tabs">
                                        @forelse($meeting->materials as $material)
                                            <button
                                                type="button"
                                                wire:click="previewMaterial({{ $material->id }})"
                                                class="material-tab {{ $this->activeMaterial?->id === $material->id ? 'is-active' : '' }}"
                                            >
                                                <span class="material-type">{{ \App\Models\LearningMaterial::TYPES[$material->type] ?? $material->type }}</span>
                                                <span>{{ $material->title }}</span>
                                            </button>
                                        @empty
                                            <div class="viewer-empty">Belum ada materi untuk modul ini.</div>
                                        @endforelse
                                    </div>

                                    @if($hasTask)
                                        <div class="viewer-empty" style="margin-bottom: 18px;">
                                            <div class="module-eyebrow">Tugas Peserta</div>
                                            <div class="panel-title">{{ $meeting->task_title ?: 'Tugas Pertemuan ' . $meeting->order }}</div>
                                            @if($meeting->task_description)
                                                <p class="module-text">{{ $meeting->task_description }}</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if(! $this->activeMaterial)
                                        <div class="viewer-empty">Pilih materi untuk melihat preview.</div>
                                    @elseif(! $viewer['url'])
                                        <div class="viewer-empty">Materi belum memiliki file atau tautan.</div>
                                    @elseif(in_array($viewer['type'], ['pdf', 'ppt', 'link'], true))
                                        <iframe src="{{ $viewer['embed_url'] }}" class="viewer-frame" allowfullscreen></iframe>
                                    @elseif($viewer['type'] === 'video' && str_contains($viewer['embed_url'], 'youtube.com/embed'))
                                        <iframe src="{{ $viewer['embed_url'] }}" class="viewer-video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                    @elseif($viewer['type'] === 'video')
                                        <video controls class="viewer-video">
                                            <source src="{{ $viewer['url'] }}">
                                        </video>
                                    @else
                                        <iframe src="{{ $viewer['embed_url'] }}" class="viewer-frame" allowfullscreen></iframe>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="learning-card learning-header">
                            <div class="learning-description">Admin belum mengatur modul untuk event ini.</div>
                        </div>
                    @endforelse
                </section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
