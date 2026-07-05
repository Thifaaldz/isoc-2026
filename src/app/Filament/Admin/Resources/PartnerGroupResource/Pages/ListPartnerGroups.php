<?php

namespace App\Filament\Admin\Resources\PartnerGroupResource\Pages;

use App\Filament\Admin\Resources\PartnerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerGroups extends ListRecords
{
    protected static string $resource = PartnerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
