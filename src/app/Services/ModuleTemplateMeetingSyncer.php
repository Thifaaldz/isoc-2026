<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;

class ModuleTemplateMeetingSyncer
{
    public function sync(ModuleTemplate $template): void
    {
        // Materi tutor auto-generated mengikuti pertemuan dari materi peserta asalnya.
        if ($template->isGeneratedForTutor()) {
            return;
        }

        $count = max(1, (int) ($template->meeting_count ?: 1));

        $extraMeetings = $template->learningMeetings()->where('order', '>', $count)->get();

        foreach ($extraMeetings as $meeting) {
            Assessment::query()->where('learning_meeting_id', $meeting->id)->delete();
            $meeting->delete();
        }

        foreach (range(1, $count) as $order) {
            LearningMeeting::query()->firstOrCreate(
                [
                    'module_template_id' => $template->id,
                    'order' => $order,
                ],
                [
                    'learning_event_id' => null,
                    'title' => 'Pertemuan ' . $order,
                    'description' => null,
                    'duration_minutes' => 25,
                    'task_title' => null,
                    'task_description' => null,
                    'starts_at' => null,
                    'is_published' => true,
                ],
            );
        }
    }
}
