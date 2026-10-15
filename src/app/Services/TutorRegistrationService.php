<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\TutorRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Pendaftaran tutor ke event (form publik) dan validasinya oleh Admin RTIK Pusat. */
class TutorRegistrationService
{
    /** Event yang bisa dipilih calon tutor: event aktif yang sudah di-publish (sama seperti pendaftaran peserta). */
    public function availableEventsQuery(): Builder
    {
        return app(EventEnrollmentService::class)->availableEventsQuery()
            ->with('school')
            ->withCount('tutors')
            ->orderBy('starts_at');
    }

    /** @return Collection<int, LearningEvent> */
    public function availableEvents(): Collection
    {
        return $this->availableEventsQuery()->get()
            ->reject(fn (LearningEvent $event) => app(EventEnrollmentService::class)->isPast($event))
            ->values();
    }

    /** Alasan pendaftaran tutor tertutup, atau null bila masih bisa didaftari. */
    public function closedReason(LearningEvent $event, ?Tutor $tutor = null): ?string
    {
        $target = (int) $event->target_tutors;
        $assigned = $event->tutors_count ?? $event->tutors()->count();

        return match (true) {
            app(EventEnrollmentService::class)->isPast($event) => 'Pendaftaran tutor ditutup karena tanggal event sudah lewat.',
            $tutor && $event->tutors()->whereKey($tutor->id)->exists() => 'Kamu sudah terdaftar sebagai tutor di event ini.',
            $tutor && $this->registration($event, $tutor)?->status === TutorRegistration::PENDING => 'Pendaftaran tutor kamu di event ini sedang menunggu validasi Admin RTIK Pusat.',
            $target > 0 && $assigned >= $target => 'Kuota tutor event ini sudah penuh.',
            default => null,
        };
    }

    public function registration(LearningEvent $event, Tutor $tutor): ?TutorRegistration
    {
        return TutorRegistration::query()->where('learning_event_id', $event->id)->where('tutor_id', $tutor->id)->first();
    }

    /** Ajukan tutor ke event (pendaftaran yang pernah ditolak bisa diajukan ulang). */
    public function apply(Tutor $tutor, LearningEvent $event): TutorRegistration
    {
        $registration = $this->registration($event, $tutor) ?? new TutorRegistration(['learning_event_id' => $event->id, 'tutor_id' => $tutor->id]);

        if ($registration->status !== TutorRegistration::ASSIGNED) {
            $registration->fill(['status' => TutorRegistration::PENDING, 'assigned_at' => null])->save();
        }

        return $registration;
    }

    /** Setujui: akun tutor diaktifkan dan tutor ditugaskan ke event. */
    public function approve(TutorRegistration $registration): void
    {
        $registration->update(['status' => TutorRegistration::ASSIGNED, 'assigned_at' => now()]);
        $registration->tutor?->user?->update(['is_active' => true]);
    }

    /** Tolak: akun yang belum pernah disetujui di event mana pun tetap nonaktif. */
    public function reject(TutorRegistration $registration): void
    {
        $registration->update(['status' => TutorRegistration::REJECTED, 'assigned_at' => null]);
    }

    public function pendingCount(): int
    {
        return TutorRegistration::query()->where('status', TutorRegistration::PENDING)->count();
    }

    public static function isTutor(?\App\Models\User $user): bool
    {
        return $user?->role === UserRole::Tutor && $user->tutor !== null;
    }
}
