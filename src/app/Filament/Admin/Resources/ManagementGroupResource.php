<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ManagementGroupResource\Pages;
use App\Models\ManagementGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ManagementGroupResource extends Resource
{
    protected static ?string $model = ManagementGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Grup Pengurus';

    protected static ?string $modelLabel = 'Grup Pengurus';

    protected static ?string $pluralModelLabel = 'Grup Pengurus';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Grup')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('layout')
                    ->label('Layout Website')
                    ->options([
                        'grid' => 'Grid kartu',
                        'list' => 'List horizontal',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Grup')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('layout')
                    ->label('Layout')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Anggota')
                    ->counts('members')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManagementGroups::route('/'),
            'create' => Pages\CreateManagementGroup::route('/create'),
            'edit' => Pages\EditManagementGroup::route('/{record}/edit'),
        ];
    }
}
