<?php

namespace App\Filament\Resources\PeerGroupResource\Pages;

use App\Filament\Resources\PeerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePeerGroup extends ManageRecords
{
    protected static string $resource = PeerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
