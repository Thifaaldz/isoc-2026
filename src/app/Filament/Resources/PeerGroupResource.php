<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\PeerGroupResource\Pages;
use App\Models\PeerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PeerGroupResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = PeerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Komunitas';

    protected static ?string $modelLabel = 'Kelompok Sebaya';

    protected static ?string $pluralModelLabel = 'Kelompok Sebaya';

    protected static ?int $navigationSort = 2;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor, UserRole::Peserta];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function scopeType(): ?string
    {
        return 'school';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')->label('Sekolah')->relationship('school', 'name')->searchable()->preload()->required()->default(fn () => auth()->user()?->school_id),
            Forms\Components\Select::make('tutor_id')->label('Tutor pendamping')->options(fn () => \App\Models\Tutor::with('user')->get()->pluck('user.name', 'id'))->searchable()->nullable(),
            Forms\Components\TextInput::make('name')->label('Nama kelompok')->required(),
            Forms\Components\TextInput::make('member_count')->label('Jumlah anggota')->numeric()->default(0),
            Forms\Components\Select::make('status')->label('Status')->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Kelompok')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tutor.user.name')->label('Tutor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('member_count')->label('Anggota')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
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
            'index' => Pages\ManagePeerGroup::route('/'),
        ];
    }
}
