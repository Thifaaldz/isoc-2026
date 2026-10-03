<?php

namespace App\Services;

use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Materi tutor (ToT) dibuat otomatis dari Materi Event peserta dengan isi pertemuan & materi yang sama.
 * Yang diisi manual untuk materi tutor hanya Pre-Test dan Post-Test ToT.
 */
class TutorMaterialMirror
{
    private static bool $syncing = false;

    /** Buat (bila belum ada) lalu samakan materi tutor dari Materi Event peserta. */
    public function ensureFor(ModuleTemplate $source): ?ModuleTemplate
    {
        if ($source->audience !== ModuleTemplate::AUDIENCE_PESERTA) {
            return null;
        }

        $mirror = $source->tutorMirror()->first() ?? ModuleTemplate::query()->create([
            'source_template_id' => $source->id,
            'created_by' => $source->created_by,
            'name' => $source->name . ' - ToT Tutor',
            'audience' => ModuleTemplate::AUDIENCE_TUTOR,
            'description' => $source->description,
            'purpose' => $source->purpose,
            'meeting_count' => $source->meeting_count,
            'is_active' => $source->is_active,
        ]);

        $this->sync($source, $mirror);

        return $mirror;
    }

    /** Dipanggil saat pertemuan/materi template peserta berubah; hanya jalan bila materi tutornya sudah ada. */
    public function syncFromTemplateId(?int $templateId): void
    {
        if (self::$syncing || ! $templateId) {
            return;
        }

        $source = ModuleTemplate::query()->find($templateId);
        $mirror = $source?->tutorMirror()->first();

        if ($mirror) {
            $this->sync($source, $mirror);
        }
    }

    private function sync(ModuleTemplate $source, ModuleTemplate $mirror): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            DB::transaction(function () use ($source, $mirror): void {
                $mirror->update([
                    'meeting_count' => $source->meeting_count,
                    'is_active' => $source->is_active,
                ]);

                // Pertemuan tutor dibangun ulang dari sumber; Pre/Post-Test ToT melekat ke template, jadi tidak terhapus.
                $mirror->learningMeetings()->whereNull('learning_event_id')->get()
                    ->each(fn (LearningMeeting $meeting) => $meeting->materials()->delete());
                $mirror->learningMeetings()->whereNull('learning_event_id')->delete();

                $meetings = $source->learningMeetings()
                    ->whereNull('learning_event_id')
                    ->with('materials')
                    ->orderBy('order')
                    ->get();

                foreach ($meetings as $meeting) {
                    $copy = $meeting->replicate();
                    $copy->module_template_id = $mirror->id;
                    $copy->save();

                    foreach ($meeting->materials as $material) {
                        $materialCopy = $material->replicate();
                        $materialCopy->learning_meeting_id = $copy->id;
                        $materialCopy->save();
                    }
                }
            });
        } finally {
            self::$syncing = false;
        }
    }
}
