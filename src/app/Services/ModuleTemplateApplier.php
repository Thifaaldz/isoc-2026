<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\LearningEvent;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Support\TorEventTemplate;

class ModuleTemplateApplier
{
    /** $regenerateRundown false = rundown yang sudah disusun di wizard dipertahankan. */
    public function applyToEvent(LearningEvent $event, bool $replaceExisting = true, bool $regenerateRundown = true): void
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

        $templateMeetings = $template->learningMeetings()
            ->with([
                'materials' => fn ($query) => $query->orderBy('order'),
                'assessments' => fn ($query) => $query->where('type', 'quiz')->orderBy('id'),
            ])
            ->whereNull('learning_event_id')
            // Hanya modul yang dipilih untuk dibawakan di event ini; kosong = semua (event lama).
            ->when(filled($event->selected_meeting_ids), fn ($query) => $query->whereIn('id', array_map('intval', (array) $event->selected_meeting_ids)))
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
                    'questions_per_attempt' => $assessment->questions_per_attempt,
                    'is_open' => $assessment->is_open,
                    'questions' => $assessment->questions,
                ]);
            });

        // Rundown mengikuti susunan acara TOR; slot Materi 1/2 diisi modul yang dibawakan.
        $rundown = TorEventTemplate::rundown($event->starts_at?->format('H:i'), $templateMeetings->pluck('title')->all());
        $materiSlots = array_values(array_filter($rundown, fn (array $row) => str_starts_with($row['activity'], 'Materi ')));

        foreach ($templateMeetings->values() as $index => $templateMeeting) {
            $order = $index + 1;
            $duration = max(5, (int) ($templateMeeting->duration_minutes ?: 25));
            $slotTime = $materiSlots[$index]['start_time'] ?? null;
            $start = $event->starts_at && $slotTime
                ? $event->starts_at->copy()->setTimeFromTimeString($slotTime)
                : $event->starts_at?->copy()->addMinutes(max(0, $order - 1) * $duration);

            $meeting = LearningMeeting::query()->create([
                'learning_event_id' => $event->id,
                'module_template_id' => null,
                'order' => $order,
                'title' => $templateMeeting->title,
                'description' => $templateMeeting->description,
                'duration_minutes' => $duration,
                'task_title' => $templateMeeting->task_title,
                'task_description' => $templateMeeting->task_description,
                'starts_at' => $start,
                'is_published' => $templateMeeting->is_published,
            ]);

            foreach ($templateMeeting->materials as $material) {
                LearningMaterial::query()->create([
                    'learning_meeting_id' => $meeting->id,
                    'order' => $material->order,
                    'title' => $material->title,
                    'type' => $material->type,
                    'duration_minutes' => $material->duration_minutes,
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

        if ($regenerateRundown) {
            $event->update(['rundown_items' => $rundown]);
        }
    }
}
