<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\TutorRegistration;
use App\Services\TutorRegistrationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

/** Validasi pendaftaran tutor dari form publik (Registrasi Tutor) oleh Admin RTIK Pusat. */
class TutorApproval extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Approval Tutor';

    protected static ?string $title = 'Approval Registrasi Tutor';

    protected static ?string $slug = 'approval-tutor';

    protected static string $view = 'filament.pages.tutor-approval';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = app(TutorRegistrationService::class)->pendingCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TutorRegistration::query()->with(['tutor.user', 'learningEvent.school']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tutor.user.name')
                    ->label('Tutor')
                    ->description(fn (TutorRegistration $record) => $record->tutor?->user?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('tutor.user.phone')->label('WhatsApp')->toggleable(),
                Tables\Columns\TextColumn::make('tutor.institution')->label('Lembaga')->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('tutor.nik')->label('NIK')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('learningEvent.title')
                    ->label('Event')
                    ->description(fn (TutorRegistration $record) => $record->learningEvent?->school?->name)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('learningEvent.starts_at')->label('Tanggal event')->date('d M Y')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Mendaftar')->since()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => TutorRegistration::STATUSES[$state] ?? 'Ditugaskan')
                    ->color(fn (?string $state) => match ($state) {
                        TutorRegistration::PENDING => 'warning',
                        TutorRegistration::REJECTED => 'danger',
                        default => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(TutorRegistration::STATUSES)
                    ->default(TutorRegistration::PENDING),
                Tables\Filters\SelectFilter::make('learning_event_id')
                    ->label('Event')
                    ->relationship('learningEvent', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (TutorRegistration $record) => $record->status !== TutorRegistration::ASSIGNED)
                    ->requiresConfirmation()
                    ->modalDescription(fn (TutorRegistration $record) => 'Akun tutor diaktifkan dan tutor ditugaskan ke "' . $record->learningEvent?->title . '".'
                        . ($this->quotaFull($record) ? ' Catatan: kuota tutor event ini sudah penuh.' : ''))
                    ->action(function (TutorRegistration $record): void {
                        app(TutorRegistrationService::class)->approve($record);
                        Notification::make()->title('Tutor disetujui')->body($record->tutor?->user?->name . ' sekarang tutor di ' . $record->learningEvent?->title . '.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (TutorRegistration $record) => $record->status === TutorRegistration::PENDING)
                    ->requiresConfirmation()
                    ->modalDescription('Pendaftaran ditolak. Akun yang belum pernah disetujui tetap nonaktif; tutor bisa mendaftar ulang.')
                    ->action(function (TutorRegistration $record): void {
                        app(TutorRegistrationService::class)->reject($record);
                        Notification::make()->title('Pendaftaran tutor ditolak')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approveSelected')
                    ->label('Setujui terpilih')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $pending = $records->where('status', '!=', TutorRegistration::ASSIGNED);
                        $pending->each(fn (TutorRegistration $record) => app(TutorRegistrationService::class)->approve($record));
                        Notification::make()->title($pending->count() . ' tutor disetujui')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Belum ada pendaftaran tutor')
            ->emptyStateDescription('Pendaftaran dari halaman Registrasi Tutor (/tutors/register) tampil di sini.');
    }

    private function quotaFull(TutorRegistration $record): bool
    {
        $event = $record->learningEvent;
        $target = (int) $event?->target_tutors;

        return $event && $target > 0 && $event->tutors()->count() >= $target;
    }
}
