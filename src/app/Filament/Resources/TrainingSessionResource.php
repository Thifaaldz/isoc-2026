<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\TrainingSessionResource\Pages;
use App\Models\TrainingSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrainingSessionResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = TrainingSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'Jadwal Sesi';

    protected static ?string $pluralModelLabel = 'Jadwal Sesi';

    protected static ?int $navigationSort = 1;

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
        return 'school';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')->label('Sekolah')->options(fn () => static::scopedSchoolOptions())->searchable()->preload()->required()->default(fn () => auth()->user()?->school_id),
            Forms\Components\TextInput::make('title')->label('Judul')->required(),
            Forms\Components\DatePicker::make('date')->label('Tanggal')->required(),
            Forms\Components\TimePicker::make('start_time')->label('Mulai')->default('09:00'),
            Forms\Components\TimePicker::make('end_time')->label('Selesai')->default('12:00'),
            Forms\Components\Select::make('status')->label('Status')->options(['planned' => 'Direncanakan', 'running' => 'Berjalan', 'done' => 'Selesai'])->default('planned'),
            Forms\Components\Textarea::make('notes')->label('Catatan simulasi / role-play / refleksi')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->label('Tanggal')->searchable()->sortable()->date(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['planned' => 'Direncanakan', 'running' => 'Berjalan', 'done' => 'Selesai']),
            ])
            ->defaultSort('date')
            ->actions([
                Tables\Actions\Action::make('template')
                    ->label('Absensi Kering')
                    ->icon('heroicon-o-printer')
                    ->url(fn (TrainingSession $record) => route('attendance.template', $record))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ManageTrainingSession::route('/'),
        ];
    }
}
