<?php

namespace App\Filament\Resources\ModuleTemplateResource\Pages;

use App\Filament\Resources\ModuleTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModuleTemplates extends ListRecords
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
