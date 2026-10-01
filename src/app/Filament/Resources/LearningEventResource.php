<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\LearningEventResource\Pages;
use App\Filament\Support\SchoolLocationFields;
use App\Models\LearningEvent;
use App\Models\ModuleTemplate;
use App\Models\School;
use App\Services\ImportSpreadsheetParser;
use App\Services\LearningEventProvisioner;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LearningEventResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = LearningEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Seminar';

    protected static ?string $modelLabel = 'Seminar / Event';

    protected static ?string $pluralModelLabel = 'Seminar / Event';

    protected static ?string $navigationLabel = 'Kelola Seminar';

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
        return 'learning_event';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Sekolah / Tempat')
                    ->description('Data lokasi event dan jadwal pelatihan.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
                        Forms\Components\TextInput::make('title')
                            ->label('Nama Event / Lokus')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),
                        Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                        Forms\Components\Select::make('event_type')
                            ->label('Tipe Event')
                            ->options(static::eventTypeOptions())
                            ->default('offline')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('audience_type')
                            ->label('Kategori Peserta')
                            ->options(static::audienceTypeOptions())
                            ->default('school')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('school_id')
                            ->label('Sekolah / Tempat Event')
                            ->options(fn () => static::scopedSchoolOptions())
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => $get('audience_type') !== 'general')
                            ->helperText(fn (Get $get) => $get('audience_type') === 'general'
                                ? 'Opsional untuk event umum.'
                                : 'Wajib untuk event khusus sekolah/tempat.')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama sekolah/tempat')->required(),
                                Forms\Components\TextInput::make('npsn')->label('NPSN')->maxLength(20),
                                Forms\Components\Select::make('type')->label('Tipe')->options(['SMA' => 'SMA', 'SMK' => 'SMK', 'Tempat' => 'Tempat Event'])->default('SMA')->required(),
                                ...SchoolLocationFields::schema(),
                                Forms\Components\Textarea::make('address')->label('Alamat')->columnSpanFull(),
                                Forms\Components\TextInput::make('maps_url')->label('Link Google Maps')->url()->maxLength(255)->placeholder('https://maps.google.com/...')->columnSpanFull(),
                                Forms\Components\TextInput::make('pic_name')->label('PIC'),
                                Forms\Components\TextInput::make('pic_phone')->label('Kontak PIC'),
                                Forms\Components\TextInput::make('participant_target')->label('Target peserta')->numeric()->default(100),
                                Forms\Components\DatePicker::make('training_date')->label('Tanggal pelatihan'),
                            ])
                            ->createOptionUsing(fn (array $data) => School::query()->create($data)->id),
                        Forms\Components\Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
                        Forms\Components\TextInput::make('zoom_url')
                            ->label('Link Zoom / Webinar')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('https://zoom.us/j/...')
                            ->helperText('Diisi untuk webinar/hybrid. Link ini tampil di dashboard peserta dan dapat diperbarui oleh admin daerah atau tutor.')
                            ->visible(fn (Get $get) => in_array($get('event_type'), ['webinar', 'hybrid'], true))
                            ->columnSpanFull(),
                        Forms\Components\Select::make('module_template_id')
                            ->label('Materi Event')
                            ->helperText('Pilih materi event dari Super Admin. Sistem akan generate pertemuan, materi, tugas, dan kuis ke seminar ini.')
                            ->options(fn () => ModuleTemplate::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Tanggal & Jam Mulai')
                            ->seconds(false)
                            ->native(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('training_start_time', static::timeFromDateTime($state, '09:00')))
                            ->required(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Tanggal & Jam Selesai')
                            ->seconds(false)
                            ->native(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('training_end_time', static::timeFromDateTime($state, '12:00')))
                            ->required(),
                        Forms\Components\Hidden::make('training_start_time')->default('09:00'),
                        Forms\Components\Hidden::make('training_end_time')->default('12:00'),
                        Forms\Components\TextInput::make('target_participants')->label('Target Peserta')->numeric()->default(100)->required(),
                        Forms\Components\TextInput::make('target_tutors')->label('Target Tutor')->numeric()->default(3)->required(),
                        Forms\Components\FileUpload::make('preparation_document')
                            ->label('Surat/MoU/Berita Acara Persiapan')
                            ->disk('public')
                            ->directory('event-documents'),
                        Forms\Components\Select::make('workflow_status')
                            ->label('Status Workflow')
                            ->options(static::workflowStatusOptions())
                            ->default('draft')
                            ->disabled(fn () => auth()->user()?->role !== UserRole::SuperAdmin)
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('publish_approval_status')
                            ->label('Approval Publish')
                            ->options(static::publishApprovalStatusOptions())
                            ->default('draft')
                            ->disabled(fn () => auth()->user()?->role !== UserRole::SuperAdmin)
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status Publik')
                            ->options(['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Ditutup'])
                            ->default('draft')
                            ->required(),
                        Forms\Components\Toggle::make('registration_open')
                            ->label('Pendaftaran dibuka')
                            ->default(false)
                            ->disabled(fn () => auth()->user()?->role !== UserRole::SuperAdmin)
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Tampil di landing page & peserta')
                            ->default(false)
                            ->disabled(fn () => auth()->user()?->role !== UserRole::SuperAdmin)
                            ->dehydrated(),
                    ])
                    ->columns(2),
                Forms\Components\Wizard\Step::make('Peserta')
                    ->description('Import Excel atau input peserta manual. Sistem membuat akun peserta.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\FileUpload::make('participant_import_file')
                            ->label('File Excel Peserta')
                            ->helperText('Upload XLSX/CSV sebagai arsip import. Data final tetap dapat diperiksa di daftar peserta manual di bawah.')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('downloadParticipantTemplate')
                                    ->label('Download template')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(fn () => route('import-templates.participants'))
                                    ->openUrlInNewTab(),
                            )
                            ->disk('public')
                            ->directory('event-imports')
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $rows = app(ImportSpreadsheetParser::class)->participants($state);

                                if ($rows === []) {
                                    Notification::make()
                                        ->title('Data peserta belum terbaca')
                                        ->body('Pastikan file memakai template dan header kolom tidak diubah.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $set('participant_rows', $rows);

                                Notification::make()
                                    ->title('Data peserta terisi')
                                    ->body(count($rows) . ' baris peserta berhasil dibaca dari file.')
                                    ->success()
                                    ->send();
                            })
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('participant_rows')
                            ->label('Data Peserta')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('Nama lengkap')->required(),
                                Forms\Components\TextInput::make('nis')->label('NIS/NISN'),
                                Forms\Components\TextInput::make('grade')->label('Kelas'),
                                Forms\Components\TextInput::make('organization')->label('Organisasi / Instansi'),
                                Forms\Components\TextInput::make('position')->label('Jabatan / Peran'),
                                Forms\Components\Select::make('gender')->label('Jenis Kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
                                Forms\Components\DatePicker::make('birth_date')->label('Tanggal lahir'),
                                Forms\Components\TextInput::make('phone')->label('Kontak'),
                                Forms\Components\TextInput::make('email')->label('Email')->email(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah peserta')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Wizard\Step::make('Tutor / Fasilitator')
                    ->description('Data 3 tutor/fasilitator. Sistem membuat akun tutor.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\FileUpload::make('tutor_import_file')
                            ->label('File Excel Tutor')
                            ->helperText('Upload XLSX/CSV sebagai arsip import. Data final tetap dapat diperiksa di daftar tutor manual di bawah.')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('downloadTutorTemplate')
                                    ->label('Download template')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(fn () => route('import-templates.tutors'))
                                    ->openUrlInNewTab(),
                            )
                            ->disk('public')
                            ->directory('event-imports')
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $rows = app(ImportSpreadsheetParser::class)->tutors($state);

                                if ($rows === []) {
                                    Notification::make()
                                        ->title('Data tutor belum terbaca')
                                        ->body('Pastikan file memakai template dan header kolom tidak diubah.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $set('tutor_rows', $rows);

                                Notification::make()
                                    ->title('Data tutor terisi')
                                    ->body(count($rows) . ' baris tutor berhasil dibaca dari file.')
                                    ->success()
                                    ->send();
                            })
                            ->acceptedFileTypes([
                                'text/csv',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('tutor_rows')
                            ->label('Data Tutor')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('Nama lengkap')->required(),
                                Forms\Components\TextInput::make('phone')->label('Kontak'),
                                Forms\Components\TextInput::make('email')->label('Email')->email(),
                                Forms\Components\TextInput::make('institution')->label('Institusi / Lembaga'),
                                Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah tutor')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Wizard\Step::make('Rundown Acara')
                    ->description('Susun alur kegiatan seminar sesuai durasi acara.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Section::make('Rundown Acara')
                            ->description('Default mengikuti format pelatihan 180 menit. Admin daerah tetap bisa mengubah jam, agenda, PIC, dan catatan.')
                            ->schema([
                                Forms\Components\Repeater::make('rundown_items')
                                    ->hiddenLabel()
                                    ->schema(static::rundownItemSchema())
                                    ->columns(12)
                                    ->default(static::defaultRundownItems())
                                    ->addActionLabel('Tambah agenda')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => filled($state['activity'] ?? null)
                                        ? (($state['start_time'] ?? '--:--') . ' - ' . ($state['activity'] ?? 'Agenda'))
                                        : 'Agenda')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Forms\Components\Wizard\Step::make('RAB & Submit')
                    ->description('Upload dokumen dan input rancangan biaya secara manual.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\Section::make('Dokumen RAB')
                            ->schema([
                                Forms\Components\FileUpload::make('rab_file')
                                    ->label('Upload RAB Awal')
                                    ->disk('public')
                                    ->directory('event-documents'),
                                Forms\Components\Textarea::make('local_admin_notes')
                                    ->label('Catatan Admin RTIK Local')
                                    ->rows(3),
                            ])
                            ->columns(2),
                        Forms\Components\Section::make('Input Manual RAB')
                            ->description('Isi item biaya langsung di sistem. Total item dipakai untuk estimasi termin pembayaran.')
                            ->schema([
                                Forms\Components\Repeater::make('budget_items')
                                    ->hiddenLabel()
                                    ->schema(static::budgetItemSchema())
                                    ->columns(12)
                                    ->defaultItems(1)
                                    ->addActionLabel('Tambah item biaya')
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => filled($state['description'] ?? null)
                                        ? ($state['description'] . ' - Rp ' . number_format((float) ($state['amount'] ?? 0), 0, ',', '.'))
                                        : 'Item biaya')
                                    ->columnSpanFull(),
                                Forms\Components\Placeholder::make('budget_total_preview')
                                    ->label('Total RAB Manual')
                                    ->content(fn (Get $get) => 'Rp ' . number_format(collect($get('budget_items') ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)), 0, ',', '.')),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
                ->persistStepInQueryString()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::eventTypeOptions()[$state ?: 'offline'] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'webinar' => 'info',
                        'hybrid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('audience_type')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::audienceTypeOptions()[$state ?: 'school'] ?? $state)
                    ->color(fn (?string $state) => $state === 'general' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('zoom_url')
                    ->label('Zoom')
                    ->limit(26)
                    ->url(fn (?string $state) => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moduleTemplate.name')->label('Materi Event')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('starts_at')->label('Mulai')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('participants_count')->counts('participants')->label('Peserta')->sortable(),
                Tables\Columns\TextColumn::make('tutors_count')->counts('tutors')->label('Tutor')->sortable(),
                Tables\Columns\TextColumn::make('tot_progress')
                    ->label('ToT')
                    ->state(function (LearningEvent $record): string {
                        $total = $record->tutors()->count();
                        $passed = $record->totAssessments()
                            ->where('is_perfect', true)
                            ->distinct('tutor_id')
                            ->count('tutor_id');

                        return $passed . '/' . $total;
                    })
                    ->badge()
                    ->color(function (LearningEvent $record): string {
                        $total = $record->tutors()->count();
                        $passed = $record->totAssessments()
                            ->where('is_perfect', true)
                            ->distinct('tutor_id')
                            ->count('tutor_id');

                        return $total > 0 && $passed >= $total ? 'success' : 'warning';
                    })
                    ->tooltip('Jumlah tutor yang sudah lulus ToT sempurna.'),
                Tables\Columns\TextColumn::make('meetings_count')->counts('meetings')->label('Pertemuan')->sortable(),
                Tables\Columns\TextColumn::make('budget_total')
                    ->label('Total RAB')
                    ->state(fn (LearningEvent $record) => collect($record->budget_items ?? [])->sum(fn ($item) => (float) ($item['amount'] ?? 0)))
                    ->money('IDR')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction)),
                Tables\Columns\TextColumn::make('workflow_status')->label('Workflow')->badge(),
                Tables\Columns\TextColumn::make('publish_approval_status')
                    ->label('Approval Publish')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::publishApprovalStatusOptions()[$state ?: 'draft'] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'revision' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Publik')->badge(),
                Tables\Columns\TextColumn::make('local_updated_at')
                    ->label('Update Daerah')
                    ->since()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('central_admin_notes')
                    ->label('Catatan Pusat')
                    ->limit(45)
                    ->tooltip(fn (LearningEvent $record) => $record->central_admin_notes)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('local_admin_notes')
                    ->label('Catatan Daerah')
                    ->limit(45)
                    ->tooltip(fn (LearningEvent $record) => $record->local_admin_notes)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('publish_revision_notes')
                    ->label('Catatan Publish')
                    ->limit(45)
                    ->tooltip(fn (LearningEvent $record) => $record->publish_revision_notes)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_published')->label('Publish')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->label('Tipe Event')
                    ->options(static::eventTypeOptions()),
                Tables\Filters\SelectFilter::make('audience_type')
                    ->label('Kategori Peserta')
                    ->options(static::audienceTypeOptions()),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions()),
                Tables\Filters\SelectFilter::make('workflow_status')
                    ->label('Workflow')
                    ->options(static::workflowStatusOptions()),
                Tables\Filters\SelectFilter::make('publish_approval_status')
                    ->label('Approval Publish')
                    ->options(static::publishApprovalStatusOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Ditutup']),
                Tables\Filters\TernaryFilter::make('registration_open')
                    ->label('Pendaftaran dibuka'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publish'),
            ])
            ->actions([
                Tables\Actions\Action::make('updateZoomLink')
                    ->label('Link Zoom')
                    ->icon('heroicon-o-video-camera')
                    ->color('info')
                    ->visible(fn (LearningEvent $record) => in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Tutor, UserRole::SuperAdmin], true)
                        && in_array($record->event_type, ['webinar', 'hybrid'], true))
                    ->form([
                        Forms\Components\TextInput::make('zoom_url')
                            ->label('Link Zoom / Webinar')
                            ->url()
                            ->required()
                            ->default(fn (LearningEvent $record) => $record->zoom_url)
                            ->placeholder('https://zoom.us/j/...'),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update([
                            'zoom_url' => $data['zoom_url'],
                            'local_updated_at' => now(),
                            'local_update_summary' => 'Link Zoom diperbarui oleh ' . (auth()->user()?->name ?? 'user') . '.',
                        ]);

                        Notification::make()
                            ->title('Link Zoom diperbarui')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('submitApplication')
                    ->label('Submit Pengajuan')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::Admin && in_array($record->workflow_status, ['draft', 'needs_revision'], true))
                    ->action(function (LearningEvent $record): void {
                        app(LearningEventProvisioner::class)->provisionPaymentTerms($record);

                        $record->update([
                            'workflow_status' => 'submitted',
                            'central_admin_notes' => null,
                            'local_updated_at' => now(),
                            'local_update_summary' => 'Pengajuan event dikirim ke Admin RTIK Pusat.',
                        ]);
                    }),
                Tables\Actions\Action::make('verifyTerm1')
                    ->label('Approve Event')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && $record->workflow_status === 'submitted')
                    ->form([
                        Forms\Components\Textarea::make('central_admin_notes')
                            ->label('Catatan approval')
                            ->placeholder('Opsional: catatan untuk Admin RTIK daerah.')
                            ->rows(3),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        app(LearningEventProvisioner::class)->provisionAccounts($record);
                        app(LearningEventProvisioner::class)->provisionPaymentTerms($record);

                        $note = $data['central_admin_notes'] ?? null;

                        $record->update([
                            'workflow_status' => 'verified_term_1',
                            'status' => 'active',
                            'publish_approval_status' => 'pending',
                            'is_published' => false,
                            'registration_open' => false,
                            'central_admin_notes' => $note,
                        ]);

                        Notification::make()
                            ->title('Pengajuan disetujui')
                            ->body('Akun peserta dan tutor sudah dibuat. Event menunggu approval publish sebelum tampil di landing page dan Termin-1 aktif.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('requestPublishApproval')
                    ->label('Ajukan Publish')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::Admin
                        && $record->workflow_status === 'verified_term_1'
                        && in_array($record->publish_approval_status, ['draft', 'revision'], true))
                    ->action(function (LearningEvent $record): void {
                        $record->update([
                            'publish_approval_status' => 'pending',
                            'publish_revision_notes' => null,
                            'local_updated_at' => now(),
                            'local_update_summary' => 'Admin RTIK Daerah mengajukan publish event.',
                        ]);

                        Notification::make()
                            ->title('Pengajuan publish dikirim')
                            ->body('Admin RTIK Pusat akan mengecek event sebelum tampil di landing page.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('approvePublish')
                    ->label('Publish + T1')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                        && $record->workflow_status === 'verified_term_1'
                        && in_array($record->publish_approval_status, ['pending', 'revision', 'draft'], true))
                    ->form([
                        Forms\Components\Textarea::make('central_admin_notes')
                            ->label('Catatan publish / Termin-1')
                            ->placeholder('Opsional: catatan publish dan Termin-1.')
                            ->rows(3),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        app(LearningEventProvisioner::class)->provisionPaymentTerms($record);

                        $note = $data['central_admin_notes'] ?? null;

                        $record->update([
                            'publish_approval_status' => 'published',
                            'publish_revision_notes' => null,
                            'is_published' => true,
                            'registration_open' => true,
                            'status' => 'active',
                            'central_admin_notes' => $note ?: $record->central_admin_notes,
                            'publish_approved_by' => auth()->id(),
                            'publish_approved_at' => now(),
                        ]);

                        $record->payments()->where('term', 1)->update([
                            'status' => 'eligible',
                            'notes' => $note,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Event dipublish')
                            ->body('Event tampil di landing page/peserta dan Termin-1 sudah eligible.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('requestPublishRevision')
                    ->label('Revisi Publish')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                        && $record->workflow_status === 'verified_term_1'
                        && $record->publish_approval_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('publish_revision_notes')
                            ->label('Catatan revisi publish')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update([
                            'publish_approval_status' => 'revision',
                            'is_published' => false,
                            'registration_open' => false,
                            'publish_revision_notes' => $data['publish_revision_notes'],
                        ]);

                        Notification::make()
                            ->title('Publish dikembalikan untuk revisi')
                            ->body('Event tetap approved, tetapi belum tampil di landing page sampai publish disetujui.')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('requestRevision')
                    ->label('Revisi Event')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && $record->workflow_status === 'submitted')
                    ->form([
                        Forms\Components\Textarea::make('central_admin_notes')
                            ->label('Catatan perbaikan')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update([
                            'workflow_status' => 'needs_revision',
                            'publish_approval_status' => 'draft',
                            'is_published' => false,
                            'registration_open' => false,
                            'central_admin_notes' => $data['central_admin_notes'],
                        ]);

                        $record->payments()->where('term', 1)->update([
                            'status' => 'revision',
                            'notes' => $data['central_admin_notes'],
                        ]);

                        Notification::make()
                            ->title('Pengajuan dikembalikan untuk perbaikan')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancelApplication')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && in_array($record->workflow_status, ['submitted', 'needs_revision'], true))
                    ->form([
                        Forms\Components\Textarea::make('central_admin_notes')
                            ->label('Alasan cancel')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update([
                            'workflow_status' => 'cancelled',
                            'publish_approval_status' => 'draft',
                            'status' => 'closed',
                            'registration_open' => false,
                            'is_published' => false,
                            'central_admin_notes' => $data['central_admin_notes'],
                        ]);

                        $record->payments()->update([
                            'status' => 'revision',
                            'notes' => $data['central_admin_notes'],
                        ]);

                        Notification::make()
                            ->title('Pengajuan dibatalkan')
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('generateAttendanceCode')
                    ->label('Kode Absensi')
                    ->icon('heroicon-o-qr-code')
                    ->action(function (LearningEvent $record): void {
                        $code = strtoupper(Str::random(6));
                        $record->update(['attendance_code' => $code]);
                        Notification::make()->title('Kode absensi dibuat')->body($code)->success()->send();
                    }),
                Tables\Actions\Action::make('verifyTerm2')
                    ->label('Verify T2')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && in_array($record->workflow_status, ['evidence_submitted', 'field_training_completed', 'tot_completed'], true))
                    ->action(function (LearningEvent $record): void {
                        $record->update(['workflow_status' => 'verified_term_2']);
                        $record->payments()->where('term', 2)->update(['status' => 'eligible', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                    }),
                Tables\Actions\Action::make('verifyTerm3')
                    ->label('Verify T3')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && $record->workflow_status === 'final_report_submitted')
                    ->action(function (LearningEvent $record): void {
                        $record->update(['workflow_status' => 'verified_term_3']);
                        $record->payments()->where('term', 3)->update(['status' => 'eligible', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                        || (auth()->user()?->role === UserRole::Admin && ! in_array($record->workflow_status, ['cancelled', 'closed'], true))),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningEvents::route('/'),
            'create' => Pages\CreateLearningEvent::route('/create'),
            'edit' => Pages\EditLearningEvent::route('/{record}/edit'),
        ];
    }

    public static function workflowStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Submitted by Local Admin',
            'verified_term_1' => 'Verified for Termin-1',
            'tot_in_progress' => 'ToT In Progress',
            'tot_completed' => 'ToT Completed',
            'field_training_scheduled' => 'Field Training Scheduled',
            'field_training_completed' => 'Field Training Completed',
            'evidence_submitted' => 'Evidence Submitted',
            'verified_term_2' => 'Verified for Termin-2',
            'final_report_submitted' => 'Final Report Submitted',
            'verified_term_3' => 'Verified for Termin-3',
            'closed' => 'Closed',
            'needs_revision' => 'Needs Revision',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled by Pusat',
        ];
    }

    public static function eventTypeOptions(): array
    {
        return [
            'offline' => 'Offline',
            'webinar' => 'Webinar',
            'hybrid' => 'Hybrid',
        ];
    }

    public static function audienceTypeOptions(): array
    {
        return [
            'school' => 'Khusus Sekolah / Tempat',
            'general' => 'Umum',
        ];
    }

    public static function publishApprovalStatusOptions(): array
    {
        return [
            'draft' => 'Belum diajukan',
            'pending' => 'Menunggu approval publish',
            'revision' => 'Revisi publish',
            'published' => 'Published',
        ];
    }

    public static function budgetCategoryOptions(): array
    {
        return [
            'konsumsi' => 'Konsumsi',
            'transportasi' => 'Transportasi',
            'atk' => 'ATK',
            'banner_publikasi' => 'Banner / Publikasi',
            'dokumentasi' => 'Dokumentasi',
            'honor_narasumber' => 'Honor / Narasumber',
            'sewa_perlengkapan' => 'Sewa / Perlengkapan',
            'lain_lain' => 'Lain-lain',
        ];
    }

    public static function normalizeEventScheduleData(array $data): array
    {
        $data['training_start_time'] = static::timeFromDateTime($data['starts_at'] ?? null, $data['training_start_time'] ?? '09:00');
        $data['training_end_time'] = static::timeFromDateTime($data['ends_at'] ?? null, $data['training_end_time'] ?? '12:00');

        return $data;
    }

    public static function timeFromDateTime(mixed $state, string $fallback): string
    {
        if (blank($state)) {
            return $fallback;
        }

        try {
            return Carbon::parse($state)->format('H:i');
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function budgetItemSchema(): array
    {
        $recalculateAmount = function (Get $get, Set $set): void {
            $quantity = (float) ($get('quantity') ?: 0);
            $unitPrice = (float) ($get('unit_price') ?: 0);

            if ($quantity > 0 && $unitPrice > 0) {
                $set('amount', $quantity * $unitPrice);
            }
        };

        return [
            Forms\Components\Select::make('category')
                ->label('Kategori')
                ->options(static::budgetCategoryOptions())
                ->required()
                ->columnSpan(3),
            Forms\Components\TextInput::make('description')
                ->label('Keperluan')
                ->placeholder('Contoh: Banner kegiatan')
                ->required()
                ->columnSpan(5),
            Forms\Components\TextInput::make('quantity')
                ->label('Qty')
                ->numeric()
                ->default(1)
                ->live(onBlur: true)
                ->afterStateUpdated($recalculateAmount)
                ->columnSpan(2),
            Forms\Components\TextInput::make('unit')
                ->label('Satuan')
                ->placeholder('pcs, paket, orang')
                ->columnSpan(2),
            Forms\Components\TextInput::make('unit_price')
                ->label('Harga Satuan')
                ->numeric()
                ->prefix('Rp')
                ->live(onBlur: true)
                ->afterStateUpdated($recalculateAmount)
                ->columnSpan(3),
            Forms\Components\TextInput::make('amount')
                ->label('Total')
                ->numeric()
                ->prefix('Rp')
                ->required()
                ->helperText('Otomatis dari Qty x Harga Satuan, tetap bisa disesuaikan manual.')
                ->columnSpan(3),
            Forms\Components\TextInput::make('vendor')
                ->label('Vendor / Toko')
                ->columnSpan(3),
            Forms\Components\TextInput::make('receipt_number')
                ->label('No. Kwitansi / Invoice')
                ->columnSpan(3),
            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->rows(2)
                ->columnSpanFull(),
        ];
    }

    public static function rundownItemSchema(): array
    {
        return [
            Forms\Components\TimePicker::make('start_time')
                ->label('Mulai')
                ->seconds(false)
                ->default('09:00')
                ->required()
                ->columnSpan(2),
            Forms\Components\TimePicker::make('end_time')
                ->label('Selesai')
                ->seconds(false)
                ->default('09:15')
                ->required()
                ->columnSpan(2),
            Forms\Components\TextInput::make('activity')
                ->label('Agenda')
                ->placeholder('Pembukaan / Materi / Diskusi / Quiz')
                ->required()
                ->columnSpan(4),
            Forms\Components\TextInput::make('pic')
                ->label('PIC')
                ->placeholder('Tutor / Admin RTIK / Sekolah')
                ->columnSpan(2),
            Forms\Components\TextInput::make('notes')
                ->label('Catatan')
                ->columnSpan(2),
        ];
    }

    public static function defaultRundownItems(): array
    {
        return [
            [
                'start_time' => '09:00',
                'end_time' => '09:10',
                'activity' => 'Registrasi peserta dan pembukaan',
                'pic' => 'Admin RTIK Local',
                'notes' => 'Cek kehadiran awal dan kesiapan kelas.',
            ],
            [
                'start_time' => '09:10',
                'end_time' => '09:25',
                'activity' => 'Ice breaking dan pengarahan kegiatan',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Menjelaskan tujuan sesi dan aturan belajar.',
            ],
            [
                'start_time' => '09:25',
                'end_time' => '09:40',
                'activity' => 'Pre-Test peserta',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Peserta wajib menyelesaikan pre-test sebelum modul.',
            ],
            [
                'start_time' => '09:40',
                'end_time' => '11:15',
                'activity' => 'Penyampaian modul, praktik, diskusi, dan kuis modul',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Materi mengikuti Materi Event yang dipilih pada seminar.',
            ],
            [
                'start_time' => '11:15',
                'end_time' => '11:40',
                'activity' => 'Praktik microsite / simulasi kasus',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Peserta mengerjakan praktik dan refleksi.',
            ],
            [
                'start_time' => '11:40',
                'end_time' => '11:55',
                'activity' => 'Post-Test peserta',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Post-test dibuka setelah pre-test dan kuis modul selesai.',
            ],
            [
                'start_time' => '11:55',
                'end_time' => '12:00',
                'activity' => 'Penutupan dan dokumentasi',
                'pic' => 'Admin RTIK Local / Tutor',
                'notes' => 'Ambil foto, video slogan, dan cek bukti dukung.',
            ],
        ];
    }
}
