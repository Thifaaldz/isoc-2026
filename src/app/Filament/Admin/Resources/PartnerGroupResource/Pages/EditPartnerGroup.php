<?php

namespace App\Filament\Admin\Resources\PartnerGroupResource\Pages;

use App\Filament\Admin\Resources\PartnerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartnerGroup extends EditRecord
{
    protected static string $resource = PartnerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
