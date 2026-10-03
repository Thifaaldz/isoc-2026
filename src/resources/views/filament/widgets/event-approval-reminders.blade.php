<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-bell-alert" icon-color="danger">
        <x-slot name="heading">Reminder Approval Event</x-slot>
        <x-slot name="headerEnd">
            @if ($events->isNotEmpty())
                <x-filament::badge color="danger">{{ $events->count() }} event</x-filament::badge>
            @endif
        </x-slot>
        <x-slot name="description">Acara ≤ {{ \App\Filament\Widgets\EventApprovalReminders::DAYS_BEFORE }} hari lagi yang belum di-approve.</x-slot>

        <div style="height: 22rem; overflow-y: auto; margin: -0.5rem -0.25rem; padding: 0 0.25rem;">
            @forelse ($events as $event)
                @php
                    $daysLeft = (int) now()->startOfDay()->diffInDays($event->starts_at->copy()->startOfDay());
                    $awaitingEvent = in_array($event->workflow_status, ['draft', 'submitted', 'needs_revision'], true);
                    $statusLabel = $awaitingEvent
                        ? ($workflowLabels[$event->workflow_status] ?? $event->workflow_status)
                        : ($publishLabels[$event->publish_approval_status] ?? $event->publish_approval_status);
                @endphp
                <a
                    href="{{ $previewUrl($event) }}"
                    style="display: flex; gap: 0.75rem; align-items: center; padding: 0.7rem 0.25rem; {{ $loop->last ? '' : 'border-bottom: 1px solid rgba(148, 163, 184, 0.2);' }}"
                    class="hover:bg-gray-50 dark:hover:bg-white/5"
                >
                    <x-filament::badge color="danger" style="flex-shrink: 0; min-width: 4.25rem; justify-content: center;">
                        {{ $daysLeft === 0 ? 'Hari ini' : 'H-' . $daysLeft }}
                    </x-filament::badge>
                    <div style="min-width: 0; flex: 1;">
                        <div class="text-sm font-semibold text-gray-950 dark:text-white" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $event->title }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400" style="margin-top: 0.15rem;">
                            {{ $event->starts_at->locale('id')->translatedFormat('l, d M Y · H:i') }} WIB · {{ $event->school?->name ?? 'Lokasi belum dipilih' }}
                        </div>
                    </div>
                    <x-filament::badge size="sm" :color="$awaitingEvent ? 'warning' : 'info'" style="flex-shrink: 0;">
                        {{ $awaitingEvent ? '' : 'Publish: ' }}{{ $statusLabel }}
                    </x-filament::badge>
                </a>
            @empty
                <div class="text-sm text-gray-500" style="padding: 1.5rem; text-align: center;">Tidak ada event mendesak yang menunggu approval.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
