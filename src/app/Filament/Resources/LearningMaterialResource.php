<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\LearningMaterialResource\Pages;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LearningMaterialResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = LearningMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationGroup = 'Konten & Penilaian';

    protected static ?string $modelLabel = 'Materi Seminar';

    protected static ?string $pluralModelLabel = 'Materi Seminar';

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
        return 'learning_material';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('learning_meeting_id')
                ->label('Pertemuan')
                ->default(fn () => request()->integer('learning_meeting_id') ?: null)
                ->options(fn () => static::scopedLearningMeetingOptions(LearningMeeting::query())
                    ->with('event')
                    ->with('moduleTemplate')
                    ->orderBy('learning_event_id')
                    ->orderBy('order')
                    ->get()
                    ->mapWithKeys(fn (LearningMeeting $meeting) => [$meeting->id => ($meeting->event?->title ?? $meeting->moduleTemplate?->name ?? 'Materi Event') . ' - ' . $meeting->title]))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('order')->label('Urutan')->numeric()->default(1)->required(),
            Forms\Components\TextInput::make('title')->label('Judul materi')->required(),
            Forms\Components\Select::make('type')->label('Jenis')->options(LearningMaterial::TYPES)->default('pdf')->required(),
            Forms\Components\FileUpload::make('file_path')->label('Upload file materi')->directory('learning-materials')->acceptedFileTypes([
                'application/pdf',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'video/mp4',
                'video/webm',
                'video/ogg',
            ]),
            Forms\Components\TextInput::make('external_url')->label('Link video / materi')->url(),
            Forms\Components\Toggle::make('is_published')->label('Tampil di peserta')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('meeting.event.title')->label('Event')->searchable(),
                Tables\Columns\TextColumn::make('meeting.moduleTemplate.name')->label('Materi Event')->searchable(),
                Tables\Columns\TextColumn::make('meeting.title')->label('Pertemuan')->searchable(),
                Tables\Columns\TextColumn::make('order')->label('Urutan')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Materi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Jenis')->badge(),
                Tables\Columns\IconColumn::make('is_published')->label('Publish')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('meeting', fn ($meetingQuery) => $meetingQuery->where('learning_event_id', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('learning_meeting_id')
                    ->label('Pertemuan')
                    ->options(fn () => static::scopedLearningMeetingOptions(LearningMeeting::query())
                        ->with('event')
                        ->orderBy('learning_event_id')
                        ->orderBy('order')
                        ->get()
                        ->mapWithKeys(fn (LearningMeeting $meeting) => [$meeting->id => ($meeting->event?->title ?? 'Event') . ' - ' . $meeting->title])),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Materi')
                    ->options(LearningMaterial::TYPES),
            ])
            ->defaultSort('order')
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
            'index' => Pages\ManageLearningMaterial::route('/'),
            'create' => Pages\CreateLearningMaterial::route('/create'),
            'edit' => Pages\EditLearningMaterial::route('/{record}/edit'),
        ];
    }
}
