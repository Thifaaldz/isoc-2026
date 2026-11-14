<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?string $modelLabel = 'Users & Akses';

    protected static ?string $pluralModelLabel = 'Users & Akses';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function scopeType(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\TextInput::make('email')->label('Email')->required()->email()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->label('Password')->password()->revealable()->dehydrated(fn ($state) => filled($state))->required(fn (string $operation) => $operation === 'create')->helperText('Kosongkan saat edit jika tidak diganti.'),
            Forms\Components\Select::make('role')->label('Role')->options(UserRole::options())->required(),
            Forms\Components\Select::make('school_id')->label('Sekolah')->relationship('school', 'name')->searchable()->preload()->nullable(),
            Forms\Components\TextInput::make('phone')->label('Telepon'),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('name')
                    ->label('Nama')
                    ->view('filament.resources.user.name-column')
                    ->searchable(['name', 'email'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === UserRole::Admin ? 'Admin RTIK Daerah' : ($state instanceof UserRole ? $state->label() : $state))
                    ->icon(fn ($state) => match ($state) {
                        UserRole::SuperAdmin => 'heroicon-m-shield-check',
                        UserRole::Admin => 'heroicon-m-map-pin',
                        UserRole::Tutor => 'heroicon-m-presentation-chart-bar',
                        UserRole::Manajemen => 'heroicon-m-chart-pie',
                        default => 'heroicon-m-academic-cap',
                    })
                    ->color(fn ($state) => match ($state) {
                        UserRole::SuperAdmin => 'danger',
                        UserRole::Admin => 'warning',
                        UserRole::Tutor => 'success',
                        UserRole::Manajemen => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi / Sekolah')->placeholder('-')->wrap(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options(UserRole::options()),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
            'index' => Pages\ManageUser::route('/'),
        ];
    }
}
