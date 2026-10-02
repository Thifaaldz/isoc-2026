<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\MicrositePracticeResource\Pages;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MicrositePracticeResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = MicrositePractice::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Tugas Peserta';

    protected static ?string $modelLabel = 'Praktik Microsite';

    protected static ?string $pluralModelLabel = 'Praktik Microsite';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Peserta];
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
                : Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => static::scopedParticipantOptions())->searchable()->required(),
            Forms\Components\Select::make('learning_event_id')
                ->label('Event')
                ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('sid_url')->label('Tautan s.id / microsite')->required()->url(),
            Forms\Components\Textarea::make('notes')->label('Catatan'),
            Forms\Components\Select::make('status')->label('Status')->options(['submitted' => 'Dikumpulkan', 'reviewed' => 'Ditinjau'])->default(fn () => auth()->user()?->role === UserRole::Peserta ? 'reviewed' : 'submitted'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('sid_url')->label('Tautan')->url(fn ($record) => $record->sid_url, true),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null) ? $query->where('learning_event_id', $data['value']) : $query),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['submitted' => 'Dikumpulkan', 'reviewed' => 'Ditinjau']),
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
            'index' => Pages\ManageMicrositePractice::route('/'),
        ];
    }
}
