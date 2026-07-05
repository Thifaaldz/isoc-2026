<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Mitra';

    protected static ?string $modelLabel = 'Mitra';

    protected static ?string $pluralModelLabel = 'Mitra';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('partner_group_id')
                    ->label('Grup Mitra')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama Mitra')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Upload Logo')
                    ->disk('public')
                    ->directory('partners')
                    ->image()
                    ->imageEditor()
                    ->optimize('webp')
                    ->maxSize(2048)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('logo_url')
                    ->label('URL Logo Eksternal')
                    ->helperText('Dipakai jika tidak ada upload logo.')
                    ->url()
                    ->maxLength(2048),
                Forms\Components\TextInput::make('url')
                    ->label('URL Website')
                    ->url()
                    ->maxLength(2048),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(4)
                    ->columnSpanFull(),
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
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Mitra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('Website')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('partner_group_id')
                    ->label('Grup Mitra')
                    ->relationship('group', 'name'),
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
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
