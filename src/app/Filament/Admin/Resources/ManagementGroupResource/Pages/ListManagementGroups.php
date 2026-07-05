<?php

namespace App\Filament\Admin\Resources\ManagementGroupResource\Pages;

use App\Filament\Admin\Resources\ManagementGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManagementGroups extends ListRecords
{
    protected static string $resource = ManagementGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
