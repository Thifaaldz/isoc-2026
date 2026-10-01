<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\TotAssessmentResource\Pages;
use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\TotAssessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TotAssessmentResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = TotAssessment::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Penilaian';

    protected static ?string $modelLabel = 'ToT Tutor';

    protected static ?string $pluralModelLabel = 'ToT Tutor';

    protected static ?int $navigationSort = 2;

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
        return 'tot_assessment';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('learning_event_id')
                ->label('Event')
                ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('tutor_id')
                ->label('Tutor')
                ->options(fn () => static::scopedTutorOptions())
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('score')->label('Nilai ToT')->numeric()->minValue(0)->maxValue(100)->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('is_perfect', (int) $state === 100)),
            Forms\Components\Toggle::make('is_perfect')->label('Lulus sempurna')->disabled()->dehydrated(),
            Forms\Components\DateTimePicker::make('completed_at')->label('Tanggal selesai')->default(now()),
            Forms\Components\Hidden::make('assessed_by')->default(fn () => auth()->id()),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable(),
                Tables\Columns\TextColumn::make('tutor.user.name')->label('Tutor')->searchable(),
                Tables\Columns\TextColumn::make('score')->label('Nilai')->sortable(),
                Tables\Columns\IconColumn::make('is_perfect')->label('Sempurna')->boolean(),
                Tables\Columns\TextColumn::make('completed_at')->label('Selesai')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id')),
                Tables\Filters\TernaryFilter::make('is_perfect')->label('Lulus sempurna'),
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
            'index' => Pages\ManageTotAssessment::route('/'),
        ];
    }

    protected static function scopedTutorOptions()
    {
        $user = auth()->user();
        $query = Tutor::query()->with('user');

        if ($user?->role === UserRole::Admin && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        if ($user?->role === UserRole::Tutor) {
            $query->whereKey($user->tutor?->id);
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Tutor $tutor) => [$tutor->id => $tutor->user?->name ?? 'Tutor #' . $tutor->id]);
    }
}
