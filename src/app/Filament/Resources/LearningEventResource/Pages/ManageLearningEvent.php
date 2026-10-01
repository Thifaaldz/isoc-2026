<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use App\Services\LearningEventProvisioner;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLearningEvent extends ManageRecords
{
    protected static string $resource = LearningEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(fn (LearningEvent $record) => app(LearningEventProvisioner::class)->provisionPaymentTerms($record)),
        ];
    }
}
