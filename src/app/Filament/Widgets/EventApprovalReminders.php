<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Reminder: event yang acaranya tinggal <= 2 hari tetapi belum di-approve RTIK Pusat. */
class EventApprovalReminders extends Widget
{
    public const DAYS_BEFORE = 2;

    protected static string $view = 'filament.widgets.event-approval-reminders';

    protected int | string | array $columnSpan = ['default' => 'full', 'lg' => 1];

    protected static ?int $sort = -5;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    /** @return Collection<int, LearningEvent> */
    public static function dueEvents(): Collection
    {
        return LearningEvent::query()
            ->with(['school', 'creator'])
            ->whereBetween('starts_at', [now(), now()->addDays(self::DAYS_BEFORE)->endOfDay()])
            ->where(fn (Builder $query) => $query
                // Event belum di-approve (Termin-1) atau pengajuan publish belum disetujui.
                ->whereIn('workflow_status', ['draft', 'submitted', 'needs_revision'])
                ->orWhereIn('publish_approval_status', ['pending', 'revision']))
            ->whereNotIn('workflow_status', ['cancelled', 'rejected', 'closed'])
            ->orderBy('starts_at')
            ->get();
    }

    protected function getViewData(): array
    {
        return [
            'events' => static::dueEvents(),
            'previewUrl' => fn (LearningEvent $event): string => LearningEventResource::getUrl('view', ['record' => $event]),
            'workflowLabels' => LearningEventResource::workflowStatusOptions(),
            'publishLabels' => LearningEventResource::publishApprovalStatusOptions(),
        ];
    }
}
