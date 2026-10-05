<?php

namespace App\Filament\Support;

use App\Models\LearningEvent;
use App\Services\EventAttendanceService;
use Closure;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

/** Tombol "Hadirkan semua peserta" & "Tandai hadir" (massal) untuk Admin RTIK Daerah, Tutor, dan Super Admin. */
class MarkAttendanceActions
{
    /**
     * @param  class-string<\Filament\Actions\Action>|class-string<Tables\Actions\Action>  $class
     * @param  Closure(): \Illuminate\Support\Collection<int, string>  $eventOptions  [id => judul]
     * @param  Closure(): ?int|null  $fixedEvent  event yang sedang dibuka (pilihan event disembunyikan)
     */
    public static function markAll(string $class, Closure $eventOptions, ?Closure $fixedEvent = null)
    {
        $eventFor = fn (array $data) => LearningEvent::query()->find(($fixedEvent ? $fixedEvent() : null) ?? ($data['learning_event_id'] ?? null));

        return $class::make('markAllAttended')
            ->label('Hadirkan semua peserta')
            ->icon('heroicon-o-user-group')
            ->color('success')
            ->modalHeading('Hadirkan semua peserta event')
            ->modalDescription('Semua peserta terdaftar yang belum punya catatan absensi di event ini dicatat Hadir. Peserta yang sudah absen, izin, sakit, atau alpa tidak diubah.')
            ->modalSubmitActionLabel('Ya, hadirkan semua')
            ->form([
                Forms\Components\Select::make('learning_event_id')
                    ->label('Event')
                    ->options($eventOptions)
                    ->searchable()
                    ->required()
                    ->live()
                    ->hidden(fn () => $fixedEvent && $fixedEvent()),
                Forms\Components\Select::make('mode')
                    ->label('Jenis kehadiran')
                    ->options(fn (Forms\Get $get) => ($event = LearningEvent::query()->find(($fixedEvent ? $fixedEvent() : null) ?? $get('learning_event_id')))
                        ? app(EventAttendanceService::class)->modesFor($event)
                        : EventAttendanceService::MODES)
                    ->default('offline')
                    ->required(),
            ])
            ->action(function (array $data) use ($eventFor): void {
                $event = $eventFor($data);

                if (! $event) {
                    return;
                }

                static::notify($event, app(EventAttendanceService::class)->markPresent($event, [], $data['mode'] ?? null, auth()->id()));
            });
    }

    /** Aksi massal untuk baris peserta terpilih; $eventId & $participantId membaca event/peserta dari tiap baris. */
    public static function markSelected(Closure $eventId, Closure $participantId): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('markSelectedAttended')
            ->label('Tandai hadir')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('Peserta terpilih yang belum punya catatan absensi dicatat Hadir. Catatan absensi yang sudah ada tidak diubah.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($eventId, $participantId): void {
                $records->groupBy($eventId)->each(function (Collection $rows, $id) use ($participantId): void {
                    if ($event = LearningEvent::query()->find($id)) {
                        static::notify($event, app(EventAttendanceService::class)->markPresent($event, $rows->map($participantId)->filter()->all(), null, auth()->id()));
                    }
                });
            });
    }

    /** @param  array{created: int, skipped: int}  $result */
    private static function notify(LearningEvent $event, array $result): void
    {
        Notification::make()
            ->title($result['created'] . ' peserta dicatat hadir')
            ->body($event->title . ($result['skipped'] ? " · {$result['skipped']} peserta sudah punya catatan absensi (tidak diubah)." : '.'))
            ->success()
            ->send();
    }
}
