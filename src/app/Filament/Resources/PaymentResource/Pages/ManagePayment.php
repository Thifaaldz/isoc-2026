<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayment extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadRabTemplate')
                ->label('Template RAB')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('import-templates.rab'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
