<?php

namespace App\Filament\Pages;

use App\Services\MicrositeLinkChecker;
use App\Support\IdentityNumber;
use App\Filament\Support\UploadTypes;
use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\School;
use App\Models\User;
use Filament\Forms;
use Illuminate\Support\HtmlString;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Akun';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Saya';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.profile';

    public ?array $profileData = [];

    public ?array $passwordData = [];

    public ?array $micrositeData = [];

    public function mount(): void
    {
        $user = auth()->user()?->load(['participant', 'tutor']);

        $this->profileForm->fill([
            'avatar_url' => $user?->avatar_url,
            'name' => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->phone,
            'school_id' => $user?->school_id,
            'participant' => [
                'school_id' => $user?->participant?->school_id ?? $user?->school_id,
                'participant_category' => $user?->participant?->participant_category ?? 'pelajar',
                'nis' => $user?->participant?->nis,
                'nik' => $user?->participant?->nik,
                'grade' => $user?->participant?->grade,
                'gender' => $user?->participant?->gender,
                'birth_date' => $user?->participant?->birth_date,
                'is_cadre' => $user?->participant?->is_cadre ?? false,
            ],
            'tutor' => [
                'school_id' => $user?->tutor?->school_id ?? $user?->school_id,
                'institution' => $user?->tutor?->institution,
                'nik' => $user?->tutor?->nik,
                'npwp' => $user?->tutor?->npwp,
                'bank_name' => $user?->tutor?->bank_name,
                'bank_account_number' => $user?->tutor?->bank_account_number,
                'tot_completed' => $user?->tutor?->tot_completed ?? false,
                'is_cadre' => $user?->tutor?->is_cadre ?? false,
            ],
        ]);

        $event = $this->currentParticipantEvent();
        $practice = $user?->participant?->micrositeForEvent($event);

        $this->micrositeForm->fill([
            'learning_event_id' => $event?->id,
            'sid_url' => MicrositeLinkChecker::sidPath($practice?->sid_url),
            'notes' => $practice?->notes,
        ]);

        $this->passwordForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'micrositeForm',
            'passwordForm',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Profil')
                    ->description('Perbarui foto dan informasi dasar akun yang digunakan untuk login panel.')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Foto Profil')
                            ->image()
                            ->acceptedFileTypes(UploadTypes::IMAGES)
                            ->avatar()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignorable: fn () => auth()->user()),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\Select::make('school_id')
                            ->label('Sekolah Utama')
                            ->disabled(fn () => auth()->user()?->role !== UserRole::Peserta)
                            ->helperText(fn () => auth()->user()?->role !== UserRole::Peserta ? 'Diatur oleh Admin RTIK Pusat.' : null)
                            ->options(fn () => $this->schoolOptions())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Placeholder::make('role_label')
                            ->label('Role')
                            ->content(fn () => auth()->user()?->role?->label() ?? '-'),
                        Forms\Components\Placeholder::make('panel_label')
                            ->label('Panel')
                            ->content(fn () => auth()->user()?->role?->panelId() ?? '-'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Profil Peserta')
                    ->description('Lengkapi data peserta yang dipakai untuk pembelajaran, tes, dan sertifikat.')
                    ->schema([
                        Forms\Components\Select::make('participant.school_id')
                            ->label('Sekolah Peserta')
                            ->options(fn () => $this->schoolOptions())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Wajib untuk kategori pelajar.'),
                        Forms\Components\Select::make('participant.participant_category')
                            ->label('Kategori Peserta')
                            ->options([
                                'pelajar' => 'Pelajar',
                                'mahasiswa' => 'Mahasiswa',
                                'umum' => 'Umum',
                                'karyawan' => 'Karyawan',
                            ])
                            ->native(false)
                            ->required()
                            ->live(),
                        IdentityNumber::nik(Forms\Components\TextInput::make('participant.nik')
                            ->label('NIK')),
                        IdentityNumber::nisn(Forms\Components\TextInput::make('participant.nis')
                            ->label(fn (Forms\Get $get) => $get('participant.participant_category') === 'mahasiswa' ? 'NIM' : 'NISN')
                            ->visible(fn (Forms\Get $get) => in_array($get('participant.participant_category'), ['pelajar', 'mahasiswa'], true)),
                            fn (Forms\Get $get) => $get('participant.participant_category') === 'pelajar'),
                        Forms\Components\Select::make('participant.grade')
                            ->label('Kelas')
                            ->options([
                                'X' => 'X',
                                'XI' => 'XI',
                                'XII' => 'XII',
                            ])
                            ->native(false),
                        Forms\Components\Select::make('participant.gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->native(false),
                        Forms\Components\DatePicker::make('participant.birth_date')
                            ->label('Tanggal Lahir')
                            ->maxDate(now()->subDay()),
                        Forms\Components\Toggle::make('participant.is_cadre')
                            ->label('Status Kader')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn () => auth()->user()?->role === UserRole::Peserta),
                Forms\Components\Section::make('Profil Tutor')
                    ->description('Lengkapi data tutor yang dipakai untuk pengelolaan pendampingan sekolah.')
                    ->schema([
                        Forms\Components\Select::make('tutor.school_id')
                            ->disabled()
                            ->helperText('Diatur oleh Admin RTIK Pusat.')
                            ->label('Sekolah Dampingi')
                            ->options(fn () => $this->schoolOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('tutor.institution')
                            ->label('Institusi')
                            ->maxLength(255),
                        IdentityNumber::nik(Forms\Components\TextInput::make('tutor.nik')
                            ->label('NIK')),
                        Forms\Components\TextInput::make('tutor.npwp')
                            ->label('NPWP')
                            ->maxLength(30),
                        Forms\Components\TextInput::make('tutor.bank_name')
                            ->label('Bank')
                            ->placeholder('Contoh: BRI, BNI, Mandiri, BCA')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('tutor.bank_account_number')
                            ->label('Nomor Rekening')
                            ->maxLength(50),
                        Forms\Components\Toggle::make('tutor.tot_completed')
                            ->label('ToT Selesai')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('tutor.is_cadre')
                            ->label('Status Kader')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->visible(fn () => auth()->user()?->role === UserRole::Tutor),
            ])
            ->statePath('profileData');
    }

    public function micrositeForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Form s.id / Microsite')
                    ->description('Isi tautan s.id sesuai event yang kamu ikuti. Setelah tautan tersimpan, bukti akhir s.id dianggap lengkap.')
                    ->schema([
                        Forms\Components\Select::make('learning_event_id')
                            ->label('Event')
                            ->options(fn () => $this->participantEventOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                $participant = auth()->user()?->participant;
                                $practice = $participant
                                    ? MicrositePractice::query()
                                        ->where('participant_id', $participant->id)
                                        ->where('learning_event_id', $state)
                                        ->latest()
                                        ->first()
                                    : null;

                                $set('sid_url', MicrositeLinkChecker::sidPath($practice?->sid_url));
                                $set('notes', $practice?->notes);
                            }),
                        // Sama dengan kartu Link Microsite di dashboard: peserta cukup mengetik bagian setelah https://s.id/.
                        Forms\Components\TextInput::make('sid_url')
                            ->label('Link s.id')
                            ->prefix('https://s.id/')
                            ->placeholder('Daftar_Peserta')
                            ->helperText(function (Forms\Get $get): HtmlString {
                                $text = e('Ketik nama link s.id Anda setelah https://s.id/ (mis. Daftar_Peserta). Link dicek otomatis dan harus bisa dibuka.');
                                $saved = auth()->user()?->participant?->micrositePractices()
                                    ->where('learning_event_id', $get('learning_event_id'))
                                    ->whereNotNull('sid_url')
                                    ->latest()
                                    ->value('sid_url');

                                // Sama dengan dashboard: link yang sudah tersimpan tampil hijau di bawah input.
                                return new HtmlString($saved
                                    ? $text . '<br><span style="color: #16a34a;">Link tersimpan: <a href="' . e($saved) . '" target="_blank" rel="noopener" style="font-weight: 600; text-decoration: underline; word-break: break-all;">' . e($saved) . '</a></span>'
                                    : $text);
                            })
                            ->extraInputAttributes(['x-on:input' => "\$el.value = \$el.value.replace(/^\\s*(https?:\\/\\/)?(www\\.)?s\\.id\\//i, '')"])
                            ->formatStateUsing(fn (?string $state) => MicrositeLinkChecker::sidPath($state))
                            ->required()
                            ->validationMessages(['required' => 'Link microsite wajib diisi, mis. Daftar_Peserta.'])
                            ->maxLength(240)
                            ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail): void {
                                $result = app(MicrositeLinkChecker::class)->check(static::sidUrl($value));

                                if (! $result['ok']) {
                                    $fail($result['reason']);
                                }
                            })
                            ->dehydrateStateUsing(fn (?string $state) => static::sidUrl($state)),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->placeholder('Opsional, jelaskan isi microsite atau tugas yang dikumpulkan.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn () => auth()->user()?->role === UserRole::Peserta),
            ])
            ->statePath('micrositeData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ganti Password')
                    ->description('Masukkan password saat ini sebelum mengganti password baru.')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Password Saat Ini')
                            ->password()
                            ->revealable()
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->label('Password Baru')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password Baru')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        $data = $this->profileForm->getState();
        $user = auth()->user();

        if (! $user) {
            return;
        }

        // Sekolah akun admin/tutor menentukan cakupan data, jadi hanya bisa diubah Admin RTIK Pusat lewat menu Users.
        $user->update(Arr::only($data, array_filter([
            'avatar_url',
            'name',
            'email',
            'phone',
            $user->role === UserRole::Peserta ? 'school_id' : null,
        ])));

        if ($user->role === UserRole::Peserta) {
            $participantData = $data['participant'] ?? [];
            $schoolId = $participantData['school_id'] ?? $data['school_id'] ?? $user->school_id;

            $user->participant()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $schoolId,
                    'participant_category' => $participantData['participant_category'] ?? 'pelajar',
                    'nis' => in_array($participantData['participant_category'] ?? 'pelajar', ['pelajar', 'mahasiswa'], true)
                        ? ($participantData['nis'] ?? null)
                        : null,
                    'nik' => $participantData['nik'] ?? null,
                    'grade' => $participantData['grade'] ?? null,
                    'gender' => $participantData['gender'] ?? null,
                    'birth_date' => $participantData['birth_date'] ?? null,
                ],
            );

            $user->update(['school_id' => $schoolId]);
        }

        if ($user->role === UserRole::Tutor) {
            $tutorData = $data['tutor'] ?? [];

            $user->tutor()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $user->tutor?->school_id ?? $user->school_id,
                    'institution' => $tutorData['institution'] ?? null,
                    'nik' => $tutorData['nik'] ?? null,
                    'npwp' => $tutorData['npwp'] ?? null,
                    'bank_name' => $tutorData['bank_name'] ?? null,
                    'bank_account_number' => $tutorData['bank_account_number'] ?? null,
                ],
            );
        }

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();
    }

    public function updateMicrosite(): void
    {
        $participant = auth()->user()?->participant;
        $event = LearningEvent::query()->find($this->micrositeData['learning_event_id'] ?? null);

        // Sama dengan dashboard: hanya event yang diikuti dan bukti dukungnya (follow IG & join WAG) sudah lengkap.
        if (! $participant || ! $event || ! $participant->learningEvents()->whereKey($event->id)->exists()) {
            return;
        }

        if (! $participant->isApprovedForEvent($event)) {
            Notification::make()
                ->title('Link s.id belum bisa disimpan')
                ->body('Lengkapi bukti dukung (follow Instagram dan join WAG) di Dashboard terlebih dahulu.')
                ->warning()
                ->send();

            return;
        }

        $data = $this->micrositeForm->getState();

        MicrositePractice::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'learning_event_id' => $data['learning_event_id'],
            ],
            [
                'sid_url' => $data['sid_url'],
                'notes' => $data['notes'] ?? null,
                'status' => 'reviewed',
            ],
        );

        Notification::make()
            ->title(MicrositeLinkChecker::SAVED_TITLE)
            ->body(MicrositeLinkChecker::savedMessage($data['sid_url'], $event->title))
            ->success()
            ->send();
    }

    /** Awalan https://s.id/ selalu dibuat sistem; awalan yang ikut ditempel peserta dibuang. */
    protected static function sidUrl(?string $state): string
    {
        $path = MicrositeLinkChecker::sidPath($state);

        return $path === '' ? '' : 'https://s.id/' . $path;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'passwordData.current_password' => ['required'],
            'passwordData.password' => ['required', 'string', 'min:8'],
            'passwordData.password_confirmation' => ['required', 'same:passwordData.password'],
        ], [
            'passwordData.password_confirmation.same' => 'Konfirmasi password baru tidak sama.',
        ]);

        $user = auth()->user();

        if (! $user || ! Hash::check($this->passwordData['current_password'], $user->password)) {
            $this->addError('passwordData.current_password', 'Password saat ini tidak sesuai.');

            return;
        }

        $user->update([
            'password' => $this->passwordData['password'],
        ]);

        $this->passwordForm->fill();

        Notification::make()
            ->title('Password berhasil diganti')
            ->success()
            ->send();
    }

    private function schoolOptions(): array
    {
        return School::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function participantEventOptions(): array
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return [];
        }

        return LearningEvent::query()
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->orderByDesc('starts_at')
            ->pluck('title', 'id')
            ->all();
    }

    private function currentParticipantEvent(): ?LearningEvent
    {
        $participant = auth()->user()?->participant;

        if (! $participant) {
            return null;
        }

        return LearningEvent::query()
            ->whereHas('participants', fn ($query) => $query->where('participants.id', $participant->id))
            ->orderByDesc('starts_at')
            ->first();
    }
}
