<?php

namespace App\Filament\Resources\LearningMeetingResource\Pages;

use App\Filament\Resources\LearningMeetingResource;
use App\Models\ModuleTemplate;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Collection;

class ManageLearningMeeting extends ManageRecords
{
    protected static string $resource = LearningMeetingResource::class;

    protected static string $view = 'filament.resources.pages.materi-event-scoped-records';

    public function mount(): void
    {
        parent::mount();

        if (request()->has('materi_event')) {
            return;
        }

        if ($templateId = ModuleTemplate::query()->where('is_active', true)->whereNull('source_template_id')->orderBy('name')->value('id')) {
            $this->redirect(static::getResource()::getUrl('index', ['materi_event' => $templateId]), navigate: false);
        }
    }

    /** @return Collection<int, ModuleTemplate> */
    public function templates(): Collection
    {
        return ModuleTemplate::query()
            ->withCount('learningMeetings')
            ->where('is_active', true)
            ->whereNull('source_template_id')
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
        return (int) $template->learning_meetings_count;
    }

    public function templateCounterLabel(): string
    {
        return 'Pertemuan';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => LearningMeetingResource::getUrl('create', array_filter([
                    'module_template_id' => request()->integer('materi_event') ?: null,
                ]))),
        ];
    }
}
