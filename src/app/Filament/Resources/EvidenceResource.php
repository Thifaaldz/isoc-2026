<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\EvidenceResource\Pages;
use App\Models\Evidence;
use App\Models\LearningEvent;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvidenceResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Evidence::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Bukti Dukung';

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
        return 'evidence';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Event / Lokus')
                    ->description('Pilih event yang sedang tutor dampingi.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Select::make('learning_event_id')
                            ->label('Event')
                            ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('school_id', LearningEvent::query()->find($state)?->school_id))
                            ->required(),
                        Forms\Components\Select::make('school_id')
                            ->label('Sekolah / Lokus')
                            ->options(fn () => static::scopedSchoolOptions())
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Opsional untuk event umum.'),
                        Forms\Components\Hidden::make('uploaded_by')->default(fn () => auth()->id()),
                    ])
                    ->columns(2),
                Forms\Components\Wizard\Step::make('Bukti Dukung')
                    ->description('Upload file atau isi tautan bukti lapangan.')
                    ->icon('heroicon-o-document-arrow-up')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis bukti')
                            ->options(Evidence::TYPES)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('session_index')
                            ->label('Sesi ke')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['foto_sesi', 'absensi_basah'], true))
                            ->helperText('Isi jika bukti dikumpulkan per sesi/pertemuan.'),
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Berkas')
                            ->disk('public')
                            ->directory('evidences')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('link')
                            ->label('Tautan bukti / video')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Wizard\Step::make('Status Verifikasi')
                    ->description('Tutor hanya mengirim bukti. Admin RTIK/Pusat melakukan verifikasi.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'needs_revision' => 'Perlu revisi', 'rejected' => 'Ditolak'])
                            ->default('pending')
                            ->disabled(fn () => auth()->user()?->role === UserRole::Tutor)
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Catatan verifikasi')
                            ->disabled(fn () => auth()->user()?->role === UserRole::Tutor)
                            ->dehydrated(fn () => auth()->user()?->role !== UserRole::Tutor)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
                ->persistStepInQueryString()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Jenis')->formatStateUsing(fn ($state) => \App\Models\Evidence::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('session_index')->label('Sesi'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', 'needs_revision' => 'warning', default => 'gray' }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(\App\Models\LearningEvent::query())->pluck('title', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Bukti')
                    ->options(Evidence::TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'needs_revision' => 'Perlu revisi', 'rejected' => 'Ditolak']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Setujui')->icon('heroicon-o-check')->color('success')
                    ->visible(fn ($record) => in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && $record->status !== 'approved')
                    ->action(fn ($record) => $record->update(['status' => 'approved', 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\Action::make('reject')->label('Tolak')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn ($record) => in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && $record->status !== 'rejected')
                    ->form([Forms\Components\Textarea::make('review_notes')->label('Alasan penolakan')->required()])
                    ->action(fn ($record, array $data) => $record->update(['status' => 'rejected', 'review_notes' => $data['review_notes'], 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\Action::make('revision')->label('Minta Revisi')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn ($record) => in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && $record->status !== 'needs_revision')
                    ->form([Forms\Components\Textarea::make('review_notes')->label('Catatan revisi')->required()])
                    ->action(fn ($record, array $data) => $record->update(['status' => 'needs_revision', 'review_notes' => $data['review_notes'], 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Evidence $record) => auth()->user()?->role !== UserRole::Tutor || $record->status !== 'approved'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Evidence $record) => auth()->user()?->role !== UserRole::Tutor || $record->status !== 'approved'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEvidence::route('/'),
        ];
    }
}
