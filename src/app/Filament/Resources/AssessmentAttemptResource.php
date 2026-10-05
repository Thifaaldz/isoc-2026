<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AssessmentAttemptResource\Pages;
use App\Models\AssessmentAttempt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentAttemptResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = AssessmentAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Penilaian';

    protected static ?string $modelLabel = 'Nilai Siswa & Progress';

    protected static ?string $pluralModelLabel = 'Nilai Siswa & Progress';

    protected static ?int $navigationSort = 1;

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
        return 'participant';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('assessment_id')->label('Tes')->options(fn () => static::scopedAssessmentOptions())->searchable()->required(),
            auth()->user()?->role === UserRole::Peserta
                ? Forms\Components\Hidden::make('participant_id')->default(fn () => auth()->user()->participant?->id)
                : Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => static::scopedParticipantOptions())->searchable()->required(),
            Forms\Components\TextInput::make('score')->label('Skor')->numeric(),
            Forms\Components\TextInput::make('correct_count')->label('Jawaban benar')->numeric(),
            Forms\Components\TextInput::make('total_questions')->label('Jumlah soal')->numeric(),
            Forms\Components\TextInput::make('threat_identification')->label('Identifikasi ancaman (%)')->numeric(),
            Forms\Components\TextInput::make('self_efficacy')->label('Self-efficacy')->numeric(),
            Forms\Components\DateTimePicker::make('submitted_at')->label('Waktu submit')->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('assessment.learningEvent.title')->label('Event')->searchable(),
                Tables\Columns\TextColumn::make('assessment.title')->label('Tes')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('assessment.type')->label('Jenis')->badge(),
                Tables\Columns\TextColumn::make('score')->label('Skor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('correct_count')->label('Benar')->formatStateUsing(fn ($state, $record) => $state . '/' . $record->total_questions),
                Tables\Columns\TextColumn::make('threat_identification')->label('Identifikasi (%)')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('self_efficacy')->label('Self-efficacy')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')->label('Submit')->searchable()->sortable()->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Tes')
                    ->options(['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test'])
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('assessment', fn ($assessmentQuery) => $assessmentQuery->where('type', $data['value']))
                        : $query),
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
            'index' => Pages\ManageAssessmentAttempt::route('/'),
        ];
    }
}
