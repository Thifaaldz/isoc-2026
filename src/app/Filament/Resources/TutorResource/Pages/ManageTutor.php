<?php

namespace App\Filament\Resources\TutorResource\Pages;

use App\Filament\Resources\TutorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTutor extends ManageRecords
{
    protected static string $resource = TutorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
