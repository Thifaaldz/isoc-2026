<?php

namespace App\Filament\Resources\ModuleTemplateResource\Pages;

use App\Filament\Resources\ModuleTemplateResource;
use App\Services\ModuleTemplateMeetingSyncer;
use Filament\Resources\Pages\CreateRecord;

class CreateModuleTemplate extends CreateRecord
{
    protected static string $resource = ModuleTemplateResource::class;

    protected function afterCreate(): void
    {
        app(ModuleTemplateMeetingSyncer::class)->sync($this->record);
    }
}
