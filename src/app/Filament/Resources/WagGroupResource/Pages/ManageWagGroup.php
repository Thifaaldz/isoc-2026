<?php

namespace App\Filament\Resources\WagGroupResource\Pages;

use App\Filament\Resources\WagGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWagGroup extends ManageRecords
{
    protected static string $resource = WagGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
