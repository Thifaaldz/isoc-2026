<?php

namespace App\Filament\Pages;

class ParticipantTests extends ParticipantLearning
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Seminar & Materi';

    protected static ?string $navigationLabel = 'Tes';

    protected static ?string $title = 'Tes';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.participant-tests';
}
