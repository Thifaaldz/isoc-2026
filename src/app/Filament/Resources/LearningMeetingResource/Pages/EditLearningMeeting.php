<?php

namespace App\Filament\Resources\LearningMeetingResource\Pages;

use App\Filament\Resources\LearningMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLearningMeeting extends EditRecord
{
    protected static string $resource = LearningMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
