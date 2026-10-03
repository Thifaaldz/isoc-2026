<?php

namespace App\Filament\Resources\ModuleTemplateResource\Pages;

use App\Filament\Resources\ModuleTemplateResource;
use App\Services\ModuleTemplateMeetingSyncer;
use App\Services\TutorMaterialMirror;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModuleTemplate extends EditRecord
{
    protected static string $resource = ModuleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(ModuleTemplateMeetingSyncer::class)->sync($this->record);
        app(TutorMaterialMirror::class)->ensureFor($this->record);
    }
}
