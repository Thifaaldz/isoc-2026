<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;

class ModuleTemplateMeetingSyncer
{
    /** Samakan banyak pertemuan materi dengan Jumlah Pertemuan: tambah di akhir atau hapus dari urutan terakhir. */
    public function sync(ModuleTemplate $template): void
    {
        $count = max(1, (int) ($template->meeting_count ?: 1));
        $meetings = $template->learningMeetings()->whereNull('learning_event_id')->orderBy('order')->orderBy('id')->get();

        foreach ($meetings->slice($count) as $meeting) {
            Assessment::query()->where('learning_meeting_id', $meeting->id)->delete();
            $meeting->delete();
        }

        $nextOrder = (int) $meetings->take($count)->max('order');

        for ($i = $meetings->count(); $i < $count; $i++) {
            $nextOrder++;

            LearningMeeting::query()->create([
                'module_template_id' => $template->id,
                'order' => $nextOrder,
                'learning_event_id' => null,
                'title' => 'Pertemuan ' . $nextOrder,
                'description' => null,
                'duration_minutes' => 25,
                'task_title' => null,
                'task_description' => null,
                'starts_at' => null,
                'is_published' => true,
            ]);
        }
    }
}
