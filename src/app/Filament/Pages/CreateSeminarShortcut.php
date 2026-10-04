<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use Filament\Pages\Page;

class CreateSeminarShortcut extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationGroup = 'Seminar';

    protected static ?string $navigationLabel = 'Add Seminar';

    protected static ?string $title = 'Add Seminar';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.redirecting';

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && LearningEventResource::canCreate();
    }

    public function mount(): void
    {
        $this->redirect(LearningEventResource::getUrl('create'), navigate: false);
    }
}
