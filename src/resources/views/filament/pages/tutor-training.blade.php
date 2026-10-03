<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->tutor?->tot_completed)
            <x-filament::section>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">ToT Tutor Sudah Lulus</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">Akun tutor ini sudah eligible untuk mendampingi pelatihan lapangan.</p>
                    </div>
                    <x-filament::badge color="success">Nilai sempurna</x-filament::badge>
                </div>
            </x-filament::section>
        @endif

        <x-filament::section collapsible collapsed>
            <x-slot name="heading">TOR Pelatihan Tutor</x-slot>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Tujuan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Menyiapkan tutor/fasilitator lokal agar mampu menjalankan modul, pre-test, post-test, absensi, dokumentasi, dan pendampingan WAG mentoring sesuai standar DSC.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Kriteria Lulus</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Tutor mempelajari materi ToT, mengerjakan Pre-Test ToT, lalu mendapatkan nilai 100 pada Post-Test ToT sebelum event lapangan dilanjutkan.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Saat Pelatihan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Memimpin ice breaking, menyampaikan modul, mengelola pre/post-test, memandu microsite s.id, simulasi kasus, role-play, open mic, dan refleksi.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Setelah Pelatihan</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Melengkapi foto per sesi, video slogan, absensi basah, bukti dukung, WAG mentoring, dan monitoring KPI peserta bersama admin lokal.</p>
                </div>
            </div>
        </x-filament::section>

        @php $testTemplate = $this->testTemplate; @endphp
        @if ($testTemplate)
            <x-filament::section>
                <x-slot name="heading">Tes ToT: {{ $testTemplate->name }}</x-slot>
                <x-slot name="description">Kerjakan Pre-Test ToT, pelajari materi di bawah, lalu kerjakan Post-Test ToT. Tutor lulus jika nilai Post-Test ToT 100 (boleh diulang).</x-slot>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([$this->preTest, $this->postTest] as $test)
                        @continue(! $test)
                        @php $attempt = $this->testAttempt($test); @endphp
                        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-primary-600">{{ $test->type === 'pre' ? 'Pre-Test ToT' : 'Post-Test ToT' }}</div>
                                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $test->title }}</h3>
                                    <p class="text-sm text-gray-500">{{ count($this->testQuestions($test)) }} soal pilihan ganda</p>
                                </div>
                                @if ($attempt && ($test->type === 'pre' || (float) $attempt->score >= 100))
                                    <x-filament::badge color="success">Selesai</x-filament::badge>
                                @elseif ($attempt)
                                    <x-filament::badge color="warning">Belum sempurna</x-filament::badge>
                                @else
                                    <x-filament::badge color="gray">Belum dikerjakan</x-filament::badge>
                                @endif
                            </div>
                            @if ($attempt)
                                <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
                                    <div class="font-semibold text-gray-950 dark:text-white">Skor: {{ $attempt->score }}</div>
                                    <div class="text-gray-500">Benar {{ $attempt->correct_count }}/{{ $attempt->total_questions }} soal · {{ $attempt->submitted_at?->format('d M Y H:i') }}</div>
                                </div>
                            @endif
                            <div class="mt-auto">
                                @if ($this->canStartTest($test))
                                    <x-filament::button wire:click="startTest({{ $test->id }})" icon="heroicon-o-pencil-square">
                                        {{ $attempt ? 'Ulangi' : 'Kerjakan' }} {{ $test->type === 'pre' ? 'Pre-Test ToT' : 'Post-Test ToT' }}
                                    </x-filament::button>
                                @elseif ($test->type === 'post' && ! $this->testAttempt($this->preTest))
                                    <p class="text-sm" style="color: rgb(var(--warning-700));">Selesaikan Pre-Test ToT terlebih dahulu.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($this->activeTest)
                    <form wire:submit.prevent="submitTest" class="mt-6 space-y-5">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">{{ $this->activeTest->title }}</h3>
                        @foreach ($this->testQuestions($this->activeTest) as $qIndex => $question)
                            <fieldset class="space-y-2 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                                <legend class="px-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $qIndex + 1 }}. {{ $question['question'] ?? '-' }}</legend>
                                @foreach (array_values($question['options'] ?? []) as $oIndex => $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:border-primary-400 dark:border-white/10 dark:text-gray-200">
                                        <input type="radio" wire:model="testAnswers.{{ $qIndex }}" value="{{ $oIndex }}" class="mt-1 border-gray-300 text-primary-600 focus:ring-primary-600">
                                        <span>{{ $option['text'] ?? '-' }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endforeach
                        <div class="flex justify-end gap-3">
                            <x-filament::button type="button" color="gray" wire:click="$set('activeTestId', null)">Batal</x-filament::button>
                            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">Submit Jawaban</x-filament::button>
                        </div>
                    </form>
                @endif
            </x-filament::section>
        @endif

        @forelse ($this->tutorTemplates as $template)
            <x-filament::section>
                <x-slot name="heading">Materi ToT: {{ $template->name }}</x-slot>
                @if ($template->purpose || $template->description)
                    <x-slot name="description">{{ $template->purpose ?: $template->description }}</x-slot>
                @endif
                <div class="space-y-3">
                    @forelse ($template->learningMeetings as $meeting)
                        <details class="group rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900" @if ($loop->first) open @endif>
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 p-4">
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $meeting->title }}</span>
                                <span class="flex items-center gap-2">
                                    @foreach ($meeting->materials as $material)
                                        <x-filament::badge color="gray">{{ strtoupper($material->type) }}</x-filament::badge>
                                    @endforeach
                                </span>
                            </summary>
                            <div class="space-y-4 border-t border-gray-200 p-4 dark:border-white/10">
                                @if ($meeting->description)
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $meeting->description }}</p>
                                @endif
                                @forelse ($meeting->materials as $material)
                                    <div class="space-y-2">
                                        <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $material->title }}</div>
                                        <x-material-viewer :material="$material" height="520px" />
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-500">Belum ada file materi pada pertemuan ini.</p>
                                @endforelse
                            </div>
                        </details>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada pertemuan pada materi ToT ini.</p>
                    @endforelse
                </div>
            </x-filament::section>
        @empty
            <x-filament::section>
                <x-slot name="heading">LMS Modul ToT</x-slot>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ($this->modules as $module)
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $module['title'] }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $module['summary'] }}</p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endforelse

        @unless ($testTemplate)
            <x-filament::section>
                <x-slot name="heading">Cek Pemahaman</x-slot>
                <form wire:submit.prevent="submitTraining" class="space-y-5">
                    @foreach ($this->questions as $index => $question)
                        <fieldset class="space-y-3">
                            <legend class="text-sm font-semibold text-gray-950 dark:text-white">{{ $index + 1 }}. {{ $question['question'] }}</legend>
                            <div class="space-y-2">
                                @foreach ($question['options'] as $option)
                                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:border-primary-400 dark:border-white/10 dark:text-gray-200">
                                        <input type="radio" wire:model="answers.{{ $index }}" value="{{ $option }}" class="mt-1 border-gray-300 text-primary-600 focus:ring-primary-600">
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach

                    <x-filament::button type="submit" icon="heroicon-o-check-circle" class="w-full justify-center">
                        Simpan Kelulusan ToT
                    </x-filament::button>
                </form>
            </x-filament::section>
        @endunless
    </div>
</x-filament-panels::page>
