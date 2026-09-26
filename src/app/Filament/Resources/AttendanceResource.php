<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operasional';

    protected static ?string $modelLabel = 'Kehadiran';

    protected static ?string $pluralModelLabel = 'Kehadiran';

    protected static ?int $navigationSort = 2;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function scopeType(): ?string
    {
        return 'session';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('training_session_id')->label('Sesi')->relationship('session', 'title')->required(),
            Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => \App\Models\Participant::with('user')->get()->pluck('user.name', 'id'))->searchable()->nullable(),
            Forms\Components\Select::make('tutor_id')->label('Tutor')->options(fn () => \App\Models\Tutor::with('user')->get()->pluck('user.name', 'id'))->searchable()->nullable(),
            Forms\Components\Select::make('status')->label('Status')->options(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'])->required()->default('hadir'),
            Forms\Components\Hidden::make('recorded_by')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.title')->label('Sesi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta'),
                Tables\Columns\TextColumn::make('tutor.user.name')->label('Tutor'),
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
            'index' => Pages\ManageAttendance::route('/'),
        ];
    }
}
