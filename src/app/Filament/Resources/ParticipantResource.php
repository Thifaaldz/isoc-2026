<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ParticipantResource\Pages;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParticipantResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Peserta';

    protected static ?string $pluralModelLabel = 'Peserta';

    protected static ?int $navigationSort = 3;

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
        return 'school';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('Akun Pengguna')->relationship('user', 'name', fn ($query) => $query->where('role', 'peserta'))->searchable()->preload()->required(),
            Forms\Components\Select::make('school_id')->label('Sekolah')->relationship('school', 'name')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('nis')->label('NIS'),
            Forms\Components\Select::make('grade')->label('Kelas')->options(['X' => 'X', 'XI' => 'XI', 'XII' => 'XII']),
            Forms\Components\Select::make('gender')->label('Jenis Kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
            Forms\Components\DatePicker::make('birth_date')->label('Tanggal Lahir'),
            Forms\Components\DateTimePicker::make('consent_at')->label('Persetujuan data pribadi (consent)'),
            Forms\Components\Toggle::make('followed_instagram')->label('Follow IG'),
            Forms\Components\Toggle::make('joined_wag')->label('Join WAG'),
            Forms\Components\Toggle::make('registered_ecert')->label('Registrasi e-Certificate'),
            Forms\Components\Toggle::make('is_cadre')->label('Kader'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nis')->label('NIS')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('grade')->label('Kelas')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('followed_instagram')->label('IG')->boolean(),
                Tables\Columns\IconColumn::make('joined_wag')->label('WAG')->boolean(),
                Tables\Columns\IconColumn::make('registered_ecert')->label('e-Cert')->boolean(),
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
            'index' => Pages\ManageParticipant::route('/'),
        ];
    }
}
