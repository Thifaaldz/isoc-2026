<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ManagementMemberResource\Pages;
use App\Models\ManagementMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ManagementMemberResource extends Resource
{
    protected static ?string $model = ManagementMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Anggota Pengurus';

    protected static ?string $modelLabel = 'Anggota Pengurus';

    protected static ?string $pluralModelLabel = 'Anggota Pengurus';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('management_group_id')
                    ->label('Grup Pengurus')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->label('Jabatan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('photo_path')
                    ->label('Upload Foto')
                    ->disk('public')
                    ->directory('management-members')
                    ->image()
                    ->imageEditor()
                    ->optimize('webp')
                    ->maxSize(2048)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('photo_url')
                    ->label('URL Foto Eksternal')
                    ->helperText('Dipakai jika tidak ada upload foto.')
                    ->url()
                    ->maxLength(2048),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('linkedin_url')
                    ->label('LinkedIn URL')
                    ->url()
                    ->maxLength(2048),
                Forms\Components\Textarea::make('bio')
                    ->label('Bio')
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
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.name')
                    ->label('Grup')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('management_group_id')
                    ->label('Grup Pengurus')
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
            'index' => Pages\ListManagementMembers::route('/'),
            'create' => Pages\CreateManagementMember::route('/create'),
            'edit' => Pages\EditManagementMember::route('/{record}/edit'),
        ];
    }
}
