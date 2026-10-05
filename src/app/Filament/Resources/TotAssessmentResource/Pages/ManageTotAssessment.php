<?php

namespace App\Filament\Resources\TotAssessmentResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\TotAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTotAssessment extends ManageRecords
{
    protected static string $resource = TotAssessmentResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat hasil ToT tutor.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->where('learning_event_id', $event->id);
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $attempts = $this->eventRecords($event)->get(['tutor_id', 'is_perfect']);
        $perfect = $attempts->where('is_perfect', true)->pluck('tutor_id')->unique()->count();
        // Tutor hanya melihat ToT miliknya sendiri; role lain melihat semua tutor event.
        $tutors = auth()->user()?->role === \App\Enums\UserRole::Tutor ? 1 : $event->tutors()->count();

        return [
            'badges' => [[$attempts->count() . ' hasil ToT', 'gray'], [$perfect . ' tutor sempurna', 'success']],
            'progress' => ['label' => 'Tutor lulus ToT sempurna', 'done' => $perfect, 'total' => $tutors],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
