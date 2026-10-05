<?php

namespace App\Filament\Resources\TrainingSessionResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\TrainingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTrainingSession extends ManageRecords
{
    protected static string $resource = TrainingSessionResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat jadwal sesi di lokasinya.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        // Jadwal sesi tercatat per lokasi; tampilkan sesi di lokasi event.
        return $query->where('school_id', $event->school_id ?? 0);
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $sessions = $this->eventRecords($event)->get(['status']);
        $done = $sessions->where('status', 'done')->count();

        return [
            'badges' => [[$sessions->count() . ' sesi', 'gray'], [$done . ' selesai', 'success']],
            'progress' => $sessions->isEmpty() ? null : ['label' => 'Sesi selesai', 'done' => $done, 'total' => $sessions->count()],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
