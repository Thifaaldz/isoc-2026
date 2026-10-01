<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Filament\Resources\LearningEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLearningEvents extends ListRecords
{
    protected static string $resource = LearningEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Event'),
        ];
    }
}
