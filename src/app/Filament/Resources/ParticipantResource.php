<?php

namespace App\Filament\Resources;

use App\Support\IdentityNumber;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ParticipantResource\Pages;
use App\Filament\Support\ParticipantProgressActions;
use App\Models\Participant;
use App\Services\EventAttendanceService;
use App\Services\ParticipantProgressEditor;
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
            ->columns(static::isTutor() ? static::tutorColumns() : static::fullColumns())
            ->filters([
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
                ParticipantProgressActions::group(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    protected static function isTutor(): bool
    {
        return auth()->user()?->role === UserRole::Tutor;
    }

    /** Kolom lengkap untuk Admin RTIK (data diri, approval, bukti dukung, dan progres event yang dibuka). */
    protected static function fullColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('user.name')->label('Nama')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('participant_category')->label('Kategori')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('nis')->label('NISN / NIM')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('grade')->label('Kelas')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('organization')->label('Organisasi')->searchable()->toggleable(),
            Tables\Columns\TextColumn::make('position')->label('Jabatan')->searchable()->toggleable(),
            static::approvalColumn(),
            Tables\Columns\IconColumn::make('followed_instagram')->label('IG')->boolean(),
            Tables\Columns\IconColumn::make('joined_wag')->label('WAG')->boolean(),
            static::attendanceColumn(),
            static::scoreColumn('pre', 'Pre-Test'),
            static::scoreColumn('post', 'Post-Test'),
            static::micrositeColumn(),
        ];
    }

    /**
     * Kolom ringkas untuk tutor agar tabel muat tanpa geser ke kanan: identitas digabung di kolom Peserta,
     * IG/WAG di satu kolom, Pre/Post-Test di satu kolom. Data diri lain bisa dimunculkan lewat pengaturan kolom.
     */
    protected static function tutorColumns(): array
    {
        $hidden = fn (Tables\Columns\Column $column) => $column->toggleable(isToggledHiddenByDefault: true);

        return [
            Tables\Columns\TextColumn::make('user.name')->label('Peserta')->weight('bold')->wrap()
                ->searchable(['name', 'email'])->sortable()
                ->description(fn (Participant $record) => collect([
                    $record->user?->email,
                    collect([$record->school?->name, $record->grade ? 'Kelas ' . $record->grade : null])->filter()->implode(' · '),
                ])->filter()->implode(' — ')),
            Tables\Columns\TextColumn::make('bukti_dukung')->label('Bukti Dukung')->badge()
                ->state(fn () => ['IG', 'WAG'])
                ->icon(fn (string $state, Participant $record) => static::proofDone($record, $state) ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color(fn (string $state, Participant $record) => static::proofDone($record, $state) ? 'success' : 'gray'),
            static::attendanceColumn(),
            Tables\Columns\TextColumn::make('event_scores')->label('Nilai')->badge()
                ->visible(fn ($livewire) => ParticipantProgressActions::event($livewire) !== null)
                ->state(fn (Participant $record, $livewire) => ($event = ParticipantProgressActions::event($livewire))
                    ? collect(['pre' => 'Pre', 'post' => 'Post'])->map(fn (string $label, string $type) => $label . ' ' . static::formatScore(app(ParticipantProgressEditor::class)->score($event, $record, $type)))->values()->all()
                    : [])
                ->color(fn (string $state) => str_ends_with($state, '-') ? 'gray' : 'info'),
            static::micrositeColumn()->formatStateUsing(fn () => 'Buka')->icon('heroicon-m-arrow-top-right-on-square'),
            $hidden(static::approvalColumn()),
            $hidden(Tables\Columns\TextColumn::make('participant_category')->label('Kategori')->badge()),
            $hidden(Tables\Columns\TextColumn::make('nik')->label('NIK')->searchable()),
            $hidden(Tables\Columns\TextColumn::make('nis')->label('NISN / NIM')->searchable()),
            $hidden(Tables\Columns\TextColumn::make('organization')->label('Organisasi')),
            $hidden(Tables\Columns\TextColumn::make('position')->label('Jabatan')),
        ];
    }

    protected static function proofDone(Participant $record, string $proof): bool
    {
        return (bool) ($proof === 'IG' ? $record->followed_instagram : $record->joined_wag);
    }

    protected static function formatScore(?float $score): string
    {
        return $score === null ? '-' : rtrim(rtrim(number_format($score, 1, ',', ''), '0'), ',');
    }

    protected static function approvalColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('general_approval')
            ->label('Approval Awal')
            ->state(fn (Participant $record) => static::generalApprovalStatus($record))
            ->badge()
            ->color(fn (string $state) => match ($state) {
                'approved' => 'success',
                'rejected' => 'danger',
                'pending' => 'warning',
                default => 'gray',
            });
    }

    // Progres di event yang sedang dibuka; diubah lewat tombol Kelola.
    protected static function attendanceColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('event_attendance')->label('Absensi')->badge()->placeholder('Belum')
            ->visible(fn ($livewire) => ParticipantProgressActions::event($livewire) !== null)
            ->state(fn (Participant $record, $livewire) => ($event = ParticipantProgressActions::event($livewire))
                ? ParticipantProgressEditor::ATTENDANCE_STATUSES[app(EventAttendanceService::class)->attendanceFor($record, $event)?->status] ?? null
                : null)
            ->color(fn (string $state) => $state === 'Hadir' ? 'success' : 'warning');
    }

    protected static function scoreColumn(string $type, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make("event_{$type}_score")->label($label)->numeric(1)->placeholder('-')
            ->visible(fn ($livewire) => ParticipantProgressActions::event($livewire) !== null)
            ->state(fn (Participant $record, $livewire) => ($event = ParticipantProgressActions::event($livewire))
                ? app(ParticipantProgressEditor::class)->score($event, $record, $type)
                : null);
    }

    protected static function micrositeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('event_microsite')->label('Link s.id')->placeholder('Belum')->limit(30)
            ->visible(fn ($livewire) => ParticipantProgressActions::event($livewire) !== null)
            ->state(fn (Participant $record, $livewire) => ($event = ParticipantProgressActions::event($livewire))
                ? app(ParticipantProgressEditor::class)->micrositeUrl($event, $record)
                : null)
            ->url(fn (?string $state) => $state, true)
            ->color('success');
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
