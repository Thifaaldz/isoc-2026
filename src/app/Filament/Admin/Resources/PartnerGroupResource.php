<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PartnerGroupResource\Pages;
use App\Models\PartnerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerGroupResource extends Resource
{
    protected static ?string $model = PartnerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Grup Mitra';

    protected static ?string $modelLabel = 'Grup Mitra';

    protected static ?string $pluralModelLabel = 'Grup Mitra';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Grup')
                    ->required()
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('partners_count')
                    ->label('Mitra')
                    ->counts('partners')
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
            'index' => Pages\ListPartnerGroups::route('/'),
            'create' => Pages\CreatePartnerGroup::route('/create'),
            'edit' => Pages\EditPartnerGroup::route('/{record}/edit'),
        ];
    }
}
