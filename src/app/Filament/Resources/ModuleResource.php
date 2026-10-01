<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ModuleResource\Pages;
use App\Models\Module;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ModuleResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Module::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Pembelajaran';

    protected static ?string $modelLabel = 'Modul OTS';

    protected static ?string $pluralModelLabel = 'Modul OTS';

    protected static ?int $navigationSort = 4;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
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
            Forms\Components\TextInput::make('number')->label('Nomor Modul (1-6)')->required()->numeric()->minValue(1)->maxValue(6)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('title')->label('Judul')->required(),
            Forms\Components\Textarea::make('description')->label('Deskripsi'),
            Forms\Components\RichEditor::make('content')->label('Konten')->columnSpanFull(),
            Forms\Components\TextInput::make('resource_url')->label('Tautan materi/video')->url(),
            Forms\Components\Toggle::make('is_published')->label('Dipublikasikan')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('No')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Publish')->boolean(),
            ])
            ->defaultSort('number')
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
            'index' => Pages\ManageModule::route('/'),
        ];
    }
}
