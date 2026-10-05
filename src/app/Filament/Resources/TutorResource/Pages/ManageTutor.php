<?php

namespace App\Filament\Resources\TutorResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\TutorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTutor extends ManageRecords
{
    protected static string $resource = TutorResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat tutor yang ditugaskan.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->whereHas('learningEvents', fn (Builder $events) => $events->where('learning_events.id', $event->id));
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $tutors = $this->eventRecords($event)->get(['tot_completed']);
        $passed = $tutors->where('tot_completed', true)->count();

        return [
            'badges' => [[$tutors->count() . ' tutor', 'gray'], [$passed . ' lulus ToT', $passed && $passed >= $tutors->count() ? 'success' : 'warning']],
            'progress' => ['label' => 'Tutor lulus ToT', 'done' => $passed, 'total' => $tutors->count()],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
