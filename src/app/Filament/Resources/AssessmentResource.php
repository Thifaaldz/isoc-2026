<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\Assessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Pembelajaran';

    protected static ?string $modelLabel = 'Pre/Post-Test';

    protected static ?string $pluralModelLabel = 'Pre/Post-Test';

    protected static ?int $navigationSort = 2;

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
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->label('Jenis')->options(['pre' => 'Pre-Test', 'post' => 'Post-Test'])->required(),
            Forms\Components\TextInput::make('title')->label('Judul')->required(),
            Forms\Components\TextInput::make('form_url')->label('Tautan formulir daring')->url(),
            Forms\Components\TextInput::make('passing_score')->label('Nilai lulus')->numeric()->default(85),
            Forms\Components\Toggle::make('is_open')->label('Dibuka')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Jenis')->badge(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('form_url')->label('Formulir')->url(fn ($record) => $record->form_url, true)->limit(40),
                Tables\Columns\IconColumn::make('is_open')->label('Dibuka')->boolean(),
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
            'index' => Pages\ManageAssessment::route('/'),
        ];
    }
}
