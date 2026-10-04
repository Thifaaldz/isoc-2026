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
use Illuminate\Database\Eloquent\Builder;

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->role === UserRole::SuperAdmin) {
            return $query;
        }

        // Absensi per sesi (lokasi yang dikelola) atau absensi kode event (event yang bisa diakses).
        return $query->where(fn (Builder $scoped) => $scoped
            ->whereHas('session', fn ($q) => $q->whereIn('school_id', static::managedSchoolIds()))
            ->orWhereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('learning_event_id')->label('Event')->options(fn () => static::scopedLearningEventOptions(\App\Models\LearningEvent::query())->pluck('title', 'id'))->searchable()
                ->requiredWithout('training_session_id'),
            Forms\Components\Select::make('training_session_id')->label('Sesi')->options(fn () => static::scopedTrainingSessionOptions())->searchable()
                ->requiredWithout('learning_event_id'),
            Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => static::scopedParticipantOptions())->searchable()->nullable(),
            Forms\Components\Select::make('tutor_id')->label('Tutor')->options(fn () => static::scopedTutorOptions())->searchable()->nullable(),
            Forms\Components\Select::make('status')->label('Status')->options(['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'])->required()->default('hadir'),
            Forms\Components\Select::make('mode')->label('Kehadiran')->options(['offline' => 'Offline', 'online' => 'Online'])->nullable(),
            Forms\Components\Hidden::make('recorded_by')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable()->placeholder('-'),
                Tables\Columns\TextColumn::make('session.title')->label('Sesi')->searchable()->sortable()->placeholder('-'),
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta'),
                Tables\Columns\TextColumn::make('tutor.user.name')->label('Tutor'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('mode')->label('Kehadiran')->badge()->placeholder('-')
                    ->formatStateUsing(fn (?string $state) => ['offline' => 'Offline', 'online' => 'Online'][$state] ?? $state),
                Tables\Columns\TextColumn::make('method')->label('Metode')
                    ->formatStateUsing(fn (?string $state) => $state === 'kode' ? 'Kode absensi' : 'Input manual'),
                Tables\Columns\TextColumn::make('checked_in_at')->label('Waktu Absen')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(\App\Models\LearningEvent::query())->pluck('title', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('mode')
                    ->label('Kehadiran')
                    ->options(['offline' => 'Offline', 'online' => 'Online']),
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
