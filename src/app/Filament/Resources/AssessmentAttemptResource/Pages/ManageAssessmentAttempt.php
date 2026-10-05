<?php

namespace App\Filament\Resources\AssessmentAttemptResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\AssessmentAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAssessmentAttempt extends ManageRecords
{
    protected static string $resource = AssessmentAttemptResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat nilai dan progres siswa.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->whereHas('assessment', fn (Builder $assessment) => $assessment->where('learning_event_id', $event->id));
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $done = fn (string $type) => $this->eventRecords($event)
            ->whereHas('assessment', fn (Builder $assessment) => $assessment->where('type', $type))
            ->distinct()
            ->count('participant_id');
        $participants = $event->participants()->count();
        $post = $done('post');

        return [
            'badges' => [['Pre-test ' . $done('pre'), 'info'], ['Post-test ' . $post, 'success'], [$participants . ' peserta', 'gray']],
            'progress' => ['label' => 'Selesai post-test', 'done' => $post, 'total' => $participants],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
