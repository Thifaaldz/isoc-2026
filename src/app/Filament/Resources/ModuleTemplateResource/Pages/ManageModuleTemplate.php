<?php

namespace App\Filament\Resources\ModuleTemplateResource\Pages;

use App\Filament\Resources\ModuleTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageModuleTemplate extends ManageRecords
{
    protected static string $resource = ModuleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Materi Event'),
        ];
    }
}
