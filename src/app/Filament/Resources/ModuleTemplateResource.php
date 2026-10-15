<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ModuleTemplateResource\Pages;
use App\Models\ModuleTemplate;
use App\Services\TutorMaterialMirror;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ModuleTemplateResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = ModuleTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Konten & Penilaian';

    protected static ?string $navigationLabel = 'Materi Event';

    protected static ?string $modelLabel = 'Materi Event';

    protected static ?string $pluralModelLabel = 'Materi Event';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function form(Form $form): Form
    {
        $generated = fn (?ModuleTemplate $record) => (bool) $record?->isGeneratedForTutor();

        return $form->schema([
            Forms\Components\Section::make('Generated for Tutor')
                ->description('Auto-generated')
                ->icon('heroicon-o-sparkles')
                ->schema([
                    Forms\Components\Placeholder::make('generated_info')
                        ->hiddenLabel()
                        ->content(fn (ModuleTemplate $record) => 'Materi ini disalin otomatis dari Materi Event "' . ($record->sourceTemplate?->name ?? '-') . '" saat pertama dibuat. Pertemuan dan materinya (PPT, video, dll.) bisa diedit sendiri tanpa memengaruhi materi peserta. Isi juga Pre-Test dan Post-Test milik tutor.'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('edit_tutor_meetings')
                            ->label('Edit Pertemuan & Materi Tutor')
                            ->icon('heroicon-o-pencil-square')
                            ->url(fn (ModuleTemplate $record) => LearningMeetingResource::getUrl('index', ['materi_event' => $record->id])),
                        Forms\Components\Actions\Action::make('resync_from_source')
                            ->label('Salin ulang dari materi peserta')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalDescription('Pertemuan dan materi tutor akan diganti dengan salinan terbaru materi peserta. Perubahan yang sudah dibuat di materi tutor akan hilang. Pre-Test dan Post-Test ToT tidak berubah.')
                            ->visible(fn (ModuleTemplate $record) => $record->sourceTemplate !== null)
                            ->action(function (ModuleTemplate $record): void {
                                app(TutorMaterialMirror::class)->resync($record);

                                Notification::make()->title('Materi tutor disalin ulang dari materi peserta')->success()->send();
                            }),
                        Forms\Components\Actions\Action::make('pre_test_tutor')
                            ->label(fn (ModuleTemplate $record) => static::hasTest($record, 'pre') ? 'Ubah Pre-Test Tutor' : 'Isi Pre-Test Tutor')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->url(fn (ModuleTemplate $record) => static::testUrl($record, 'pre')),
                        Forms\Components\Actions\Action::make('post_test_tutor')
                            ->label(fn (ModuleTemplate $record) => static::hasTest($record, 'post') ? 'Ubah Post-Test Tutor' : 'Isi Post-Test Tutor')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->url(fn (ModuleTemplate $record) => static::testUrl($record, 'post')),
                    ]),
                ])
                ->visible($generated),
            Forms\Components\Section::make('Informasi Materi Event')
                ->schema([
                    Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Materi Event')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('audience')
                        ->label('Tipe Materi')
                        ->options(ModuleTemplate::AUDIENCES)
                        ->default(ModuleTemplate::AUDIENCE_PESERTA)
                        ->required()
                        ->native(false)
                        ->disabled($generated)
                        ->helperText('Peserta: dipilih admin daerah di wizard event dan menjadi modul/tes peserta, sekaligus otomatis dibuatkan materi tutor (Generated for Tutor) berupa salinan yang bisa diedit. Tutor: tampil di halaman ToT Awal tutor.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif / bisa dipilih admin')
                        ->default(true),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('purpose')
                        ->label('Tujuan Materi')
                        ->helperText('Jelaskan tujuan pembelajaran dan hasil yang diharapkan dari materi event ini.')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('meeting_count')
                        ->label('Jumlah Pertemuan')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(30)
                        ->default(1)
                        ->live(onBlur: true)
                        ->helperText('Sistem akan membuat daftar pertemuan. Lengkapi isi pertemuan dan materi dari menu Pertemuan & Materi.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('sourceTemplate'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Materi Event')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ModuleTemplate $record) => $record->isGeneratedForTutor()
                        ? 'Auto-generated dari: ' . ($record->sourceTemplate?->name ?? '-')
                        : null),
                Tables\Columns\TextColumn::make('generated_label')
                    ->label('Sumber')
                    ->state(fn (ModuleTemplate $record) => $record->isGeneratedForTutor() ? 'Generated for Tutor' : null)
                    ->placeholder('Manual')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-sparkles'),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ModuleTemplate::AUDIENCES[$state] ?? $state)
                    ->color(fn (?string $state) => $state === ModuleTemplate::AUDIENCE_TUTOR ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purpose')->label('Tujuan')->limit(55)->toggleable(),
                Tables\Columns\TextColumn::make('meeting_count')->label('Target Pertemuan')->sortable(),
                Tables\Columns\TextColumn::make('learning_meetings_count')->counts('learningMeetings')->label('Terbuat'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Tipe Materi')
                    ->options(ModuleTemplate::AUDIENCES),
            ])
            ->recordUrl(fn (ModuleTemplate $record) => static::getUrl('edit', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-play-circle')
                    ->color('info')
                    ->url(fn (ModuleTemplate $record) => \App\Filament\Pages\MaterialPreview::getUrlForTemplate($record)),
                Tables\Actions\EditAction::make()->label('Buka'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    private static function hasTest(ModuleTemplate $record, string $type): bool
    {
        return $record->assessments()->where('type', $type)->exists();
    }

    /** Buka Pre/Post-Test tutor yang sudah ada, atau form baru yang langsung terhubung ke materi tutor ini. */
    private static function testUrl(ModuleTemplate $record, string $type): string
    {
        $assessment = $record->assessments()->where('type', $type)->first();

        if ($assessment) {
            return AssessmentResource::getUrl('edit', ['record' => $assessment]);
        }

        return AssessmentResource::getUrl('create', ['module_template_id' => $record->id, 'type' => $type]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleTemplates::route('/'),
            'create' => Pages\CreateModuleTemplate::route('/create'),
            'edit' => Pages\EditModuleTemplate::route('/{record}/edit'),
        ];
    }
}
