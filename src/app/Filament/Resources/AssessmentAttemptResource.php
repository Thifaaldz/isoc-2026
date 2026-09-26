<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AssessmentAttemptResource\Pages;
use App\Models\AssessmentAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentAttemptResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = AssessmentAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Pembelajaran';

    protected static ?string $modelLabel = 'Hasil Tes';

    protected static ?string $pluralModelLabel = 'Hasil Tes & KPI';

    protected static ?int $navigationSort = 3;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor, UserRole::Peserta];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return 'participant';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('assessment_id')->label('Tes')->relationship('assessment', 'title')->required(),
            auth()->user()?->role === UserRole::Peserta
                ? Forms\Components\Hidden::make('participant_id')->default(fn () => auth()->user()->participant?->id)
                : Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => \App\Models\Participant::with('user')->get()->pluck('user.name', 'id'))->searchable()->required(),
            Forms\Components\TextInput::make('score')->label('Skor')->numeric(),
            Forms\Components\TextInput::make('threat_identification')->label('Identifikasi ancaman (%)')->numeric(),
            Forms\Components\TextInput::make('self_efficacy')->label('Self-efficacy')->numeric(),
            Forms\Components\DateTimePicker::make('submitted_at')->label('Waktu submit')->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('assessment.title')->label('Tes')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('score')->label('Skor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('threat_identification')->label('Identifikasi (%)')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('self_efficacy')->label('Self-efficacy')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')->label('Submit')->searchable()->sortable()->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAssessmentAttempt::route('/'),
        ];
    }
}
