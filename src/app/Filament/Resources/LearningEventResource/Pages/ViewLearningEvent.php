<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

/** Preview event: wizard yang sama dengan form tambah/edit, tetapi hanya untuk dibaca. */
class ViewLearningEvent extends ViewRecord
{
    protected static string $resource = LearningEventResource::class;

    public function getTitle(): string
    {
        return 'Preview: ' . $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        // Tombol approve/revisi/publish yang sama dengan tabel Kelola Seminar; status di wizard ikut diperbarui.
        $workflowActions = array_map(
            fn (Actions\Action $action) => $action->after(fn () => $this->refreshFormData([
                'workflow_status',
                'publish_approval_status',
                'status',
                'registration_open',
                'is_published',
            ])),
            LearningEventResource::workflowActions(Actions\Action::class),
        );

        return [
            ...$workflowActions,
            Actions\EditAction::make()
                ->color('gray')
                ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin
                    || (auth()->user()?->role === UserRole::Admin && ! in_array($this->record->workflow_status, ['cancelled', 'closed'], true))),
        ];
    }
}
