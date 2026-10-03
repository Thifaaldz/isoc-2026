@php
    $workflowColor = fn (?string $status) => match ($status) {
        'submitted' => 'warning',
        'needs_revision' => 'danger',
        'draft' => 'gray',
        'cancelled', 'rejected' => 'danger',
        default => 'success',
    };
    $publishColor = fn (?string $status) => match ($status) {
        'published' => 'success',
        'pending' => 'warning',
        'revision' => 'danger',
        default => 'gray',
    };
@endphp
<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-arrow-path" icon-color="primary">
        <x-slot name="heading">Update Event dari Admin RTIK Daerah</x-slot>
        <x-slot name="description">Event baru, revisi, dan pengajuan publish yang perlu dicek.</x-slot>

        <div style="height: 22rem; overflow-y: auto; margin: -0.5rem -0.25rem; padding: 0 0.25rem;">
            @forelse ($events as $event)
                <a
                    href="{{ $previewUrl($event) }}"
                    style="display: block; padding: 0.7rem 0.25rem; {{ $loop->last ? '' : 'border-bottom: 1px solid rgba(148, 163, 184, 0.2);' }}"
                    class="hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <div style="display: flex; gap: 0.75rem; align-items: baseline; justify-content: space-between;">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white" style="min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $event->title }}</div>
                        <span class="text-xs text-gray-500 dark:text-gray-400" style="flex-shrink: 0;">{{ ($event->local_updated_at ?? $event->updated_at)?->locale('id')->diffForHumans() }}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400" style="margin-top: 0.15rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $event->school?->name ?? 'Lokasi belum dipilih' }}@if ($event->creator) · {{ $event->creator->name }}@endif
                        @if ($event->local_update_summary) · {{ $event->local_update_summary }}@endif
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.4rem;">
                        <x-filament::badge size="sm" :color="$workflowColor($event->workflow_status)">{{ $workflowLabels[$event->workflow_status] ?? $event->workflow_status }}</x-filament::badge>
                        <x-filament::badge size="sm" :color="$publishColor($event->publish_approval_status)">Publish: {{ $publishLabels[$event->publish_approval_status ?? 'draft'] ?? $event->publish_approval_status }}</x-filament::badge>
                    </div>
                </a>
            @empty
                <div class="text-sm text-gray-500" style="padding: 1.5rem; text-align: center;">Belum ada update event dari Admin RTIK Daerah.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
