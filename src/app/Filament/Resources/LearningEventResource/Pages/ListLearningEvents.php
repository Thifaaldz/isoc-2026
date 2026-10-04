<?php

namespace App\Filament\Resources\LearningEventResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListLearningEvents extends ListRecords
{
    protected static string $resource = LearningEventResource::class;

    /** Admin RTIK Daerah melihat event lokusnya sebagai kartu ringkas; role lain tetap memakai tabel. */
    public function getView(): string
    {
        return auth()->user()?->role === UserRole::Admin
            ? 'filament.resources.learning-event.admin-list'
            : parent::getView();
    }

    public function getTitle(): string
    {
        return auth()->user()?->role === UserRole::Admin ? 'Event Saya' : parent::getTitle();
    }

    public function getAdminEventsProperty(): Collection
    {
        return LearningEventResource::getEloquentQuery()
            ->with(['school', 'payments', 'tutors.user'])
            ->orderBy('starts_at')
            ->get();
    }

    /** Tab cepat Admin RTIK Pusat: pisahkan event yang butuh keputusan dari yang sedang berjalan / selesai. */
    public function getTabs(): array
    {
        if (auth()->user()?->role !== UserRole::SuperAdmin) {
            return [];
        }

        $approval = fn (Builder $query) => $query->where(fn ($inner) => $inner->whereIn('workflow_status', ['submitted', 'needs_revision'])->orWhere('publish_approval_status', 'pending'));
        $report = fn (Builder $query) => $query->where('final_report_status', 'submitted');
        $done = fn (Builder $query) => $query->where(fn ($inner) => $inner->where('final_report_status', 'approved')->orWhere('workflow_status', 'closed'));
        $running = fn (Builder $query) => $query
            ->whereIn('workflow_status', ['verified_term_1', 'tot_in_progress', 'tot_completed', 'field_training_scheduled', 'field_training_completed', 'evidence_submitted', 'verified_term_2'])
            ->where(fn ($inner) => $inner->whereNull('final_report_status')->orWhereIn('final_report_status', ['draft', 'revision']));
        $count = fn (\Closure $scope) => $scope(LearningEventResource::getEloquentQuery())->count();

        return [
            'semua' => Tab::make('Semua'),
            'persetujuan' => Tab::make('Perlu persetujuan')->icon('heroicon-o-inbox-arrow-down')->badge($count($approval) ?: null)->badgeColor('danger')->modifyQueryUsing($approval),
            'berjalan' => Tab::make('Berjalan')->icon('heroicon-o-play-circle')->badge($count($running) ?: null)->modifyQueryUsing($running),
            'laporan' => Tab::make('Laporan masuk')->icon('heroicon-o-document-check')->badge($count($report) ?: null)->badgeColor('warning')->modifyQueryUsing($report),
            'selesai' => Tab::make('Selesai')->icon('heroicon-o-check-badge')->badge($count($done) ?: null)->badgeColor('success')->modifyQueryUsing($done),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Event'),
        ];
    }
}
