<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAttendance extends ManageRecords
{
    protected static string $resource = AttendanceResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat absensinya.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        // Absensi event (kode/manual) dan absensi sesi pelatihan di lokasi event.
        return $query->where(fn (Builder $inner) => $inner
            ->where('learning_event_id', $event->id)
            ->orWhere(fn (Builder $session) => $session
                ->whereNull('learning_event_id')
                ->whereHas('session', fn (Builder $sessionQuery) => $sessionQuery->where('school_id', $event->school_id ?? 0))));
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $present = $this->eventRecords($event)->whereNotNull('participant_id')->where('status', 'hadir')->count();
        $participants = $event->participants()->count();

        return [
            'badges' => [[$present . ' hadir', 'success'], [$participants . ' peserta', 'gray']],
            'progress' => ['label' => 'Peserta hadir', 'done' => $present, 'total' => $participants],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Muncul setelah card event dibuka: catat hadir semua peserta event tersebut sekaligus.
            \App\Filament\Support\MarkAttendanceActions::markAll(Actions\Action::class, fn () => collect(), fn () => $this->eventId)
                ->visible(fn () => (bool) $this->selectedEvent)
                ->after(fn () => $this->resetTable()),
            Actions\CreateAction::make(),
        ];
    }
}
