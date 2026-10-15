<?php

namespace App\Services;

use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use Illuminate\Support\Facades\DB;

/**
 * Materi tutor (ToT) disalin dari Materi Event peserta saat pertama kali dibuat (pertemuan & materi yang sama).
 * Setelah tersalin, materi tutor berdiri sendiri: pertemuan dan materinya (PPT, video, dll.) bisa diedit
 * tanpa tertimpa perubahan materi peserta. Salin ulang hanya lewat tombol "Salin ulang dari materi peserta".
 */
class TutorMaterialMirror
{
    /** Buat materi tutor (bila belum ada) dan salin isi materi peserta selama materi tutor masih kosong. */
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

        if (! $mirror->learningMeetings()->whereNull('learning_event_id')->exists()) {
            $this->copy($source, $mirror);
        }

        return $mirror;
    }

    /** Timpa pertemuan & materi tutor dengan salinan terbaru materi peserta asalnya (Pre/Post-Test ToT tetap). */
    public function resync(ModuleTemplate $mirror): bool
    {
        $source = $mirror->sourceTemplate;

        if (! $source) {
            return false;
        }

        $this->copy($source, $mirror);

        return true;
    }

    private function copy(ModuleTemplate $source, ModuleTemplate $mirror): void
    {
        DB::transaction(function () use ($source, $mirror): void {
            $mirror->update(['meeting_count' => $source->meeting_count]);

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
    }
}
