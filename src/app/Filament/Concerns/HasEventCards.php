<?php

namespace App\Filament\Concerns;

use App\Models\LearningEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Halaman resource yang dibuka lewat card per event (seperti Bukti Dukung): halaman awal berisi card event,
 * klik card menampilkan data resource khusus event tersebut.
 *
 * Halaman wajib mengisi scopeToEvent(); eventCardSummary() opsional untuk badge & progres di card.
 */
trait HasEventCards
{
    /** Event yang sedang dibuka; kosong = halaman awal berisi card per event. */
    #[Url(as: 'event')]
    public ?int $eventId = null;

    public string $eventSearch = '';

    /** Batasi query resource ke satu event. */
    abstract protected function scopeToEvent(Builder $query, LearningEvent $event): Builder;

    /**
     * Ringkasan di card: badges [label, color] dan progress (label, done, total) atau null.
     *
     * @return array{badges: array<int, array{0: string, 1: string}>, progress: array{label: string, done: int, total: int}|null}
     */
    protected function eventCardSummary(LearningEvent $event): array
    {
        return [
            'badges' => [[$this->eventRecords($event)->count() . ' data', 'gray']],
            'progress' => null,
        ];
    }

    /** Teks petunjuk di atas card event. */
    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat datanya.';
    }

    public function getView(): string
    {
        return 'filament.resources.event-cards-page';
    }

    /** Query resource (sudah dibatasi role) untuk satu event. */
    protected function eventRecords(LearningEvent $event): Builder
    {
        return $this->scopeToEvent(static::getResource()::getEloquentQuery(), $event);
    }

    public function getEventCardsProperty(): Collection
    {
        $search = trim($this->eventSearch);

        return static::getResource()::scopedEventsQuery()
            ->with('school')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$search}%")
                ->orWhereHas('school', fn (Builder $school) => $school->where('name', 'like', "%{$search}%"))))
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (LearningEvent $event) => ['event' => $event, ...$this->eventCardSummary($event)]);
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->eventId
            ? static::getResource()::scopedEventsQuery()->with('school')->find($this->eventId)
            : null;
    }

    public function openEvent(int $eventId): void
    {
        $this->eventId = $eventId;
        $this->resetTable();
    }

    public function closeEvent(): void
    {
        $this->eventId = null;
        $this->resetTable();
    }

    /** Tabel hanya berisi data event yang dibuka (query bawaan resource tetap dipakai). */
    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();
        $event = $this->selectedEvent;

        return $event ? $this->scopeToEvent($query, $event) : $query->whereRaw('1 = 0');
    }
}
