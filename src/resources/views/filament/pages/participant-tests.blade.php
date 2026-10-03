<x-filament-panels::page>
    <style>
        .participant-test-grid {
            align-items: stretch;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-width: 1100px;
        }

        .participant-test-card {
            height: 100%;
        }

        .participant-test-card > section,
        .participant-test-card .fi-section {
            height: 100%;
        }

        .participant-test-body {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 190px;
        }

        .participant-test-result {
            margin-top: auto;
        }

        @media (max-width: 900px) {
            .participant-test-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if(! $this->participant)
        <x-filament::section>
            <div class="text-sm text-gray-600">Akun ini belum terhubung dengan data peserta.</div>
        </x-filament::section>
    @elseif($this->events->isEmpty())
        <x-filament::section>
            <div class="text-sm text-gray-600">Belum ada event pembelajaran yang terhubung dengan akun peserta ini.</div>
        </x-filament::section>
    @else
        <div class="space-y-6">
            <x-filament::section>
                <div class="grid gap-4 md:grid-cols-[1fr_260px] md:items-end">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Tes Peserta</h2>
                        <p class="mt-1 text-sm text-gray-500">Pre-test, kuis modul, post-test, dan sertifikat mengikuti event yang dipilih.</p>
                    </div>
                    <select wire:model.live="selectedEventId" class="fi-input block w-full rounded-lg border-gray-300 bg-white py-2 text-sm shadow-sm dark:border-white/10 dark:bg-white/5">
                        @foreach($this->events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filament::section>

            @if($this->selectedEvent)
                <div class="participant-test-grid">
                    @foreach($this->assessments as $assessment)
                        @php $attempt = $this->attemptFor($assessment->id); @endphp
                        <div class="participant-test-card">
                            <x-filament::section>
                            <div class="participant-test-body">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-primary-600">
                                            {{ ['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test'][$assessment->type] ?? $assessment->type }}
                                        </div>
                                        <h3 class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $assessment->title }}</h3>
                                        <p class="mt-1 text-sm text-gray-500">{{ $this->questionsFor($assessment)->count() }} soal pilihan ganda</p>
                                    </div>
                                    @if($attempt)
                                        <x-filament::badge color="success">Selesai</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning">Belum isi</x-filament::badge>
                                    @endif
                                </div>

                                @if($attempt)
                                    <div class="participant-test-result rounded-lg bg-gray-50 p-4 text-sm dark:bg-white/5">
                                        <div class="font-semibold text-gray-950 dark:text-white">Skor: {{ $attempt->score }}</div>
                                        <div class="mt-1 text-gray-500">Benar {{ $attempt->correct_count }}/{{ $attempt->total_questions }} soal, submit {{ $attempt->submitted_at?->format('d M Y H:i') }}</div>
                                    </div>
                                @elseif(! $this->canStartAssessment($assessment))
                                    <div class="rounded-lg p-4 text-sm" style="background-color: rgba(var(--warning-400), 0.12); color: rgb(var(--warning-700));">
                                        {{ $this->assessmentLockReason($assessment) }}
                                    </div>
                                @else
                                    <x-filament::button wire:click="startAssessment({{ $assessment->id }})" icon="heroicon-o-pencil-square">
                                        Kerjakan {{ ['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test'][$assessment->type] ?? 'Tes' }}
                                    </x-filament::button>
                                @endif
                            </div>
                            </x-filament::section>
                        </div>
                    @endforeach
                </div>

                @if($this->activeAssessment)
                    <x-filament::section>
                        <x-slot name="heading">{{ $this->activeAssessment->title }}</x-slot>

                        <form wire:submit.prevent="submitAssessment({{ $this->activeAssessment->id }})" class="space-y-6">
                            @foreach($this->questionsFor($this->activeAssessment) as $questionIndex => $question)
                                <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $questionIndex + 1 }}. {{ $question['question'] ?? '-' }}</div>
                                    <div class="mt-4 space-y-3">
                                        @foreach($this->displayOptions($this->activeAssessment, $questionIndex, $question) as $optionIndex => $option)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                                                <input type="radio" wire:model="answers.{{ $questionIndex }}" value="{{ $optionIndex }}" class="mt-1 border-gray-300 text-primary-600">
                                                <span>{{ $option['text'] ?? '-' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <div class="flex justify-end gap-3">
                                <x-filament::button type="button" color="gray" wire:click="$set('activeAssessmentId', null)">Batal</x-filament::button>
                                <x-filament::button type="submit" icon="heroicon-o-paper-airplane">Submit Jawaban</x-filament::button>
                            </div>
                        </form>
                    </x-filament::section>
                @endif

                @php $certificate = $this->certificate; @endphp
                <x-filament::section>
                    <x-slot name="heading">Sertifikat</x-slot>
                    @if($certificate?->isEligible() && ! $certificate->isIssued() && $certificate->status !== 'revoked')
                        <div class="rounded-lg p-4 text-sm" style="background-color: rgba(var(--success-400), 0.12); color: rgb(var(--success-700));">
                            <div class="font-semibold">Syarat sertifikat sudah lengkap.</div>
                            <div class="mt-1">Sertifikat nomor {{ $certificate->number }} sedang menunggu penerbitan oleh admin. Tombol unduh akan muncul setelah sertifikat diterbitkan.</div>
                        </div>
                    @elseif($certificate?->isIssued())
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="font-semibold text-gray-950 dark:text-white">Sertifikat sudah bisa dicetak</div>
                                <div class="mt-1 text-sm text-gray-500">Nomor: {{ $certificate->number }}</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-filament::button tag="a" href="{{ route('certificates.preview-pdf', $certificate) }}" target="_blank" icon="heroicon-o-eye">
                                    Preview PDF
                                </x-filament::button>
                                <x-filament::button tag="a" href="{{ route('certificates.download-pdf', $certificate) }}" color="success" icon="heroicon-o-arrow-down-tray">
                                    Download PDF
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg p-4 text-sm" style="background-color: rgba(var(--warning-400), 0.12); color: rgb(var(--warning-700));">
                            <div class="font-semibold">Sertifikat belum bisa dicetak.</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @forelse($this->certificateNotes as $note)
                                    <li>{{ $note }}</li>
                                @empty
                                    <li>Selesaikan pre-test, kuis modul, dan post-test terlebih dahulu.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endif
                </x-filament::section>
            @endif
        </div>
    @endif
</x-filament-panels::page>
