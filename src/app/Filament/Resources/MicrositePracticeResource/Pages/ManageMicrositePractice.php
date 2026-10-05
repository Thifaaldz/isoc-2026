<?php

namespace App\Filament\Resources\MicrositePracticeResource\Pages;

use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\MicrositePracticeResource;
use App\Models\LearningEvent;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageMicrositePractice extends ManageRecords
{
    protected static string $resource = MicrositePracticeResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat link praktik microsite peserta.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->where('learning_event_id', $event->id);
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $submitted = $this->eventRecords($event)
            ->whereNotNull('sid_url')
            ->where('sid_url', '!=', '')
            ->distinct()
            ->count('participant_id');
        $participants = $event->participants()->count();

        return [
            'badges' => array_values(array_filter([
                [$submitted . ' link s.id', $submitted ? 'success' : 'gray'],
                [$participants . ' peserta', 'gray'],
                $participants - $submitted > 0 ? [($participants - $submitted) . ' belum', 'warning'] : null,
            ])),
            'progress' => ['label' => 'Peserta sudah praktik microsite', 'done' => $submitted, 'total' => $participants],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
