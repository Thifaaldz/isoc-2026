<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\Assessment;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssessmentResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Assessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Konten & Penilaian';

    protected static ?string $navigationLabel = 'Tes & Kuis';

    protected static ?string $modelLabel = 'Tes & Kuis';

    protected static ?string $pluralModelLabel = 'Tes & Kuis';

    protected static ?int $navigationSort = 3;

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
        return 'assessment';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Jenis')
                ->options(['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test'])
                ->default(fn () => request()->query('type'))
                ->required(),
            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->default(fn () => match (request()->query('type')) {
                    'pre' => 'Pre-Test',
                    'post' => 'Post-Test',
                    'quiz' => 'Kuis Modul',
                    default => null,
                })
                ->required(),
            Forms\Components\Select::make('module_template_id')
                ->label('Materi Event')
                ->options(fn () => ModuleTemplate::query()->where('is_active', true)->orderBy('audience')->orderBy('name')->get()->mapWithKeys(fn (ModuleTemplate $template) => [$template->id => $template->name . ' (' . (ModuleTemplate::AUDIENCES[$template->audience] ?? $template->audience) . ')']))
                ->default(fn () => request()->integer('module_template_id') ?: null)
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Isi untuk Pre-Test/Post-Test template. Untuk kuis modul, pilih juga pertemuannya.'),
            Forms\Components\Select::make('learning_meeting_id')
                ->label('Pertemuan')
                ->default(fn () => request()->integer('learning_meeting_id') ?: null)
                ->options(fn () => LearningMeeting::query()
                    ->with('moduleTemplate')
                    ->whereNotNull('module_template_id')
                    ->whereDoesntHave('moduleTemplate', fn ($template) => $template->whereNotNull('source_template_id'))
                    ->orderBy('module_template_id')
                    ->orderBy('order')
                    ->get()
                    ->mapWithKeys(fn (LearningMeeting $meeting) => [$meeting->id => ($meeting->moduleTemplate?->name ?? 'Materi Event') . ' - ' . $meeting->title]))
                ->searchable(),
            Forms\Components\TextInput::make('form_url')->label('Tautan eksternal (opsional)')->url(),
            Forms\Components\TextInput::make('passing_score')->label('Nilai lulus')->numeric()->default(85),
            Forms\Components\TextInput::make('questions_per_attempt')
                ->label('Jumlah soal per peserta')
                ->numeric()
                ->minValue(1)
                ->maxValue(100)
                ->helperText('Opsional. Isi mis. 5 agar setiap peserta mendapat 5 soal acak dari bank soal. Kosongkan untuk memakai semua soal. Urutan soal selalu diacak per peserta.'),
            Forms\Components\Toggle::make('is_open')->label('Dibuka')->default(true),
            Forms\Components\Repeater::make('questions')
                ->label('Soal pilihan ganda')
                ->schema([
                    Forms\Components\Textarea::make('question')->label('Pertanyaan')->required()->columnSpanFull(),
                    Forms\Components\Repeater::make('options')
                        ->label('Pilihan jawaban')
                        ->schema([
                            Forms\Components\TextInput::make('text')->label('Jawaban')->required(),
                            Forms\Components\Toggle::make('is_correct')->label('Benar'),
                        ])
                        ->columns(2)
                        ->defaultItems(4)
                        ->minItems(2)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->defaultItems(3)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'Soal')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Jenis')->badge(),
                Tables\Columns\TextColumn::make('meeting.title')->label('Pertemuan')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('questions_count')->label('Soal')->state(fn (Assessment $record) => count(self::normalizeQuestions($record->questions))),
                Tables\Columns\IconColumn::make('is_open')->label('Dibuka')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_meeting_id')
                    ->label('Pertemuan')
                    ->options(fn () => LearningMeeting::query()
                        ->with('moduleTemplate')
                        ->whereNotNull('module_template_id')
                        ->orderBy('module_template_id')
                        ->orderBy('order')
                        ->get()
                        ->mapWithKeys(fn (LearningMeeting $meeting) => [$meeting->id => ($meeting->moduleTemplate?->name ?? 'Materi Event') . ' - ' . $meeting->title])),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Tes')
                    ->options(['pre' => 'Pre-Test', 'quiz' => 'Kuis Modul', 'post' => 'Post-Test']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where(function (Builder $assessmentQuery): void {
                $assessmentQuery
                    ->whereNotNull('module_template_id')
                    ->orWhereHas('meeting', fn (Builder $meetingQuery) => $meetingQuery->whereNotNull('module_template_id'));
            });

        $templateId = request()->integer('materi_event');

        return $templateId
            ? $query->where(function (Builder $assessmentQuery) use ($templateId): void {
                $assessmentQuery
                    ->where('module_template_id', $templateId)
                    ->orWhereHas('meeting', fn (Builder $meetingQuery) => $meetingQuery->where('module_template_id', $templateId));
            })
            : $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAssessment::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }

    private static function normalizeQuestions(mixed $questions): array
    {
        if (is_array($questions)) {
            return $questions;
        }

        if (is_string($questions) && $questions !== '') {
            $decoded = json_decode($questions, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
