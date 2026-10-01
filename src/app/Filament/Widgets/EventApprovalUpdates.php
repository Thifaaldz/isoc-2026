<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use Filament\Widgets\Widget;

class EventApprovalUpdates extends Widget
{
    protected static string $view = 'filament.widgets.event-approval-updates';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    protected function getViewData(): array
    {
        $events = LearningEvent::query()
            ->with(['school', 'creator'])
            ->where(function ($query): void {
                $query
                    ->whereIn('workflow_status', ['submitted', 'needs_revision'])
                    ->orWhereIn('publish_approval_status', ['pending', 'revision'])
                    ->orWhereNotNull('local_updated_at');
            })
            ->orderByRaw('local_updated_at is null')
            ->orderByDesc('local_updated_at')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return [
            'events' => $events,
            'editUrl' => fn (LearningEvent $event): string => LearningEventResource::getUrl('edit', ['record' => $event]),
        ];
    }
}
