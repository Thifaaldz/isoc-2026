<?php

namespace App\Filament\Admin\Resources\ManagementMemberResource\Pages;

use App\Filament\Admin\Resources\ManagementMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManagementMembers extends ListRecords
{
    protected static string $resource = ManagementMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
