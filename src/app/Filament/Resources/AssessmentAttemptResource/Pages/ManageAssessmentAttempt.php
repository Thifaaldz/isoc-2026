<?php

namespace App\Filament\Resources\AssessmentAttemptResource\Pages;

use App\Filament\Resources\AssessmentAttemptResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAssessmentAttempt extends ManageRecords
{
    protected static string $resource = AssessmentAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
