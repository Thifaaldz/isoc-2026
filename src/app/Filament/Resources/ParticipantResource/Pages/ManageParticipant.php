<?php

namespace App\Filament\Resources\ParticipantResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\ParticipantResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageParticipant extends ManageRecords
{
    protected static string $resource = ParticipantResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat peserta yang terdaftar.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->whereHas('learningEvents', fn (Builder $events) => $events->where('learning_events.id', $event->id));
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $pivots = \Illuminate\Support\Facades\DB::table('learning_event_participant')->where('learning_event_id', $event->id)->get(['admin_approval_status', 'tutor_approval_status']);
        $approved = $pivots->filter(fn ($pivot) => ($pivot->admin_approval_status ?? 'approved') === 'approved' || ($pivot->tutor_approval_status ?? 'approved') === 'approved')->count();
        $pending = $pivots->count() - $approved;

        return [
            'badges' => array_values(array_filter([
                [$pivots->count() . ' peserta', 'gray'],
                $approved ? [$approved . ' disetujui', 'success'] : null,
                $pending ? [$pending . ' menunggu', 'warning'] : null,
            ])),
            'progress' => ['label' => 'Peserta disetujui', 'done' => $approved, 'total' => $pivots->count()],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
