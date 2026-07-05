<?php

namespace App\Filament\Admin\Resources\ManagementMemberResource\Pages;

use App\Filament\Admin\Resources\ManagementMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagementMember extends EditRecord
{
    protected static string $resource = ManagementMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
