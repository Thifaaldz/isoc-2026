<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Services\LearningEventProvisioner;
use App\Services\ModuleTemplateApplier;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningEvent extends CreateRecord
{
    protected static string $resource = LearningEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = LearningEventResource::normalizeEventScheduleData($data);

        if (auth()->user()?->role === UserRole::Admin) {
            $data['created_by'] = auth()->id();
            $data['local_updated_at'] = now();
            $data['local_update_summary'] = 'Event baru dibuat oleh Admin RTIK Daerah.';
            $data['is_published'] = false;
            $data['registration_open'] = false;
            $data['publish_approval_status'] = $data['publish_approval_status'] ?? 'draft';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ModuleTemplateApplier::class)->applyToEvent($this->record);
        app(LearningEventProvisioner::class)->provisionPaymentTerms($this->record);
    }
}
