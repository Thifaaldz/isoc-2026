<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\TrainingSession;
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

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'Absensi';

    protected static ?string $pluralModelLabel = 'Absensi';

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
            Forms\Components\Select::make('training_session_id')->label('Sesi')->options(fn () => static::scopedTrainingSessionOptions())->searchable()->required(),
            Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => static::scopedParticipantOptions())->searchable()->nullable(),
            Forms\Components\Select::make('tutor_id')->label('Tutor')->options(fn () => static::scopedTutorOptions())->searchable()->nullable(),
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
            ->filters([
                Tables\Filters\SelectFilter::make('training_session_id')
                    ->label('Sesi')
                    ->options(fn () => static::scopedTrainingSessionOptions())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('session', fn ($sessionQuery) => $sessionQuery->where('school_id', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa']),
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
