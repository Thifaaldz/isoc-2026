<?php

namespace App\Filament\Support;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Rules\ReachableMicrositeUrl;
use App\Services\EventAttendanceService;
use App\Services\MicrositeLinkChecker;
use App\Services\ParticipantProgressEditor;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

/**
 * Aksi per peserta di Manajemen Peserta (setelah event dipilih): ubah nilai Pre/Post-Test, isi link s.id, dan absensi.
 * Dipakai Tutor (dan Admin RTIK) untuk membantu peserta yang terkendala mengisi sendiri.
 */
class ParticipantProgressActions
{
    public static function canUse(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor], true);
    }

    /** Event yang sedang dibuka di halaman card per event (HasEventCards). */
    public static function event($livewire): ?LearningEvent
    {
        return $livewire->selectedEvent ?? null;
    }

    public static function group(): Tables\Actions\ActionGroup
    {
        // ActionGroup tidak bisa membaca $livewire; tiap aksi menyembunyikan dirinya sendiri dan grup ikut tersembunyi.
        $visible = fn (Tables\Actions\Action $action) => $action->visible(fn ($livewire) => static::canUse() && static::event($livewire));

        return Tables\Actions\ActionGroup::make(array_map($visible, [static::scores(), static::microsite(), static::attendance()]))
            ->label('Kelola')
            ->icon('heroicon-m-pencil-square')
            ->button()
            ->color('primary');
    }

    public static function scores(): Tables\Actions\Action
    {
        $editor = fn () => app(ParticipantProgressEditor::class);
        $field = fn (string $type, string $label) => Forms\Components\TextInput::make("{$type}_score")
            ->label($label)
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->step(0.01)
            ->suffix('/ 100')
            ->helperText('Kosongkan bila tidak diubah.');

        return Tables\Actions\Action::make('editScores')
            ->label('Nilai Pre/Post-Test')
            ->icon('heroicon-o-academic-cap')
            ->modalHeading(fn (Participant $record) => 'Nilai tes · ' . $record->user?->name)
            ->modalDescription('Nilai yang diisi menjadi nilai tes peserta di event ini (dipakai rekap, ranking, dan sertifikat).')
            ->fillForm(fn (Participant $record, $livewire) => [
                'pre_score' => $editor()->score(static::event($livewire), $record, 'pre'),
                'post_score' => $editor()->score(static::event($livewire), $record, 'post'),
            ])
            ->form([$field('pre', 'Nilai Pre-Test'), $field('post', 'Nilai Post-Test')])
            ->action(function (Participant $record, array $data, $livewire) use ($editor): void {
                $event = static::event($livewire);
                $before = ['pre' => $editor()->score($event, $record, 'pre'), 'post' => $editor()->score($event, $record, 'post')];

                foreach (['pre', 'post'] as $type) {
                    $value = $data["{$type}_score"] ?? null;

                    if ($value !== null && $value !== '' && (float) $value !== $before[$type]) {
                        $editor()->setScore($event, $record, $type, (float) $value);
                    }
                }

                Notification::make()->title('Nilai tes tersimpan')->body($record->user?->name . ' · ' . $event->title)->success()->send();
            });
    }

    public static function microsite(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('editMicrosite')
            ->label('Link s.id')
            ->icon('heroicon-o-link')
            ->modalHeading(fn (Participant $record) => 'Link s.id · ' . $record->user?->name)
            ->modalDescription('Ketik nama link setelah https://s.id/. Link dicek otomatis dan harus bisa dibuka.')
            ->fillForm(fn (Participant $record, $livewire) => [
                'sid_path' => MicrositeLinkChecker::sidPath(app(ParticipantProgressEditor::class)->micrositeUrl(static::event($livewire), $record)),
            ])
            ->form([
                Forms\Components\TextInput::make('sid_path')
                    ->label('Link microsite')
                    ->prefix('https://s.id/')
                    ->placeholder('Daftar_Peserta')
                    ->required()
                    ->maxLength(200)
                    // Awalan yang ikut ditempel dibuang; validasi memakai link lengkap seperti di dashboard peserta.
                    ->dehydrateStateUsing(fn (?string $state) => MicrositeLinkChecker::sidPath($state))
                    ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                        $path = MicrositeLinkChecker::sidPath($value);
                        (new ReachableMicrositeUrl())->validate($attribute, $path === '' ? '' : 'https://s.id/' . $path, $fail);
                    }),
            ])
            ->action(function (Participant $record, array $data, $livewire): void {
                $event = static::event($livewire);
                $url = 'https://s.id/' . $data['sid_path'];
                app(ParticipantProgressEditor::class)->setMicrosite($event, $record, $url);

                Notification::make()->title(MicrositeLinkChecker::SAVED_TITLE)->body(MicrositeLinkChecker::savedMessage($url, $event->title))->success()->send();
            });
    }

    public static function attendance(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('editAttendance')
            ->label('Absensi')
            ->icon('heroicon-o-clipboard-document-check')
            ->modalHeading(fn (Participant $record) => 'Absensi · ' . $record->user?->name)
            ->fillForm(function (Participant $record, $livewire): array {
                $attendance = app(EventAttendanceService::class)->attendanceFor($record, static::event($livewire));

                return ['status' => $attendance?->status ?? 'hadir', 'mode' => $attendance?->mode];
            })
            ->form(fn ($livewire) => [
                Forms\Components\Select::make('status')
                    ->label('Status kehadiran')
                    ->options(ParticipantProgressEditor::ATTENDANCE_STATUSES)
                    ->required()
                    ->native(false),
                Forms\Components\Select::make('mode')
                    ->label('Jenis kehadiran')
                    ->options(app(EventAttendanceService::class)->modesFor(static::event($livewire)))
                    ->default(array_key_first(app(EventAttendanceService::class)->modesFor(static::event($livewire))))
                    ->native(false),
            ])
            ->action(function (Participant $record, array $data, $livewire): void {
                $event = static::event($livewire);
                app(ParticipantProgressEditor::class)->setAttendance($event, $record, $data['status'], $data['mode'] ?? null, auth()->id());

                Notification::make()
                    ->title('Absensi tersimpan: ' . ParticipantProgressEditor::ATTENDANCE_STATUSES[$data['status']])
                    ->body($record->user?->name . ' · ' . $event->title)
                    ->success()
                    ->send();
            });
    }
}
