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

        if (auth()->user()?->role !== UserRole::SuperAdmin) {
            // Abaikan perubahan field wewenang Pusat walau dikirim lewat request yang dimanipulasi.
            foreach (LearningEventResource::centralOnlyFields() as $field) {
                $data[$field] = $this->record->getAttribute($field);
            }
        }

        if (auth()->user()?->role === UserRole::Admin) {
            $data['local_updated_at'] = now();
            $data['local_update_summary'] = 'Event diperbarui oleh Admin RTIK Daerah.';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        LearningEventResource::syncCertificatePartners($this->record, (array) ($this->data['certificate_partner_ids'] ?? []));

        if ($this->record->wasChanged(['module_template_id', 'selected_meeting_ids'])) {
            app(ModuleTemplateApplier::class)->applyToEvent($this->record);
        }

        app(LearningEventProvisioner::class)->provisionPaymentTerms($this->record);

        // Event yang sudah disetujui: tutor terdaftar yang baru dipilih langsung ditugaskan.
        if (! in_array($this->record->workflow_status, ['draft', 'submitted', 'needs_revision', 'cancelled', 'rejected'], true)) {
            app(LearningEventProvisioner::class)->assignSelectedTutors($this->record);
        }
    }
}
