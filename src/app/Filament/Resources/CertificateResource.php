<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?string $modelLabel = 'e-Certificate';

    protected static ?string $pluralModelLabel = 'e-Certificate';

    protected static ?int $navigationSort = 3;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Peserta];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return 'participant';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            auth()->user()?->role === UserRole::Peserta
                ? Forms\Components\Hidden::make('participant_id')->default(fn () => auth()->user()->participant?->id)
                : Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => \App\Models\Participant::with('user')->get()->pluck('user.name', 'id'))->searchable()->required(),
            Forms\Components\TextInput::make('number')->label('Nomor sertifikat')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('status')->label('Status')->options(['pending' => 'Menunggu', 'issued' => 'Terbit', 'revoked' => 'Dicabut'])->default('pending'),
            Forms\Components\DateTimePicker::make('issued_at')->label('Tanggal terbit'),
            Forms\Components\FileUpload::make('file_path')->label('Berkas PDF')->directory('certificates'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('issued_at')->label('Terbit')->searchable()->sortable()->dateTime(),
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
            'index' => Pages\ManageCertificate::route('/'),
        ];
    }
}
