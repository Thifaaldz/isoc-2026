<?php

namespace App\Filament\Resources;

use App\Support\IdentityNumber;
use App\Filament\Support\UploadTypes;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\LearningEventResource\Pages;
use App\Filament\Support\SchoolLocationFields;
use App\Models\Evidence;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\LearningMeeting;
use App\Support\TorEventTemplate;
use App\Models\ModuleTemplate;
use App\Models\Partner;
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

    /** Admin RTIK Pusat: jumlah event yang menunggu keputusan (pengajuan, publish, atau laporan final). */
    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()?->role !== UserRole::SuperAdmin) {
            return null;
        }

        $count = LearningEvent::query()->where(fn ($query) => $query
            ->whereIn('workflow_status', ['submitted', 'needs_revision'])
            ->orWhere('publish_approval_status', 'pending')
            ->orWhere('final_report_status', 'submitted'))->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Seminar';

    protected static ?string $modelLabel = 'Seminar / Event';

    protected static ?string $pluralModelLabel = 'Seminar / Event';

    protected static ?string $navigationLabel = 'Kelola Event';

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

    /** Preview mengikuti hak lihat daftar event; cakupan record dibatasi lewat query per role. */
    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    /**
     * Event tiap lokus sudah disiapkan RTIK Pusat (RtikDaerahSeeder), jadi tombol New Event dinonaktifkan untuk semua role.
     * Aktifkan lagi lewat EVENT_CREATION_ENABLED=true.
     */
    public static function canCreate(): bool
    {
        return (bool) config('app.event_creation_enabled') && auth()->user()?->role === UserRole::SuperAdmin;
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
                Forms\Components\Wizard\Step::make('Lokasi')
                    ->description('Data lokasi event dan jadwal pelatihan.')
                    ->icon('heroicon-o-building-office-2')
                    ->schema([
                        Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
                        Forms\Components\TextInput::make('title')
                            ->label('Nama Event / Lokus')
                            ->helperText('Otomatis "' . TorEventTemplate::TITLE_PREFIX . '{kota lokasi}" setelah lokasi dipilih, tetap bisa diubah.')
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
                            ->label('Lokasi Event')
                            ->options(fn () => static::scopedSchoolOptions())
                            ->searchable()
                            ->preload()
                            ->required(fn (Get $get) => $get('audience_type') !== 'general')
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                $title = (string) $get('title');

                                // Nama event mengikuti format TOR selama belum diganti manual.
                                if ($state && ($title === '' || str_starts_with($title, TorEventTemplate::TITLE_PREFIX))) {
                                    $school = School::query()->find($state);
                                    $title = TorEventTemplate::title($school?->city ?: $school?->name);
                                    $set('title', $title);
                                    $set('slug', Str::slug($title));
                                }
                            })
                            ->helperText(fn (Get $get) => $get('audience_type') === 'general'
                                ? 'Opsional untuk event umum.'
                                : 'Wajib untuk event khusus lokasi.')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama lokasi')->required(),
                                Forms\Components\TextInput::make('npsn')->label('NPSN')->maxLength(20)->unique(School::class, 'npsn')
                                    ->validationMessages(['unique' => 'NPSN ini sudah terdaftar. Pilih lokasi tersebut dari daftar Lokasi Event.']),
                                Forms\Components\Select::make('type')->label('Tipe')->options(['SMA' => 'SMA', 'SMK' => 'SMK', 'Tempat' => 'Tempat Event'])->default('SMA')->required(),
                                ...SchoolLocationFields::schema(),
                                Forms\Components\Textarea::make('address')->label('Alamat')->columnSpanFull(),
                                Forms\Components\TextInput::make('maps_url')->label('Link Google Maps')->url()->maxLength(255)->placeholder('https://maps.google.com/...')->columnSpanFull(),
                                Forms\Components\TextInput::make('pic_name')->label('PIC'),
                                Forms\Components\TextInput::make('pic_phone')->label('Kontak PIC'),
                                Forms\Components\TextInput::make('participant_target')->label('Target peserta')->numeric()->default(100),
                                Forms\Components\DatePicker::make('training_date')->label('Tanggal pelatihan'),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $schoolId = School::query()->create($data)->id;
                                static::rememberCreatedSchool($schoolId);

                                return $schoolId;
                            }),
                        Forms\Components\Textarea::make('description')->label('Deskripsi Acara')->default(TorEventTemplate::DESCRIPTION)->columnSpanFull(),
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
                            ->helperText('Pilih materi event tipe Peserta dari Admin RTIK Pusat, lalu centang modul yang akan dibawakan. Sistem akan generate pertemuan, materi, tugas, dan kuis ke seminar ini.')
                            ->options(fn () => ModuleTemplate::query()->forParticipants()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => ModuleTemplate::query()->forParticipants()->where('is_active', true)->orderBy('id')->value('id'))
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $set('selected_meeting_ids', []);
                                static::refreshTorRundown($get, $set);
                            })
                            ->nullable(),
                        Forms\Components\CheckboxList::make('selected_meeting_ids')
                            ->label('Modul yang dibawakan')
                            ->helperText(fn (Get $get) => 'Pilih ' . static::requiredModuleCount($get('module_template_id')) . ' modul. Hanya modul ini yang tampil untuk peserta dan tutor serta masuk ke rundown event.')
                            ->options(fn (Get $get) => static::templateModuleOptions($get('module_template_id')))
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => static::refreshTorRundown($get, $set))
                            ->visible(fn (Get $get) => filled($get('module_template_id')))
                            ->required(fn (Get $get) => filled($get('module_template_id')))
                            ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $required = static::requiredModuleCount($get('module_template_id'));

                                if (count((array) $value) !== $required) {
                                    $fail("Pilih tepat {$required} modul yang akan dibawakan.");
                                }
                            })
                            ->columns(2)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('partners')
                            ->label('Mitra Event')
                            ->helperText('Otomatis mitra kolaborasi sesuai TOR. Centang di bawah untuk menentukan logo mitra yang masuk ke sertifikat.')
                            ->relationship('partners', 'name', fn ($query) => $query->where('status', 'active')->orderBy('name'))
                            ->default(fn () => TorEventTemplate::partnerIds())
                            ->live()
                            ->afterStateUpdated(function ($state, $old, Get $get, Set $set): void {
                                // Mitra baru otomatis dicentang; mitra yang dihapus ikut keluar dari checklist sertifikat.
                                $selected = array_map('strval', (array) $state);
                                $checked = array_map('strval', (array) ($get('certificate_partner_ids') ?? []));
                                $added = array_diff($selected, array_map('strval', (array) $old));
                                $set('certificate_partner_ids', array_values(array_intersect($selected, array_unique([...$checked, ...$added]))));
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->label('Nama Mitra')->required(),
                                Forms\Components\Select::make('category')
                                    ->label('Kategori')
                                    ->options([
                                        'international' => 'Internasional',
                                        'national' => 'Nasional',
                                        'local' => 'Lokal',
                                        'school' => 'Sekolah / Kampus',
                                        'community' => 'Komunitas',
                                    ])
                                    ->default('national')
                                    ->required(),
                                Forms\Components\FileUpload::make('logo_path')
                                    ->label('Logo Mitra')
                                    ->image()
                                    ->acceptedFileTypes(UploadTypes::IMAGES)
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('partners')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('website_url')->label('Website')->url()->columnSpanFull(),
                                Forms\Components\Hidden::make('status')->default('active'),
                            ])
                            ->createOptionUsing(fn (array $data) => Partner::query()->create($data)->id)
                            ->columnSpanFull(),
                        Forms\Components\CheckboxList::make('certificate_partner_ids')
                            ->label('Logo mitra di sertifikat')
                            ->helperText('Hanya logo mitra yang dicentang yang tampil di sertifikat peserta.')
                            ->options(fn (Get $get) => Partner::query()
                                ->whereIn('id', (array) ($get('partners') ?? []))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->mapWithKeys(fn ($name, $id) => [(string) $id => $name])
                                ->all())
                            ->default(fn () => TorEventTemplate::partnerIds())
                            ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?LearningEvent $record): void {
                                if ($record?->exists) {
                                    $component->state($record->certificatePartners()->pluck('partners.id')->map(fn ($id) => (string) $id)->all());
                                }
                            })
                            ->visible(fn (Get $get) => filled($get('partners')))
                            ->dehydrated(false)
                            ->bulkToggleable()
                            ->columns(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('certificate_template_id')
                            ->label('Template Sertifikat')
                            ->helperText('Template ini dipakai otomatis untuk sertifikat peserta event ini.')
                            ->options(fn () => CertificateTemplate::query()
                                ->orderByDesc('is_default')
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->default(fn () => CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->value('id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Tanggal & Jam Mulai')
                            ->seconds(false)
                            ->native(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $old, Get $get, Set $set): void {
                                // Saat tanggal pertama kali dipilih, jam mulai otomatis 09:00 sesuai TOR.
                                if (filled($state) && blank($old)) {
                                    $state = Carbon::parse($state)->setTimeFromTimeString(TorEventTemplate::DEFAULT_START)->format('Y-m-d H:i:s');
                                    $set('starts_at', $state);
                                }

                                $set('training_start_time', static::timeFromDateTime($state, TorEventTemplate::DEFAULT_START));

                                // Durasi kegiatan TOR 180 menit: jam selesai otomatis, tetap bisa diubah.
                                if (filled($state)) {
                                    $endsAt = Carbon::parse($state)->addMinutes(TorEventTemplate::DURATION_MINUTES);
                                    $set('ends_at', $endsAt->format('Y-m-d H:i:s'));
                                    $set('training_end_time', $endsAt->format('H:i'));
                                }

                                static::refreshTorRundown($get, $set);
                            })
                            ->helperText('Pilih tanggal; jam mulai otomatis 09:00, jam selesai 12:00, dan rundown mengikuti TOR (180 menit). Jam tetap bisa diubah.')
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
                        Forms\Components\TextInput::make('target_participants')->label('Target Peserta')->numeric()->default(TorEventTemplate::DEFAULT_PARTICIPANTS)->required(),
                        Forms\Components\TextInput::make('target_tutors')->label('Target Tutor')->numeric()->default(TorEventTemplate::DEFAULT_TUTORS)->required(),
                        Forms\Components\FileUpload::make('preparation_document')
                            ->acceptedFileTypes(UploadTypes::documents())
                            ->label('Surat/MoU/Berita Acara Persiapan')
                            ->disk('public')
                            ->directory('event-documents'),
                        Forms\Components\Textarea::make('local_admin_notes')
                            ->label('Catatan Fasilitator')
                            ->rows(3),
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
                                IdentityNumber::nik(Forms\Components\TextInput::make('nik')
                                    ->label('NIK')),
                                IdentityNumber::nisn(Forms\Components\TextInput::make('nis')
                                    ->label('NISN'))
                                    ->helperText('Khusus Lokasi (pelajar) dapat mengisi NISN selain NIK.')
                                    ->visible(fn (Get $get) => $get('../../audience_type') !== 'general'),
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
                    ->description('Pilih tutor yang sudah terdaftar atau tambah tutor baru.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Section::make('Tutor yang sudah terdaftar')
                            ->description('Pilih tutor yang sudah memiliki akun di sistem. Tutor terpilih ditugaskan ke event saat event disetujui RTIK Pusat.')
                            ->schema([
                                Forms\Components\Select::make('selected_tutor_ids')
                                    ->hiddenLabel()
                                    ->placeholder('Cari nama, email, atau lembaga tutor')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn () => static::registeredTutorOptions())
                                    ->helperText('Tutor yang sama tidak perlu ditambahkan lagi di bagian Tambah tutor baru.'),
                            ])
                            ->columnSpanFull(),
                        Forms\Components\Section::make('Tambah tutor baru')
                            ->description('Isi manual atau upload Excel untuk tutor yang belum punya akun. Akun tutor dibuat otomatis saat event disetujui RTIK Pusat (password awal: password).')
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
                            ->label('Data tutor baru')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('Nama lengkap')->required(),
                                Forms\Components\TextInput::make('phone')->label('Kontak'),
                                Forms\Components\TextInput::make('email')->label('Email')->email(),
                                Forms\Components\TextInput::make('institution')->label('Institusi / Lembaga'),
                                IdentityNumber::nik(Forms\Components\TextInput::make('nik')->label('NIK')),
                                Forms\Components\TextInput::make('npwp')->label('NPWP')->maxLength(30),
                                Forms\Components\TextInput::make('bank_name')->label('Bank')->maxLength(100),
                                Forms\Components\TextInput::make('bank_account_number')->label('Nomor Rekening')->maxLength(50),
                                Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah tutor baru')
                            ->collapsible()
                            ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Wizard\Step::make('Rundown Acara')
                    ->description('Susun alur kegiatan seminar sesuai durasi acara.')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Section::make('Rundown Acara')
                            ->description('Otomatis mengikuti susunan acara TOR (180 menit) dari jam mulai dan 2 modul yang dipilih. Admin daerah tetap bisa mengubah jam, agenda, PIC, dan catatan.')
                            ->schema([
                                Forms\Components\Repeater::make('rundown_items')
                                    ->hiddenLabel()
                                    ->schema(static::rundownItemSchema())
                                    ->columns(12)
                                    ->default(fn () => static::keyedItems(TorEventTemplate::rundown()))
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
                Forms\Components\Wizard\Step::make('Checklist Termin')
                    ->description('Checklist keperluan anggaran Termin-1 dan Termin-2 sesuai TOR.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        ...collect(TorEventTemplate::TERM_CHECKLIST)->map(fn (array $options, int $term) => Forms\Components\Section::make("Termin-{$term}")
                            ->description(TorEventTemplate::TERM_NOTES[$term])
                            ->schema([
                                Forms\Components\CheckboxList::make("term_checklist_{$term}")
                                    ->label("Keperluan Termin-{$term}")
                                    ->options($options)
                                    ->bulkToggleable()
                                    ->afterStateHydrated(fn (Forms\Components\CheckboxList $component, ?LearningEvent $record) => $component->state(TorEventTemplate::checkedKeys($record?->budget_items, $term)))
                                    ->dehydrated(false),
                            ]))->values()->all(),
                        // Checklist kedua termin disimpan di kolom budget_items.
                        Forms\Components\Hidden::make('budget_items')
                            ->dehydrateStateUsing(fn (Get $get) => TorEventTemplate::termChecklist(array_merge(
                                (array) ($get('term_checklist_1') ?? []),
                                (array) ($get('term_checklist_2') ?? []),
                            ))),
                    ])
                    ->columns(1),
            ])
                ->persistStepInQueryString()
                // Mode preview: semua langkah bisa dibuka langsung tanpa validasi.
                ->skippable(fn (string $operation) => $operation === 'view')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        // RTIK Pusat (Super Admin) melihat event sebagai card per lokasi; klik card membuka popup detail event.
        if (auth()->user()?->role === UserRole::SuperAdmin) {
            $table = $table
                ->contentGrid(['md' => 2, 'xl' => 3])
                ->paginated([12, 24, 48, 'all'])
                ->defaultPaginationPageOption(12)
                ->recordUrl(null)
                ->recordAction('detail');
        }

        return $table
            // Hitung tutor & tutor lulus ToT sekali di query (bukan per baris).
            ->modifyQueryUsing(fn ($query) => $query
                ->withCount('tutors')
                ->withCount(['totAssessments as tot_passed_count' => fn ($totQuery) => $totQuery
                    ->where('is_perfect', true)
                    ->select(\Illuminate\Support\Facades\DB::raw('count(distinct tutor_id)'))]))
            ->columns(auth()->user()?->role === UserRole::SuperAdmin ? static::cardColumns() : [
                Tables\Columns\TextColumn::make('title')->label('Event')->searchable()->sortable(),
                // Nama event sudah memuat kota lokasi, jadi kolom lokasi disembunyikan default agar status tidak terpotong.
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipe')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::eventTypeOptions()[$state ?: 'offline'] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'webinar' => 'info',
                        'hybrid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('audience_type')
                    ->label('Kategori')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::audienceTypeOptions()[$state ?: 'school'] ?? $state)
                    ->color(fn (?string $state) => $state === 'general' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('zoom_url')
                    ->label('Zoom')
                    ->limit(26)
                    ->url(fn (?string $state) => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('moduleTemplate.name')->label('Materi Event')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('partners.name')
                    ->label('Mitra')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('certificateTemplate.name')
                    ->label('Template Sertifikat')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('starts_at')->label('Jadwal')->dateTime('d M Y, H:i')->sortable(),
                Tables\Columns\TextColumn::make('participants_count')->counts('participants')->label('Peserta')->sortable(),
                Tables\Columns\TextColumn::make('tutors_count')->counts('tutors')->label('Tutor')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tot_progress')
                    ->label('ToT')
                    ->state(fn (LearningEvent $record): string => (int) $record->tot_passed_count . '/' . (int) $record->tutors_count)
                    ->badge()
                    ->color(fn (LearningEvent $record): string => $record->tutors_count > 0 && $record->tot_passed_count >= $record->tutors_count ? 'success' : 'warning')
                    ->tooltip('Jumlah tutor yang sudah lulus ToT sempurna.'),
                Tables\Columns\TextColumn::make('meetings_count')->counts('meetings')->label('Pertemuan')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('term_checklist')
                    ->label('Checklist Termin')
                    ->state(fn (LearningEvent $record): string => collect(TorEventTemplate::TERM_CHECKLIST)
                        ->map(fn (array $options, int $term) => "T{$term} " . count(TorEventTemplate::checkedKeys($record->budget_items, $term)) . '/' . count($options))
                        ->implode(' · '))
                    ->toggleable(isToggledHiddenByDefault: true),
                // Status ringkas dalam bahasa sederhana (sama dengan kartu event Admin RTIK Daerah).
                Tables\Columns\TextColumn::make('workflow_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (LearningEvent $record) => (new \App\Support\EventOverview($record))->status()['label'])
                    ->color(fn (LearningEvent $record) => (new \App\Support\EventOverview($record))->status()['color'])
                    ->tooltip(fn (?string $state) => static::workflowStatusOptions()[$state] ?? $state),
                Tables\Columns\TextColumn::make('publish_approval_status')
                    ->label('Approval Publish')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::publishApprovalStatusOptions()[$state ?: 'draft'] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'revision' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('final_report_status')
                    ->label('Laporan Final')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => static::finalReportStatusOptions()[$state ?: 'draft'] ?? $state)
                    ->color(fn (?string $state) => match ($state) {
                        'approved' => 'success',
                        'submitted' => 'warning',
                        'revision' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')->label('Publik')->badge()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('local_updated_at')
                    ->label('Update Daerah')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('central_admin_notes')
                    ->label('Catatan Admin RTIK Pusat')
                    ->limit(45)
                    ->tooltip(fn (LearningEvent $record) => $record->central_admin_notes)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('local_admin_notes')
                    ->label('Catatan Fasilitator')
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
                    ->label('Lokasi')
                    ->options(fn () => static::scopedSchoolOptions()),
                Tables\Filters\SelectFilter::make('workflow_status')
                    ->label('Workflow')
                    ->options(static::workflowStatusOptions()),
                Tables\Filters\SelectFilter::make('publish_approval_status')
                    ->label('Approval Publish')
                    ->options(static::publishApprovalStatusOptions()),
                Tables\Filters\SelectFilter::make('final_report_status')
                    ->label('Laporan Final')
                    ->options(static::finalReportStatusOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Ditutup']),
                Tables\Filters\TernaryFilter::make('registration_open')
                    ->label('Pendaftaran dibuka'),
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publish'),
            ])
            ->actions(static::cardActions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin)
                    ->modalHeading(fn (LearningEvent $record) => $record->title)
                    ->modalDescription(fn (LearningEvent $record) => $record->school?->name)
                    ->modalContent(fn (LearningEvent $record) => view('filament.resources.learning-event.detail-modal', ['event' => $record]))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\ViewAction::make()
                    ->label('Preview')
                    ->icon('heroicon-o-eye'),
                ...static::workflowActions(Tables\Actions\Action::class),
                static::submitFinalReportAction(Tables\Actions\Action::class),
                Tables\Actions\Action::make('approveFinalReport')
                    ->label('Approve Laporan + T2')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && ($record->final_report_status ?? 'draft') === 'submitted')
                    ->form([
                        Forms\Components\Textarea::make('final_report_notes')
                            ->label('Catatan approval')
                            ->rows(3),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update(['workflow_status' => 'verified_term_2']);
                        $record->update([
                            'workflow_status' => 'verified_term_2',
                            'final_report_status' => 'approved',
                            'final_report_approved_by' => auth()->id(),
                            'final_report_approved_at' => now(),
                            'final_report_notes' => $data['final_report_notes'] ?? null,
                        ]);
                        $record->payments()->where('term', 2)->update([
                            'status' => 'eligible',
                            'notes' => $data['final_report_notes'] ?? null,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Laporan final approved')
                            ->body('Termin-2 sekarang eligible.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reviseFinalReport')
                    ->label('Revisi Laporan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin && ($record->final_report_status ?? 'draft') === 'submitted')
                    ->form([
                        Forms\Components\Textarea::make('final_report_notes')
                            ->label('Catatan revisi')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (LearningEvent $record, array $data): void {
                        $record->update([
                            'workflow_status' => 'verified_term_1',
                            'final_report_status' => 'revision',
                            'final_report_notes' => $data['final_report_notes'],
                        ]);

                        Notification::make()
                            ->title('Laporan dikembalikan untuk revisi')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\ActionGroup::make([
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
                    Tables\Actions\Action::make('printWetAttendance')
                        ->label('Template Absensi Basah')
                        ->icon('heroicon-o-printer')
                        ->url(fn (LearningEvent $record) => route('events.attendance.wet', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('printDigitalAttendance')
                        ->label('Cetak Absensi Online')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->url(fn (LearningEvent $record) => route('events.attendance.digital', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('previewFinalReport')
                        ->label('Preview Laporan')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->url(fn (LearningEvent $record) => route('reports.events.activity.preview', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('downloadFinalReport')
                        ->label('Download Laporan')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (LearningEvent $record) => route('reports.events.activity.download', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                            || (auth()->user()?->role === UserRole::Admin && ! in_array($record->workflow_status, ['cancelled', 'closed'], true))),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin),
                ])
                    ->label('Lainnya')
                    ->tooltip('Aksi lainnya'),
            ]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->role === UserRole::SuperAdmin),
                ]),
            ]);
    }

    /**
     * Aksi alur pengajuan event (submit, approve, publish, revisi, cancel). Dipakai di tabel Kelola Seminar
     * dan di halaman Preview event, dengan $class Tables\Actions\Action atau Filament\Actions\Action.
     *
     * @param  class-string  $class
     * @return array<int, \Filament\Actions\Action|Tables\Actions\Action>
     */
    /** Kirim laporan final ke Admin RTIK Pusat bila semua syarat lengkap; mengembalikan true bila terkirim. */
    public static function submitFinalReport(LearningEvent $record): bool
    {
        $missing = static::missingFinalReportRequirements($record);

        if ($missing !== []) {
            Notification::make()
                ->title('Laporan belum lengkap')
                ->body('Lengkapi dulu: ' . implode(', ', $missing))
                ->danger()
                ->send();

            return false;
        }

        $record->update([
            'workflow_status' => 'final_report_submitted',
            'final_report_status' => 'submitted',
            'final_report_submitted_at' => now(),
            'final_report_notes' => null,
            'local_updated_at' => now(),
            'local_update_summary' => 'Laporan kegiatan final dikirim ke Admin RTIK Pusat.',
        ]);

        Notification::make()
            ->title('Laporan final dikirim')
            ->body('Admin RTIK Pusat dapat membuka preview laporan dan approve Termin-2.')
            ->success()
            ->send();

        return true;
    }

    /** Aksi Admin RTIK Daerah mengirim laporan final (dipakai di tabel dan halaman event). */
    public static function submitFinalReportAction(string $class)
    {
        return $class::make('submitFinalReport')
            ->label('Submit Laporan Final')
            ->icon('heroicon-o-paper-airplane')
            ->color('warning')
            ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::Admin
                && in_array($record->workflow_status, ['verified_term_1', 'tot_completed', 'field_training_completed', 'verified_term_2'], true)
                && in_array($record->final_report_status ?? 'draft', ['draft', 'revision'], true))
            ->requiresConfirmation()
            ->modalDescription('Sistem akan membuat laporan kegiatan dari data event, rundown, materi, nilai, dan bukti dukung. Laporan dikirim ke Admin RTIK Pusat untuk approval Termin-2.')
            ->action(fn (LearningEvent $record) => static::submitFinalReport($record));
    }

    public static function workflowActions(string $class): array
    {
        return [
                $class::make('submitApplication')
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
                $class::make('verifyTerm1')
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
                $class::make('requestPublishApproval')
                    ->label('Ajukan Publish')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::Admin
                        && in_array($record->workflow_status, static::publishableWorkflowStatuses(), true)
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
                $class::make('approvePublish')
                    ->label('Publish + T1')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                        && in_array($record->workflow_status, static::publishableWorkflowStatuses(), true)
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
                $class::make('requestPublishRevision')
                    ->label('Revisi Publish')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (LearningEvent $record) => auth()->user()?->role === UserRole::SuperAdmin
                        && in_array($record->workflow_status, static::publishableWorkflowStatuses(), true)
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
                $class::make('requestRevision')
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
                $class::make('cancelApplication')
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
        ];
    }

    /**
     * Card Super Admin: tombol Detail tetap terlihat, aksi lain dirangkum ke dropdown "Aksi" agar tidak keluar dari card.
     * Role lain tetap memakai deretan aksi tabel seperti biasa.
     */
    protected static function cardActions(array $actions): array
    {
        if (auth()->user()?->role !== UserRole::SuperAdmin) {
            return $actions;
        }

        [$detail, $others] = collect($actions)->partition(fn ($action) => $action instanceof Tables\Actions\Action && $action->getName() === 'detail');

        return [
            ...$detail->all(),
            Tables\Actions\ActionGroup::make($others
                ->map(fn ($action) => $action instanceof Tables\Actions\ActionGroup ? $action->dropdown(false) : $action)
                ->values()
                ->all())
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->button()
                ->size('sm')
                ->color('gray'),
        ];
    }

    /** Isi card event per lokasi (tampilan Super Admin). */
    protected static function cardColumns(): array
    {
        $overview = fn (LearningEvent $record) => new \App\Support\EventOverview($record);

        return [
            Tables\Columns\Layout\Stack::make([
                Tables\Columns\TextColumn::make('workflow_status')
                    ->badge()
                    ->formatStateUsing(fn (LearningEvent $record) => $overview($record)->status()['label'])
                    ->color(fn (LearningEvent $record) => $overview($record)->status()['color']),
                Tables\Columns\TextColumn::make('school.name')
                    ->icon('heroicon-m-map-pin')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                    ->placeholder('Lokasi belum diatur')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')->color('gray')->wrap()->searchable()->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->icon('heroicon-m-calendar-days')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Jadwal belum diatur')
                    ->sortable(),
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\TextColumn::make('participants_count')
                        ->counts('participants')
                        ->icon('heroicon-m-user-group')
                        ->formatStateUsing(fn ($state) => "{$state} peserta")
                        ->sortable(),
                    Tables\Columns\TextColumn::make('tot_progress')
                        ->icon('heroicon-m-academic-cap')
                        ->state(fn (LearningEvent $record): string => 'ToT ' . (int) $record->tot_passed_count . '/' . (int) $record->tutors_count),
                    Tables\Columns\IconColumn::make('is_published')
                        ->boolean()
                        ->tooltip(fn (LearningEvent $record) => $record->is_published ? 'Sudah publish' : 'Belum publish')
                        ->grow(false),
                ]),
                Tables\Columns\TextColumn::make('final_report_status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => 'Laporan: ' . (static::finalReportStatusOptions()[$state ?: 'draft'] ?? $state))
                    ->color(fn (?string $state) => match ($state) {
                        'approved' => 'success',
                        'submitted' => 'warning',
                        'revision' => 'danger',
                        default => 'gray',
                    }),
            ])->space(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningEvents::route('/'),
            'create' => Pages\CreateLearningEvent::route('/create'),
            'view' => Pages\ViewLearningEvent::route('/{record}'),
            'edit' => Pages\EditLearningEvent::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> Tutor aktif yang sudah terdaftar, untuk dipilih di wizard event. */
    public static function registeredTutorOptions(): array
    {
        return \App\Models\Tutor::query()
            ->with(['user', 'school'])
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            // Admin RTIK Daerah hanya memilih tutor dari lokus yang dikelolanya.
            ->when(auth()->user()?->role === UserRole::Admin, fn ($query) => $query->whereIn('school_id', static::managedSchoolIds()))
            ->get()
            ->sortBy(fn ($tutor) => $tutor->user?->name)
            ->mapWithKeys(fn ($tutor) => [$tutor->id => collect([
                $tutor->user?->name,
                $tutor->user?->email,
                $tutor->institution ?: $tutor->school?->name,
            ])->filter()->implode(' · ') . ($tutor->tot_completed ? ' · ToT lulus' : '')])
            ->all();
    }

    /** Field yang hanya boleh diubah Admin RTIK Pusat (di form hanya tampil sebagai read-only untuk role lain). */
    public static function centralOnlyFields(): array
    {
        return ['workflow_status', 'publish_approval_status', 'registration_open', 'is_published'];
    }

    /** Status event yang sudah di-approve Pusat dan masih boleh diajukan/di-publish. */
    public static function publishableWorkflowStatuses(): array
    {
        return ['verified_term_1', 'tot_in_progress', 'tot_completed'];
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
            'closed' => 'Closed',
            'needs_revision' => 'Needs Revision',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled by Admin RTIK Pusat',
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
            'school' => 'Khusus Lokasi',
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

    public static function finalReportStatusOptions(): array
    {
        return [
            'draft' => 'Belum disubmit',
            'submitted' => 'Menunggu approval Admin RTIK Pusat',
            'revision' => 'Perlu revisi',
            'approved' => 'Approved',
        ];
    }

    /** @return array<int, string> */
    public static function missingFinalReportRequirements(LearningEvent $event): array
    {
        $event->loadMissing(['participants', 'meetings', 'assessments', 'evidences']);

        $missing = [];
        if (! $event->attendanceProofComplete()) {
            $missing[] = $event->attendance_proof_mode === 'system' ? 'daftar hadir (PDF belum digenerate)' : 'absensi basah / daftar hadir';
        }

        if (! $event->proofComplete('microsite')) {
            $missing[] = $event->proofMode('microsite') === 'system' ? 'rekap microsite (PDF belum digenerate)' : 'bukti hasil microsite';
        }

        $requiredEvidence = [
            'video_slogan' => 'video slogan',
        ];

        foreach ($requiredEvidence as $type => $label) {
            $exists = $event->evidences
                ->where('type', $type)
                ->where('status', 'approved')
                ->isNotEmpty();

            if (! $exists) {
                $missing[] = $label;
            }
        }

        array_push($missing, ...\App\Models\Evidence::missingRequiredPhotos($event->id));

        if ($event->participants->isEmpty()) {
            $missing[] = 'daftar registrasi peserta';
        }

        if (! $event->assessments->where('type', 'pre')->count()) {
            $missing[] = 'pre-test';
        }

        if (! $event->assessments->where('type', 'post')->count()) {
            $missing[] = 'post-test';
        }

        if ($event->meetings->isEmpty()) {
            $missing[] = 'materi/pertemuan';
        }

        return $missing;
    }

    /** Simpan centang "logo di sertifikat" ke pivot mitra event. */
    public static function syncCertificatePartners(LearningEvent $event, array $checkedPartnerIds): void
    {
        $checked = array_map('intval', $checkedPartnerIds);

        foreach ($event->partners()->pluck('partners.id') as $partnerId) {
            $event->partners()->updateExistingPivot($partnerId, ['show_on_certificate' => in_array((int) $partnerId, $checked, true)]);
        }
    }

    /** Susun ulang rundown TOR dari jam mulai dan modul yang dipilih di wizard. */
    public static function refreshTorRundown(Get $get, Set $set): void
    {
        $titles = LearningMeeting::query()
            ->whereIn('id', array_map('intval', (array) ($get('selected_meeting_ids') ?? [])))
            ->orderBy('order')
            ->pluck('title')
            ->all();

        $set('rundown_items', static::keyedItems(TorEventTemplate::rundown(
            static::timeFromDateTime($get('starts_at'), TorEventTemplate::DEFAULT_START),
            $titles,
        )));
    }

    /** Item repeater Filament memakai key unik per baris. */
    private static function keyedItems(array $items): array
    {
        return collect($items)->mapWithKeys(fn (array $item) => [(string) Str::uuid() => $item])->all();
    }

    /** @return array<int|string, string> Pertemuan Materi Event yang bisa dipilih sebagai modul event. */
    public static function templateModuleOptions(mixed $templateId): array
    {
        if (! $templateId) {
            return [];
        }

        return LearningMeeting::query()
            ->where('module_template_id', $templateId)
            ->whereNull('learning_event_id')
            ->orderBy('order')
            ->get()
            ->mapWithKeys(fn (LearningMeeting $meeting) => [(string) $meeting->id => $meeting->title . ' (' . ($meeting->duration_minutes ?: 25) . ' menit)'])
            ->all();
    }

    public static function requiredModuleCount(mixed $templateId): int
    {
        return min(LearningEvent::MODULES_PER_EVENT, max(1, count(static::templateModuleOptions($templateId))));
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
        return TorEventTemplate::rundown();
    }
}
