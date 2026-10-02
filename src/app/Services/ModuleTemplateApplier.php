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
        $current = $event->starts_at?->copy();

        if ($current) {
            $openingEnd = $current->copy()->addMinutes(15);
            $generatedRundown[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $openingEnd->format('H:i'),
                'activity' => 'Registrasi, pembukaan, dan ice breaking',
                'pic' => 'Admin RTIK Local / Tutor',
                'notes' => 'Cek daftar hadir registrasi dan kesiapan peserta.',
            ];
            $current = $openingEnd;

            $preEnd = $current->copy()->addMinutes(15);
            $generatedRundown[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $preEnd->format('H:i'),
                'activity' => 'Pre-Test peserta',
                'pic' => 'Tutor / Fasilitator',
                'notes' => 'Peserta wajib menyelesaikan pre-test sebelum modul.',
            ];
            $current = $preEnd;
        }

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
            $duration = max(5, (int) ($templateMeeting->duration_minutes ?: 25));
            $start = $current?->copy() ?? $event->starts_at?->copy()->addMinutes(max(0, $order - 1) * $duration);
            $end = $start?->copy()->addMinutes($duration);

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

            $generatedRundown[] = [
                'start_time' => $start?->format('H:i') ?? null,
                'end_time' => $end?->format('H:i') ?? null,
                'activity' => $meeting->title,
                'pic' => 'Tutor / Fasilitator',
                'notes' => trim((string) $meeting->description) ?: ($duration . ' menit'),
            ];
            $current = $end;

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

        if ($current) {
            $postEnd = $current->copy()->addMinutes(15);
            $generatedRundown[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $postEnd->format('H:i'),
                'activity' => 'Post-Test, refleksi, dan penutup',
                'pic' => 'Tutor / Admin RTIK Local',
                'notes' => 'Post-test dibuka setelah pre-test dan kuis modul aktif selesai.',
            ];
            $current = $postEnd;

            $docEnd = $current->copy()->addMinutes(5);
            $generatedRundown[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $docEnd->format('H:i'),
                'activity' => 'Dokumentasi akhir dan video slogan',
                'pic' => 'Tutor / Admin RTIK Local',
                'notes' => 'Foto kegiatan, video slogan, daftar hadir, dan bukti microsite.',
            ];
        }

        if ($generatedRundown !== []) {
            $event->update(['rundown_items' => $generatedRundown]);
        }
    }
}
