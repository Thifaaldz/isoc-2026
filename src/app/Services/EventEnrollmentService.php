<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\WagGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Pendaftaran peserta yang sudah punya akun ke event lain (dari website maupun panel peserta). */
class EventEnrollmentService
{
    /** Event yang tampil untuk peserta: sudah di-publish dan aktif. */
    public function availableEventsQuery(): Builder
    {
        return LearningEvent::query()
            ->where('is_published', true)
            ->where('status', 'active');
    }

    public function isEnrolled(Participant $participant, LearningEvent $event): bool
    {
        return $participant->learningEvents()->whereKey($event->id)->exists();
    }

    /** Kuota penuh jika jumlah peserta sudah mencapai target peserta event. */
    public function isFull(LearningEvent $event): bool
    {
        $target = (int) $event->target_participants;

        if ($target <= 0) {
            return false;
        }

        $count = $event->participants_count ?? $event->participants()->count();

        return $count >= $target;
    }

    /** Event dianggap sudah lewat setelah jam selesai (atau akhir hari tanggal mulai bila jam selesai kosong). */
    public function isPast(LearningEvent $event): bool
    {
        $endsAt = $event->ends_at ?? $event->starts_at?->copy()->endOfDay();

        return $endsAt !== null && $endsAt->isPast();
    }

    /** Alasan pendaftaran tertutup, atau null bila event masih bisa didaftari. */
    public function registrationClosedReason(LearningEvent $event): ?string
    {
        return match (true) {
            $this->isPast($event) => 'Pendaftaran ditutup karena tanggal event sudah lewat.',
            ! $event->registration_open => 'Pendaftaran event ini sudah ditutup.',
            $this->isFull($event) => 'Kuota peserta event ini sudah penuh.',
            default => null,
        };
    }

    public function wagGroupFor(LearningEvent $event): ?WagGroup
    {
        return $event->school?->wagGroups
            ?->filter(fn (WagGroup $group): bool => $group->status === 'active' && filled($group->invite_link))
            ->sortByDesc('updated_at')
            ->first()
            ?: WagGroup::query()->where('status', 'active')->whereNotNull('invite_link')->latest()->first();
    }

    /**
     * Daftarkan peserta ke event. Akses modul/tes masih terkunci sampai peserta
     * melengkapi bukti dukung (follow IG + join WAG) dari dashboard.
     */
    public function enroll(Participant $participant, LearningEvent $event): void
    {
        $participant->learningEvents()->syncWithoutDetaching([
            $event->id => [
                'status' => 'registered',
                'registered_at' => now(),
                'admin_approval_status' => 'pending',
                'tutor_approval_status' => 'pending',
                'approval_notes' => 'Menunggu bukti dukung: bukti follow IG dan checklist join WAG dari dashboard peserta.',
            ],
        ]);

        $participant->syncInitialApprovalForEvent($event);
    }

    /** Simpan bukti follow IG peserta untuk event lalu buka akses jika checklist WAG juga sudah lengkap. */
    public function submitInstagramEvidence(Participant $participant, LearningEvent $event, string $instagramEvidencePath): bool
    {
        DB::transaction(function () use ($participant, $event, $instagramEvidencePath): void {
            Evidence::query()->updateOrCreate([
                'learning_event_id' => $event->id,
                'uploaded_by' => $participant->user_id,
                'type' => 'follow_ig',
            ], [
                'school_id' => $event->school_id ?: $participant->school_id,
                'file_path' => $instagramEvidencePath,
                'status' => 'pending',
                'verified_at' => null,
                'review_notes' => null,
            ]);

            $participant->update(['followed_instagram' => true]);
        });

        return $participant->syncInitialApprovalForEvent($event);
    }
}
