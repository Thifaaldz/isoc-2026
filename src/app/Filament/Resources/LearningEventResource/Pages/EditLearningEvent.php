<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLearningEvent extends EditRecord
{
    protected static string $resource = LearningEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->role === \App\Enums\UserRole::SuperAdmin),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = LearningEventResource::normalizeEventScheduleData($data);

        if (auth()->user()?->role === UserRole::Admin) {
            $data['local_updated_at'] = now();
            $data['local_update_summary'] = 'Event diperbarui oleh Admin RTIK Daerah.';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('module_template_id')) {
            app(ModuleTemplateApplier::class)->applyToEvent($this->record);
        }

        app(LearningEventProvisioner::class)->provisionPaymentTerms($this->record);
    }
}
