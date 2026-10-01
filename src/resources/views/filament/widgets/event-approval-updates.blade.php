<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Update Event dari Admin RTIK Daerah</x-slot>
        <x-slot name="description">Pantau event baru, revisi, dan pengajuan publish yang perlu dicek pusat.</x-slot>

        <div class="space-y-3">
            @forelse ($events as $event)
                <a
                    href="{{ $editUrl($event) }}"
                    class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 transition hover:border-primary-500 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-gray-950 dark:text-white">{{ $event->title }}</div>
                            <div class="mt-1 text-sm text-gray-500">
                                {{ $event->school?->name ?? 'Lokasi belum dipilih' }}
                                @if ($event->creator)
                                    · {{ $event->creator->name }}
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {{ strtoupper($event->event_type ?? 'offline') }}
                            </span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 dark:bg-amber-950 dark:text-amber-200">
                                {{ $event->workflow_status }}
                            </span>
                            <span class="rounded-full bg-primary-50 px-3 py-1 text-primary-700 dark:bg-primary-950 dark:text-primary-200">
                                {{ $event->publish_approval_status ?? 'draft' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-2 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-3">
                        <div>
                            <span class="font-semibold">Update daerah:</span>
                            {{ $event->local_updated_at?->diffForHumans() ?? '-' }}
                        </div>
                        <div>
                            <span class="font-semibold">Ringkasan:</span>
                            {{ $event->local_update_summary ?? '-' }}
                        </div>
                        <div>
                            <span class="font-semibold">Catatan publish:</span>
                            {{ $event->publish_revision_notes ?: '-' }}
                        </div>
                    </div>
                </a>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                    Belum ada update event dari Admin RTIK Daerah.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
