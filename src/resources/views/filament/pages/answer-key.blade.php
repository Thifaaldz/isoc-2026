<x-filament-panels::page>
    @php
        $events = $this->events;
        $event = $events->firstWhere('id', (int) $this->selectedEventId);
        $assessments = $this->assessments;
        $sets = $assessments->map(fn ($assessment) => [
            'key' => $assessment->type,
            'label' => $assessment->type === 'pre' ? 'Pre-Test' : 'Post-Test',
            'assessment' => $assessment,
            'questions' => $this->questionsFor($assessment),
        ])->values();
    @endphp

    <style>
        .ak { --ak-border: rgba(148, 163, 184, .35); --ak-muted: rgb(100, 116, 139); --ak-text: rgb(17, 24, 39); --ak-card: #fff; --ak-soft: rgb(248, 250, 252);
              --ak-ok: rgb(22, 163, 74); --ak-ok-soft: rgba(22, 163, 74, .1); --ak-ok-text: rgb(21, 128, 61); --ak-accent: rgb(22, 163, 74);
              display: grid; gap: 18px; }
        .dark .ak { --ak-border: rgba(255, 255, 255, .1); --ak-muted: rgb(148, 163, 184); --ak-text: rgb(241, 245, 249); --ak-card: rgb(24, 24, 27); --ak-soft: rgba(255, 255, 255, .04);
                    --ak-ok-soft: rgba(74, 222, 128, .12); --ak-ok-text: rgb(134, 239, 172); }
        .ak-card { background: var(--ak-card); border: 1px solid var(--ak-border); border-radius: 14px; }
        .ak-head { align-items: center; display: flex; flex-wrap: wrap; gap: 16px; justify-content: space-between; padding: 18px 20px; }
        .ak-title { color: var(--ak-text); font-size: 18px; font-weight: 800; margin: 0; }
        .ak-sub { color: var(--ak-muted); font-size: 13px; margin: 4px 0 0; }
        .ak-note { align-items: flex-start; background: rgba(245, 158, 11, .1); border-radius: 10px; color: rgb(180, 83, 9); display: flex; font-size: 13px; gap: 8px; line-height: 1.5; padding: 10px 12px; }
        .dark .ak-note { color: rgb(252, 211, 77); }
        .ak-toolbar { align-items: center; display: flex; flex-wrap: wrap; gap: 10px; }
        .ak-tabs { background: var(--ak-soft); border: 1px solid var(--ak-border); border-radius: 10px; display: inline-flex; gap: 4px; padding: 4px; }
        .ak-tab { border-radius: 8px; color: var(--ak-muted); cursor: pointer; font-size: 13px; font-weight: 700; padding: 7px 14px; }
        .ak-tab.is-active { background: var(--ak-accent); color: #fff; }
        .ak-tab small { font-weight: 600; opacity: .8; }
        .ak-search { background: var(--ak-card); border: 1px solid var(--ak-border); border-radius: 10px; color: var(--ak-text); flex: 1 1 220px; font-size: 13px; min-height: 38px; padding: 0 12px; }
        .ak-toggle { align-items: center; color: var(--ak-text); cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 600; gap: 6px; }
        .ak-btn { align-items: center; border: 1px solid var(--ak-border); border-radius: 10px; color: var(--ak-text); cursor: pointer; display: inline-flex; font-size: 13px; font-weight: 700; gap: 6px; min-height: 38px; padding: 0 12px; }
        .ak-section-title { color: var(--ak-muted); font-size: 12px; font-weight: 800; letter-spacing: .06em; margin: 0 0 12px; text-transform: uppercase; }
        .ak-sheet { display: grid; gap: 8px; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); }
        .ak-sheet a { align-items: center; border: 1px solid var(--ak-border); border-radius: 10px; color: var(--ak-text); display: flex; gap: 10px; padding: 8px 10px; text-decoration: none; }
        .ak-sheet a:hover { border-color: var(--ak-accent); }
        .ak-num { align-items: center; background: var(--ak-soft); border-radius: 999px; color: var(--ak-muted); display: inline-flex; flex: 0 0 26px; font-size: 12px; font-weight: 800; height: 26px; justify-content: center; }
        .ak-letter { align-items: center; background: var(--ak-ok); border-radius: 8px; color: #fff; display: inline-flex; flex: 0 0 26px; font-size: 13px; font-weight: 800; height: 26px; justify-content: center; }
        .ak-sheet-text { font-size: 12.5px; line-height: 1.35; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .ak-list { display: grid; gap: 12px; }
        .ak-q { padding: 16px 18px; scroll-margin-top: 90px; }
        .ak-q:target { box-shadow: 0 0 0 2px var(--ak-accent); }
        .ak-q-head { align-items: flex-start; display: flex; gap: 12px; }
        .ak-q-text { color: var(--ak-text); flex: 1; font-size: 14.5px; font-weight: 700; line-height: 1.55; margin: 2px 0 0; }
        .ak-q-answer { background: var(--ak-ok-soft); border-radius: 999px; color: var(--ak-ok-text); flex: 0 0 auto; font-size: 12px; font-weight: 800; padding: 4px 10px; white-space: nowrap; }
        .ak-options { display: grid; gap: 6px; margin: 12px 0 0 38px; }
        .ak-opt { align-items: center; border: 1px solid var(--ak-border); border-radius: 10px; color: var(--ak-text); display: flex; font-size: 13.5px; gap: 10px; line-height: 1.45; padding: 8px 10px; }
        .ak-opt .ak-opt-letter { align-items: center; border: 1px solid var(--ak-border); border-radius: 7px; color: var(--ak-muted); display: inline-flex; flex: 0 0 24px; font-size: 12px; font-weight: 800; height: 24px; justify-content: center; }
        .ak-opt.is-correct { background: var(--ak-ok-soft); border-color: var(--ak-ok); color: var(--ak-ok-text); font-weight: 700; }
        .ak-opt.is-correct .ak-opt-letter { background: var(--ak-ok); border-color: var(--ak-ok); color: #fff; }
        .ak-empty { color: var(--ak-muted); font-size: 13px; padding: 18px; text-align: center; }
        @media (max-width: 640px) { .ak-options { margin-left: 0; } .ak-q-head { flex-wrap: wrap; } }
        @media print {
            .fi-sidebar, .fi-topbar, .ak-no-print { display: none !important; }
            .ak-q, .ak-sheet a { break-inside: avoid; }
        }
    </style>

    @if ($events->isEmpty())
        <div class="ak"><div class="ak-card ak-empty">Belum ada event yang ditugaskan kepada Anda.</div></div>
    @else
        <div class="ak" x-data="{ tab: @js($sets->first()['key'] ?? 'pre'), q: '', onlyCorrect: false }">
            <div class="ak-card ak-head">
                <div>
                    <p class="ak-title">{{ $event?->title }}</p>
                    <p class="ak-sub">
                        @foreach ($sets as $set)
                            {{ $set['label'] }}: {{ count($set['questions']) }} soal{{ $set['assessment']->questions_per_attempt ? ' (peserta dapat ' . $set['assessment']->questions_per_attempt . ' acak)' : '' }}@if (! $loop->last) · @endif
                        @endforeach
                    </p>
                </div>
                <div class="ak-toolbar ak-no-print">
                    @if ($events->count() > 1)
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedEventId" aria-label="Pilih event">
                                @foreach ($events as $option)
                                    <option value="{{ $option->id }}">{{ $option->title }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                    <button type="button" class="ak-btn" x-on:click="window.print()">
                        <x-heroicon-o-printer style="height: 16px; width: 16px;" /> Cetak
                    </button>
                </div>
            </div>

            <div class="ak-note">
                <x-heroicon-o-information-circle style="height: 18px; width: 18px; flex: 0 0 18px;" />
                <span>Urutan soal dan pilihan jawaban <strong>diacak untuk setiap peserta</strong>. Cocokkan jawaban peserta dengan <strong>teks jawaban</strong>, bukan hurufnya. Gunakan kolom cari untuk menemukan soal dengan cepat.</span>
            </div>

            @if ($sets->isEmpty())
                <div class="ak-card ak-empty">Event ini belum memiliki pre-test atau post-test.</div>
            @else
                <div class="ak-toolbar ak-no-print">
                    <div class="ak-tabs" role="tablist">
                        @foreach ($sets as $set)
                            <span class="ak-tab" role="tab" x-bind:class="{ 'is-active': tab === @js($set['key']) }" x-on:click="tab = @js($set['key'])">
                                {{ $set['label'] }} <small>{{ count($set['questions']) }}</small>
                            </span>
                        @endforeach
                    </div>
                    <input type="search" class="ak-search" x-model.debounce.150ms="q" placeholder="Cari kata di soal atau jawaban, mis. &quot;password&quot;">
                    <label class="ak-toggle"><input type="checkbox" x-model="onlyCorrect"> Hanya jawaban benar</label>
                </div>

                @foreach ($sets as $set)
                    <div class="ak" x-show="tab === @js($set['key'])" @if (! $loop->first) x-cloak @endif wire:key="ak-set-{{ $set['assessment']->id }}">
                        <div class="ak-card" style="padding: 16px 18px;">
                            <p class="ak-section-title">Kunci ringkas {{ $set['label'] }}</p>
                            <div class="ak-sheet">
                                @foreach ($set['questions'] as $number => $question)
                                    <a href="#ak-{{ $set['key'] }}-{{ $number + 1 }}" title="{{ $question['question'] }}"
                                       x-show="! q || @js($question['search']).includes(q.toLowerCase())">
                                        <span class="ak-num">{{ $number + 1 }}</span>
                                        <span class="ak-letter">{{ collect($question['answers'])->pluck('letter')->implode('/') ?: '?' }}</span>
                                        <span class="ak-sheet-text">{{ collect($question['answers'])->pluck('text')->implode(' / ') ?: 'Kunci belum diisi' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="ak-list">
                            @foreach ($set['questions'] as $number => $question)
                                <div class="ak-card ak-q" id="ak-{{ $set['key'] }}-{{ $number + 1 }}"
                                     x-show="! q || @js($question['search']).includes(q.toLowerCase())">
                                    <div class="ak-q-head">
                                        <span class="ak-num">{{ $number + 1 }}</span>
                                        <p class="ak-q-text">{{ $question['question'] }}</p>
                                        <span class="ak-q-answer">Kunci: {{ collect($question['answers'])->pluck('letter')->implode(', ') ?: '-' }}</span>
                                    </div>
                                    <div class="ak-options">
                                        @foreach ($question['options'] as $option)
                                            <div class="ak-opt {{ $option['is_correct'] ? 'is-correct' : '' }}" @unless ($option['is_correct']) x-show="! onlyCorrect" @endunless>
                                                <span class="ak-opt-letter">{{ $option['letter'] }}</span>
                                                <span style="flex: 1;">{{ $option['text'] }}</span>
                                                @if ($option['is_correct'])
                                                    <x-heroicon-s-check-circle style="height: 18px; width: 18px; flex: 0 0 18px;" />
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</x-filament-panels::page>
