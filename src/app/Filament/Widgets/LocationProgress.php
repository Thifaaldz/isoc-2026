<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use App\Support\EventOverview;
use Filament\Widgets\Widget;

/** Dashboard Admin RTIK Pusat: progres ringkas setiap lokasi (peserta, kehadiran, tes, bukti laporan, status). */
class LocationProgress extends Widget
{
    protected static string $view = 'filament.widgets.location-progress';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -6;

    public string $filter = 'all';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    protected function getViewData(): array
    {
        $rows = LearningEvent::query()
            ->with(['school', 'payments', 'tutors', 'meetings', 'orderedPartners', 'assessments'])
            ->orderBy('starts_at')
            ->get()
            ->map(function (LearningEvent $event): array {
                $overview = new EventOverview($event);
                $checklist = collect($overview->finalReportChecklist());

                return [
                    'event' => $event,
                    'overview' => $overview,
                    'status' => $overview->status(),
                    'stats' => $overview->stats(),
                    'proof_done' => $checklist->where('done', true)->count(),
                    'proof_total' => $checklist->count(),
                    'report' => $overview->finalReportStatus(),
                    'url' => LearningEventResource::getUrl('view', ['record' => $event]),
                    'needs_action' => $event->final_report_status === 'submitted' || $event->workflow_status === 'submitted' || $event->publish_approval_status === 'pending',
                ];
            });

        return [
            'rows' => match ($this->filter) {
                'action' => $rows->where('needs_action', true)->values(),
                'upcoming' => $rows->filter(fn ($row) => ! $row['overview']->isFinished())->values(),
                'finished' => $rows->filter(fn ($row) => $row['overview']->isFinished())->values(),
                default => $rows,
            },
            'counts' => [
                'all' => $rows->count(),
                'action' => $rows->where('needs_action', true)->count(),
                'upcoming' => $rows->filter(fn ($row) => ! $row['overview']->isFinished())->count(),
                'finished' => $rows->filter(fn ($row) => $row['overview']->isFinished())->count(),
            ],
        ];
    }
}
