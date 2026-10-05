<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\LearningEvent;
use App\Filament\Concerns\HasEventCards;
use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePayment extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    use HasEventCards;

    protected function eventCardsHint(): string
    {
        return 'Pilih event untuk melihat termin pembayarannya.';
    }

    protected function scopeToEvent(Builder $query, LearningEvent $event): Builder
    {
        return $query->where('learning_event_id', $event->id);
    }

    protected function eventCardSummary(LearningEvent $event): array
    {
        $payments = $this->eventRecords($event)->get(['status']);
        $paid = $payments->where('status', 'eligible')->count();

        return [
            'badges' => array_values(array_filter([
                [$payments->count() . ' termin', 'gray'],
                $paid ? [$paid . ' cair', 'success'] : null,
                $payments->count() - $paid ? [($payments->count() - $paid) . ' menunggu', 'warning'] : null,
            ])),
            'progress' => ['label' => 'Termin cair', 'done' => $paid, 'total' => $payments->count()],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadRabTemplate')
                ->label('Template RAB')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn () => route('import-templates.rab'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
