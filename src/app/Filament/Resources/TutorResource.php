<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\TutorResource\Pages;
use App\Models\Tutor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TutorResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Tutor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'Tutor';

    protected static ?string $pluralModelLabel = 'Tutor';

    protected static ?int $navigationSort = 4;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return 'tutor_self';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('Akun Pengguna')->options(fn () => static::scopedUserOptions(UserRole::Tutor))->searchable()->preload()->required(),
            Forms\Components\Select::make('school_id')->label('Sekolah')->options(fn () => static::scopedSchoolOptions())->searchable()->preload()->required(),
            Forms\Components\TextInput::make('institution')->label('Institusi'),
            Forms\Components\TextInput::make('nik')->label('NIK')->maxLength(50),
            Forms\Components\TextInput::make('npwp')->label('NPWP')->maxLength(30),
            Forms\Components\TextInput::make('bank_name')->label('Bank')->maxLength(100),
            Forms\Components\TextInput::make('bank_account_number')->label('Nomor Rekening')->maxLength(50),
            Forms\Components\Toggle::make('tot_completed')->label('ToT selesai'),
            Forms\Components\Toggle::make('is_cadre')->label('Kader pelatih mandiri'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('institution')->label('Institusi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('npwp')->label('NPWP')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank')->toggleable(),
                Tables\Columns\TextColumn::make('bank_account_number')->label('No. Rekening')->toggleable(),
                Tables\Columns\IconColumn::make('tot_completed')->label('ToT')->boolean(),
                Tables\Columns\IconColumn::make('is_cadre')->label('Kader')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('tot_completed')
                    ->label('ToT selesai'),
                Tables\Filters\TernaryFilter::make('is_cadre')
                    ->label('Kader'),
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
            'index' => Pages\ManageTutor::route('/'),
        ];
    }
}
