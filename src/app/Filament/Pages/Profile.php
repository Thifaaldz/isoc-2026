<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Filament\Forms;
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
                'nis' => $user?->participant?->nis,
                'grade' => $user?->participant?->grade,
                'gender' => $user?->participant?->gender,
                'birth_date' => $user?->participant?->birth_date,
                'is_cadre' => $user?->participant?->is_cadre ?? false,
            ],
            'tutor' => [
                'school_id' => $user?->tutor?->school_id ?? $user?->school_id,
                'institution' => $user?->tutor?->institution,
                'tot_completed' => $user?->tutor?->tot_completed ?? false,
                'is_cadre' => $user?->tutor?->is_cadre ?? false,
            ],
        ]);

        $this->passwordForm->fill();
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
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
                            ->required(),
                        Forms\Components\TextInput::make('participant.nis')
                            ->label('NIS')
                            ->maxLength(50),
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
                            ->label('Sekolah Dampingi')
                            ->options(fn () => $this->schoolOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('tutor.institution')
                            ->label('Institusi')
                            ->maxLength(255),
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

        $user->update(Arr::only($data, [
            'avatar_url',
            'name',
            'email',
            'phone',
            'school_id',
        ]));

        if ($user->role === UserRole::Peserta) {
            $participantData = $data['participant'] ?? [];
            $schoolId = $participantData['school_id'] ?? $data['school_id'] ?? $user->school_id;

            $user->participant()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $schoolId,
                    'nis' => $participantData['nis'] ?? null,
                    'grade' => $participantData['grade'] ?? null,
                    'gender' => $participantData['gender'] ?? null,
                    'birth_date' => $participantData['birth_date'] ?? null,
                ],
            );

            $user->update(['school_id' => $schoolId]);
        }

        if ($user->role === UserRole::Tutor) {
            $tutorData = $data['tutor'] ?? [];
            $schoolId = $tutorData['school_id'] ?? $data['school_id'] ?? $user->school_id;

            $user->tutor()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $schoolId,
                    'institution' => $tutorData['institution'] ?? null,
                ],
            );

            $user->update(['school_id' => $schoolId]);
        }

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();
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
}
