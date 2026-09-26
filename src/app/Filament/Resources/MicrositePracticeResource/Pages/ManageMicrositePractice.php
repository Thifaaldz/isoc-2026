<?php

namespace App\Filament\Resources\MicrositePracticeResource\Pages;

use App\Filament\Resources\MicrositePracticeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMicrositePractice extends ManageRecords
{
    protected static string $resource = MicrositePracticeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
