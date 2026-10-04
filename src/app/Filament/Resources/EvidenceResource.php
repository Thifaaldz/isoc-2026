<?php

namespace App\Filament\Resources;

use App\Filament\Support\UploadTypes;
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

    /** Admin RTIK Pusat: jumlah bukti dukung yang menunggu verifikasi. */
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->role !== UserRole::SuperAdmin) {
            return null;
        }

        $count = Evidence::query()->where('status', 'pending')->where('type', '!=', 'follow_ig')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Bukti Dukung';

    protected static ?int $navigationSort = 2;

    /** Verifikasi bukti dukung hanya dilakukan Admin RTIK Pusat (Super Admin). */
    public static function canVerify(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

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
                        // Select biasa (bukan searchable): nilainya diisi otomatis dari event dan field-nya terkunci.
                        Forms\Components\Select::make('school_id')
                            ->label('Lokasi')
                            ->options(fn () => static::scopedSchoolOptions())
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
                            ->options(fn (?Evidence $record) => $record && in_array($record->type, Evidence::LEGACY_TYPES, true)
                                ? Evidence::TYPES
                                : Evidence::uploadTypes())
                            ->required()
                            ->live()
                            ->helperText(fn (Forms\Get $get) => isset(Evidence::REQUIRED_PHOTOS[$get('type')])
                                ? Evidence::REQUIRED_PHOTOS[$get('type')]['instruction'] . ' Minimal ' . Evidence::REQUIRED_PHOTOS[$get('type')]['min'] . ' foto. Untuk upload banyak foto sekaligus, gunakan tombol "Upload Foto Wajib".'
                                : null),
                        Forms\Components\TextInput::make('session_index')
                            ->label('Sesi ke')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'absensi_basah')
                            ->helperText('Isi jika bukti dikumpulkan per sesi/pertemuan.'),
                        Forms\Components\FileUpload::make('file_path')
                            ->acceptedFileTypes(UploadTypes::evidence())
                            ->label('Berkas')
                            ->disk('public')
                            ->directory('evidences')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('link')
                            ->label('Tautan bukti / video')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Wizard\Step::make('Status Verifikasi')
                    ->description('Tutor dan Admin RTIK Daerah mengirim bukti. Admin RTIK Pusat melakukan verifikasi.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'needs_revision' => 'Perlu revisi', 'rejected' => 'Ditolak'])
                            ->default('pending')
                            ->disabled(fn () => ! static::canVerify())
                            ->dehydrated()
                            // Bukti yang dikirim/diubah selain oleh RTIK Pusat selalu kembali menunggu verifikasi.
                            ->dehydrateStateUsing(fn (?string $state) => static::canVerify() ? $state : 'pending')
                            ->required(),
                        Forms\Components\Textarea::make('review_notes')
                            ->label('Catatan verifikasi')
                            ->disabled(fn () => ! static::canVerify())
                            ->dehydrated(fn () => static::canVerify())
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
                ->persistStepInQueryString()
                ->columnSpanFull(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public static function eventOptions(): \Illuminate\Support\Collection
    {
        return static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi')->searchable()->sortable(),
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
                    ->label('Lokasi')
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
                    ->visible(fn ($record) => static::canVerify() && $record->status !== 'approved')
                    ->action(fn ($record) => $record->update(['status' => 'approved', 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\Action::make('reject')->label('Tolak')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn ($record) => static::canVerify() && $record->status !== 'rejected')
                    ->form([Forms\Components\Textarea::make('review_notes')->label('Alasan penolakan')->required()])
                    ->action(fn ($record, array $data) => $record->update(['status' => 'rejected', 'review_notes' => $data['review_notes'], 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\Action::make('revision')->label('Minta Revisi')->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn ($record) => static::canVerify() && $record->status !== 'needs_revision')
                    ->form([Forms\Components\Textarea::make('review_notes')->label('Catatan revisi')->required()])
                    ->action(fn ($record, array $data) => $record->update(['status' => 'needs_revision', 'review_notes' => $data['review_notes'], 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Evidence $record) => auth()->user()?->role !== UserRole::Tutor || $record->status !== 'approved'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Evidence $record) => auth()->user()?->role !== UserRole::Tutor || $record->status !== 'approved'),
            ])
            ->bulkActions([
                // Verifikasi massal untuk Admin RTIK Pusat (satu lokus berisi belasan bukti).
                Tables\Actions\BulkAction::make('approveSelected')
                    ->label('Setujui terpilih')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Semua bukti dukung yang dipilih akan ditandai Disetujui.')
                    ->visible(fn () => static::canVerify())
                    ->deselectRecordsAfterCompletion()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        $records->each(fn (Evidence $record) => $record->update(['status' => 'approved', 'verified_by' => auth()->id(), 'verified_at' => now()]));

                        \Filament\Notifications\Notification::make()->title($records->count() . ' bukti dukung disetujui')->success()->send();
                    }),
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
