<x-filament-panels::page>
    <x-filament::section>
        <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Pilih Materi</span>
                <select wire:model.live="source" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-950">
                    @foreach ($this->sourceOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($this->manageUrl())
                <x-filament::button tag="a" :href="$this->manageUrl()" icon="heroicon-o-pencil-square" color="gray">Kelola Pertemuan & Materi</x-filament::button>
            @endif
        </div>
    </x-filament::section>

    @php $selected = $this->selected; @endphp
    @if (! $selected)
        <x-filament::section><p class="text-sm text-gray-500">Belum ada materi yang bisa dipreview.</p></x-filament::section>
    @else
        <x-filament::section>
            <x-slot name="heading">{{ $selected->name }}</x-slot>
            <x-slot name="description">
                {{ \App\Models\ModuleTemplate::AUDIENCES[$selected->audience] ?? $selected->audience }} · {{ $this->meetings->count() }} pertemuan · {{ $this->meetings->sum(fn ($m) => $m->materials->count()) }} materi
            </x-slot>

            <div class="space-y-3">
                @forelse ($this->meetings as $meeting)
                    <details class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 p-4">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $meeting->title }}</span>
                            <span class="flex flex-wrap items-center gap-2">
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
                                    <div class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $material->title }}
                                        @unless ($material->is_published)
                                            <x-filament::badge color="warning">Tidak tampil di peserta</x-filament::badge>
                                        @endunless
                                    </div>
                                    <x-material-viewer :material="$material" height="560px" />
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">Belum ada materi pada pertemuan ini.</p>
                            @endforelse
                        </div>
                    </details>
                @empty
                    <p class="text-sm text-gray-500">Belum ada pertemuan.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section collapsible>
            <x-slot name="heading">Tes</x-slot>
            <x-slot name="description">Pre-test, kuis modul, dan post-test pada materi ini.</x-slot>
            <div class="space-y-3">
                @forelse ($this->assessments as $assessment)
                    <details class="rounded-lg border border-gray-200 dark:border-white/10">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 p-3">
                            <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $assessment->title }}</span>
                            <span class="flex items-center gap-2">
                                <x-filament::badge color="info">{{ ['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test'][$assessment->type] ?? $assessment->type }}</x-filament::badge>
                                <x-filament::badge color="gray">{{ count($this->questions($assessment)) }} soal</x-filament::badge>
                            </span>
                        </summary>
                        <ol class="list-decimal space-y-3 border-t border-gray-200 p-4 pl-8 text-sm dark:border-white/10">
                            @foreach ($this->questions($assessment) as $question)
                                <li>
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $question['question'] ?? '-' }}</div>
                                    <ul class="mt-1 space-y-0.5 text-gray-600 dark:text-gray-300">
                                        @foreach (array_values($question['options'] ?? []) as $option)
                                            <li>
                                                {{ chr(65 + $loop->index) }}. {{ $option['text'] ?? '-' }}
                                                @if ($this->canSeeAnswerKey() && ! empty($option['is_correct']))
                                                    <span class="font-semibold" style="color: rgb(var(--success-600));">✓ kunci</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @empty
                    <p class="text-sm text-gray-500">Belum ada tes.</p>
                @endforelse
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
