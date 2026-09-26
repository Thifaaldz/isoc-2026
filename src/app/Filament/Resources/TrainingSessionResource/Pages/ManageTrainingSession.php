<?php

namespace App\Filament\Resources\TrainingSessionResource\Pages;

use App\Filament\Resources\TrainingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTrainingSession extends ManageRecords
{
    protected static string $resource = TrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
