<?php

namespace App\Filament\Admin\Resources\ManagementGroupResource\Pages;

use App\Filament\Admin\Resources\ManagementGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagementGroup extends EditRecord
{
    protected static string $resource = ManagementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
