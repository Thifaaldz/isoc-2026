<?php

namespace App\Filament\Resources\TotAssessmentResource\Pages;

use App\Filament\Resources\TotAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTotAssessment extends ManageRecords
{
    protected static string $resource = TotAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
