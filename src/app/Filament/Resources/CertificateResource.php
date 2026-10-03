<?php

namespace App\Filament\Resources;

use App\Filament\Support\UploadTypes;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Services\CertificateEligibilityService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Certificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $navigationLabel = 'Sertifikat Saya';

    protected static ?string $modelLabel = 'Sertifikat Saya';

    protected static ?string $pluralModelLabel = 'Sertifikat Saya';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return static::isParticipantView() ? 'Sertifikat Saya' : 'e-Certificate';
    }

    public static function getModelLabel(): string
    {
        return static::isParticipantView() ? 'Sertifikat Saya' : 'e-Certificate';
    }

    public static function getPluralModelLabel(): string
    {
        return static::isParticipantView() ? 'Sertifikat Saya' : 'e-Certificate';
    }

    protected static function isParticipantView(): bool
    {
        return auth()->user()?->role === UserRole::Peserta;
    }

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Peserta];
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
            auth()->user()?->role === UserRole::Peserta
                ? Forms\Components\Hidden::make('participant_id')->default(fn () => auth()->user()->participant?->id)
                : Forms\Components\Select::make('participant_id')->label('Peserta')->options(fn () => static::scopedParticipantOptions())->searchable()->required(),
            Forms\Components\Select::make('learning_event_id')
                ->label('Event')
                ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('certificate_template_id')
                ->label('Desain Sertifikat')
                ->options(fn () => CertificateTemplate::query()
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\TextInput::make('number')->label('Nomor sertifikat')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(fn (?Certificate $record) => $record?->eligibility_status === 'eligible'
                    ? ['pending' => 'Menunggu', 'issued' => 'Terbit', 'revoked' => 'Dicabut']
                    : ['pending' => 'Menunggu', 'revoked' => 'Dicabut'])
                ->default('pending'),
            Forms\Components\Select::make('eligibility_status')
                ->label('Eligibility')
                ->options(['pending' => 'Belum dicek', 'eligible' => 'Eligible', 'blocked' => 'Terkunci'])
                ->default('pending')
                ->helperText('Diisi otomatis oleh sistem lewat Cek Eligibility.')
                ->disabled()
                ->dehydrated(false),
            Forms\Components\DateTimePicker::make('issued_at')->label('Tanggal terbit'),
            Forms\Components\Textarea::make('eligibility_notes')->label('Catatan eligibility')->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->acceptedFileTypes(UploadTypes::PDF)->label('Berkas PDF')->directory('certificates'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable(),
                Tables\Columns\TextColumn::make('certificateTemplate.name')->label('Desain')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('number')->label('Nomor')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('eligibility_status')->label('Eligibility')->badge()->color(fn ($state) => $state === 'eligible' ? 'success' : ($state === 'blocked' ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('issued_at')->label('Terbit')->searchable()->sortable()->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('participant.learningEvents', fn ($eventQuery) => $eventQuery->where('learning_events.id', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['pending' => 'Menunggu', 'issued' => 'Terbit', 'revoked' => 'Dicabut']),
            ])
            ->actions([
                Tables\Actions\Action::make('checkEligibility')
                    ->label('Cek Eligibility')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn () => auth()->user()?->role !== UserRole::Peserta)
                    ->action(fn (Certificate $record) => app(CertificateEligibilityService::class)->updateCertificate($record)),
                Tables\Actions\Action::make('issue')
                    ->label('Terbitkan')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->visible(fn (Certificate $record) => auth()->user()?->role !== UserRole::Peserta && $record->eligibility_status === 'eligible' && $record->status !== 'issued')
                    ->action(fn (Certificate $record) => $record->update(['status' => 'issued', 'issued_at' => now()])),
                Tables\Actions\Action::make('previewPdf')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Certificate $record) => route('certificates.preview-pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Certificate $record) => auth()->user()?->role === UserRole::Peserta
                        ? $record->isIssued()
                        : ($record->isIssued() || $record->isEligible())),
                Tables\Actions\Action::make('downloadPdf')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (Certificate $record) => route('certificates.download-pdf', $record))
                    ->visible(fn (Certificate $record) => auth()->user()?->role === UserRole::Peserta
                        ? $record->isIssued()
                        : ($record->isIssued() || $record->isEligible())),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->role !== UserRole::Peserta),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->role !== UserRole::Peserta),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCertificate::route('/'),
        ];
    }
}
