<?php

namespace App\Filament\Resources\AssessmentResource\Pages;

use App\Filament\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Models\ModuleTemplate;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Collection;

class ManageAssessment extends ManageRecords
{
    protected static string $resource = AssessmentResource::class;

    protected static string $view = 'filament.resources.pages.materi-event-scoped-records';

    public function mount(): void
    {
        parent::mount();

        if (request()->has('materi_event')) {
            return;
        }

        if ($templateId = ModuleTemplate::query()->where('is_active', true)->orderBy('name')->value('id')) {
            $this->redirect(static::getResource()::getUrl('index', ['materi_event' => $templateId]), navigate: false);
        }
    }

    /** @return Collection<int, ModuleTemplate> */
    public function templates(): Collection
    {
        return ModuleTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function activeTemplateId(): ?int
    {
        return request()->integer('materi_event') ?: null;
    }

    public function templateUrl(int $templateId): string
    {
        return static::getResource()::getUrl('index', ['materi_event' => $templateId]);
    }

    public function templateCounter(ModuleTemplate $template): int
    {
        return Assessment::query()
            ->where(function ($query) use ($template): void {
                $query
                    ->where('module_template_id', $template->id)
                    ->orWhereHas('meeting', fn ($meetingQuery) => $meetingQuery->where('module_template_id', $template->id));
            })
            ->count();
    }

    public function templateCounterLabel(): string
    {
        return 'Tes';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => AssessmentResource::getUrl('create', array_filter([
                    'module_template_id' => request()->integer('materi_event') ?: null,
                ]))),
        ];
    }
}
