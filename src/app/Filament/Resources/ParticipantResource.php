<?php

namespace App\Filament\Resources;

use App\Support\IdentityNumber;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ParticipantResource\Pages;
use App\Models\LearningEvent;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParticipantResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'Peserta Terdaftar';

    protected static ?string $pluralModelLabel = 'Peserta Terdaftar';

    protected static ?int $navigationSort = 3;

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
        return 'participant_self';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')->label('Akun Pengguna')->options(fn () => static::scopedUserOptions(UserRole::Peserta))->searchable()->preload()->required(),
            Forms\Components\Select::make('school_id')->label('Sekolah')->options(fn () => static::scopedSchoolOptions())->searchable()->preload(),
            Forms\Components\Select::make('participant_category')->label('Kategori Peserta')->options([
                'pelajar' => 'Pelajar',
                'mahasiswa' => 'Mahasiswa',
                'umum' => 'Umum',
                'karyawan' => 'Karyawan',
            ])->default('pelajar')->required()->live(),
            IdentityNumber::nik(Forms\Components\TextInput::make('nik')->label('NIK')),
            IdentityNumber::nisn(Forms\Components\TextInput::make('nis')
                ->label(fn (Forms\Get $get) => $get('participant_category') === 'mahasiswa' ? 'NIM' : 'NISN')
                ->visible(fn (Forms\Get $get) => in_array($get('participant_category'), ['pelajar', 'mahasiswa'], true)),
                fn (Forms\Get $get) => $get('participant_category') === 'pelajar'),
            Forms\Components\Select::make('grade')->label('Kelas')->options(['X' => 'X', 'XI' => 'XI', 'XII' => 'XII']),
            Forms\Components\TextInput::make('organization')->label('Organisasi / Instansi'),
            Forms\Components\TextInput::make('position')->label('Jabatan / Peran'),
            Forms\Components\Select::make('gender')->label('Jenis Kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
            Forms\Components\DatePicker::make('birth_date')->label('Tanggal Lahir'),
            Forms\Components\DateTimePicker::make('consent_at')->label('Persetujuan data pribadi (consent)'),
            Forms\Components\Toggle::make('followed_instagram')->label('Follow IG'),
            Forms\Components\Toggle::make('joined_wag')->label('Join WAG'),
            Forms\Components\Toggle::make('is_cadre')->label('Kader'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['learningEvents', 'user', 'school']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('participant_category')->label('Kategori')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nis')->label('NISN / NIM')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('grade')->label('Kelas')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('organization')->label('Organisasi')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('position')->label('Jabatan')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('general_approval')
                    ->label('Approval Awal')
                    ->state(fn (Participant $record) => static::generalApprovalStatus($record))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('followed_instagram')->label('IG')->boolean(),
                Tables\Columns\IconColumn::make('joined_wag')->label('WAG')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereHas('learningEvents', fn ($eventQuery) => $eventQuery->where('learning_events.id', $data['value']))
                        : $query),
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Sekolah')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Kelas')
                    ->options(['X' => 'X', 'XI' => 'XI', 'XII' => 'XII']),
                Tables\Filters\SelectFilter::make('participant_category')
                    ->label('Kategori Peserta')
                    ->options([
                        'pelajar' => 'Pelajar',
                        'mahasiswa' => 'Mahasiswa',
                        'umum' => 'Umum',
                        'karyawan' => 'Karyawan',
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
                Tables\Filters\TernaryFilter::make('followed_instagram')
                    ->label('Bukti Follow IG'),
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
            'index' => Pages\ManageParticipant::route('/'),
        ];
    }

    protected static function generalApprovalPivot(Participant $participant): ?object
    {
        // Gunakan relasi yang sudah di-eager-load tabel agar tidak query per baris.
        return $participant->relationLoaded('learningEvents')
            ? $participant->learningEvents->sortByDesc('starts_at')->first()?->pivot
            : $participant->learningEvents()->orderByDesc('starts_at')->first()?->pivot;
    }

    protected static function generalApprovalStatus(Participant $participant): string
    {
        $pivot = static::generalApprovalPivot($participant);

        if (! $pivot) {
            return '-';
        }

        if (($pivot->admin_approval_status ?? null) === 'approved' || ($pivot->tutor_approval_status ?? null) === 'approved') {
            return 'approved';
        }

        if (($pivot->admin_approval_status ?? null) === 'rejected' || ($pivot->tutor_approval_status ?? null) === 'rejected') {
            return 'rejected';
        }

        return 'pending';
    }
}
