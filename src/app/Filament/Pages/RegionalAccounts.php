<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RegionalAccountService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/** Admin RTIK Daerah: reset password semua akun daerah (tutor & peserta di event miliknya) atau ubah password per akun. */
class RegionalAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $navigationLabel = 'Akun & Password';

    protected static ?string $title = 'Akun & Password Daerah';

    protected static ?string $slug = 'akun-password';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.regional-accounts';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function getSubheading(): ?string
    {
        return 'Tutor dan peserta yang terdaftar di event daerah Anda. Password baru langsung berlaku; sampaikan ke pemilik akun.';
    }

    protected function service(): RegionalAccountService
    {
        return app(RegionalAccountService::class);
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function passwordFields(): array
    {
        return [
            Forms\Components\TextInput::make('password')
                ->label('Password baru')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->confirmed(),
            Forms\Components\TextInput::make('password_confirmation')
                ->label('Ulangi password baru')
                ->password()
                ->revealable()
                ->required(),
        ];
    }

    protected function notifyReset(int $count): void
    {
        Notification::make()
            ->title("Password {$count} akun berhasil direset")
            ->body('Sampaikan password baru kepada pemilik akun. Sesi login lama mereka otomatis berakhir.')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        $roles = collect(RegionalAccountService::ROLES)->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()]);

        return $table
            ->query(fn () => $this->service()->accountsQuery(auth()->user())->with('school'))
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('role')->label('Role')->badge()
                    ->formatStateUsing(fn (UserRole $state) => $state->label())
                    ->color(fn (UserRole $state) => $state === UserRole::Tutor ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi')->placeholder('-')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label('Role')->options($roles),
            ])
            ->headerActions([
                Tables\Actions\Action::make('resetAll')
                    ->label('Reset semua password daerah')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset semua password daerah')
                    ->modalDescription('Password semua akun yang dipilih di bawah diganti dengan password baru yang sama.')
                    ->modalSubmitActionLabel('Ya, reset password')
                    ->form([
                        Forms\Components\Select::make('role')
                            ->label('Akun yang direset')
                            ->options(['' => 'Semua (tutor & peserta)', ...$roles->all()])
                            ->default('')
                            ->selectablePlaceholder(false),
                        ...static::passwordFields(),
                    ])
                    ->action(fn (array $data) => $this->notifyReset($this->service()->resetPasswords(
                        auth()->user(),
                        $data['password'],
                        role: filled($data['role'] ?? null) ? UserRole::from($data['role']) : null,
                    ))),
            ])
            ->actions([
                Tables\Actions\Action::make('changePassword')
                    ->label('Ubah password')
                    ->icon('heroicon-o-key')
                    ->modalHeading(fn (User $record) => 'Ubah password · ' . $record->name)
                    ->modalDescription(fn (User $record) => $record->email)
                    ->form(static::passwordFields())
                    ->action(fn (User $record, array $data) => $this->notifyReset(
                        $this->service()->resetPasswords(auth()->user(), $data['password'], [$record->id]),
                    )),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resetSelected')
                    ->label('Reset password terpilih')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form(static::passwordFields())
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records, array $data) => $this->notifyReset(
                        $this->service()->resetPasswords(auth()->user(), $data['password'], $records->modelKeys()),
                    )),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
