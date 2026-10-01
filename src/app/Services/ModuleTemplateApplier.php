<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;

class ModuleTemplateApplier
{
    public function applyToEvent(LearningEvent $event, bool $replaceExisting = true): void
    {
        $template = $event->moduleTemplate;

        if (! $template) {
            return;
        }

        if ($replaceExisting) {
            Assessment::query()
                ->where('learning_event_id', $event->id)
                ->whereIn('type', ['pre', 'quiz', 'post'])
                ->delete();

            $event->meetings()->delete();
        }

        $generatedRundown = [];
        $templateMeetings = $template->learningMeetings()
            ->with([
                'materials' => fn ($query) => $query->orderBy('order'),
                'assessments' => fn ($query) => $query->where('type', 'quiz')->orderBy('id'),
            ])
            ->orderBy('order')
            ->get();

        $template->assessments()
            ->whereIn('type', ['pre', 'post'])
            ->orderByRaw("case when type = 'pre' then 0 when type = 'post' then 1 else 2 end")
            ->get()
            ->each(function (Assessment $assessment) use ($event): void {
                Assessment::query()->create([
                    'learning_event_id' => $event->id,
                    'learning_meeting_id' => null,
                    'module_template_id' => null,
                    'type' => $assessment->type,
                    'title' => $assessment->title,
                    'form_url' => $assessment->form_url,
                    'passing_score' => $assessment->passing_score,
                    'is_open' => $assessment->is_open,
                    'questions' => $assessment->questions,
                ]);
            });

        foreach ($templateMeetings as $templateMeeting) {
            $order = (int) $templateMeeting->order;
            $start = $event->starts_at?->copy()->addMinutes(max(0, $order - 1) * 25);
            $end = $start?->copy()->addMinutes(25);

            $meeting = LearningMeeting::query()->create([
                'learning_event_id' => $event->id,
                'module_template_id' => null,
                'order' => $order,
                'title' => $templateMeeting->title,
                'description' => $templateMeeting->description,
                'task_title' => $templateMeeting->task_title,
                'task_description' => $templateMeeting->task_description,
                'starts_at' => $start,
                'is_published' => $templateMeeting->is_published,
            ]);

            $generatedRundown[] = [
                'start_time' => $start?->format('H:i') ?? null,
                'end_time' => $end?->format('H:i') ?? null,
                'activity' => $meeting->title,
                'pic' => 'Tutor / Fasilitator',
                'notes' => $meeting->description,
            ];

            foreach ($templateMeeting->materials as $material) {
                LearningMaterial::query()->create([
                    'learning_meeting_id' => $meeting->id,
                    'order' => $material->order,
                    'title' => $material->title,
                    'type' => $material->type,
                    'file_path' => $material->file_path,
                    'external_url' => $material->external_url,
                    'is_published' => $material->is_published,
                ]);
            }

            foreach ($templateMeeting->assessments as $assessment) {
                Assessment::query()->create([
                    'learning_event_id' => $event->id,
                    'learning_meeting_id' => $meeting->id,
                    'module_template_id' => null,
                    'type' => 'quiz',
                    'title' => $assessment->title,
                    'passing_score' => $assessment->passing_score,
                    'is_open' => $assessment->is_open,
                    'questions' => $assessment->questions,
                ]);
            }
        }

        if (blank($event->rundown_items) && $generatedRundown !== []) {
            $event->update(['rundown_items' => $generatedRundown]);
        }
    }
}
