<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\TrainingSession;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Kontrol akses per role untuk Filament Resource yang dipakai bersama di 4 panel.
 * Override viewRoles(), manageRoles(), dan scopeType() di resource.
 *
 * scopeType():
 *   'school'      -> tabel punya kolom school_id (admin/tutor/peserta hanya melihat sekolahnya)
 *   'school_self' -> resource School itu sendiri (kolom id)
 *   'participant' -> tabel punya participant_id (peserta miliknya, admin/tutor per sekolah)
 *   'participant_self' -> resource Participant itu sendiri, dibatasi event yang relevan
 *   'tutor_self' -> resource Tutor itu sendiri, dibatasi event yang relevan
 *   'learning_event' -> resource event/lokus
 *   'learning_meeting' -> resource pertemuan yang punya relasi event
 *   'learning_material' -> resource materi yang punya relasi meeting.event
 *   'assessment' -> resource tes/kuis yang punya relasi learningEvent
 *   'tot_assessment' -> resource ToT yang punya relasi learningEvent dan tutor
 *   'evidence' -> resource bukti dukung yang punya relasi learningEvent
 *   'payment' -> payment terms yang punya relasi learningEvent
 *   'certificate_template' -> resource desain sertifikat yang punya relasi learningEvent
 *   null          -> tanpa pembatasan data
 */
trait RoleScoped
{
    /** @return array<UserRole> */
    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    /** @return array<UserRole> */
    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return null;
    }

    protected static function currentRole(): ?UserRole
    {
        return auth()->user()?->role;
    }

    protected static function roleIn(array $roles): bool
    {
        return in_array(static::currentRole(), $roles, true);
    }

    protected static function tutorTrainingLocked(): bool
    {
        $user = auth()->user();

        return $user?->role === UserRole::Tutor
            && ! (bool) $user->tutor?->tot_completed
            && static::scopeType() !== 'tot_assessment';
    }

    protected static function scopeLearningEventBuilder(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user || $user->role === UserRole::SuperAdmin) {
            return $query;
        }

        if ($user->role === UserRole::Admin) {
            return $query->where('created_by', $user->id);
        }

        if ($user->role === UserRole::Tutor) {
            $tutorId = $user->tutor?->id;

            return $query->where(function (Builder $eventQuery) use ($user, $tutorId): void {
                // Tutor hanya melihat event yang ditugaskan kepadanya.
                if ($tutorId) {
                    $eventQuery->whereHas('tutors', fn (Builder $tutorQuery) => $tutorQuery->where('tutors.id', $tutorId));
                } else {
                    $eventQuery->whereRaw('1 = 0');
                }
            });
        }

        if ($user->role === UserRole::Peserta) {
            $participantId = $user->participant?->id;

            return $query->where(function (Builder $eventQuery) use ($user, $participantId): void {
                if ($participantId) {
                    $eventQuery->whereHas('participants', fn (Builder $participantQuery) => $participantQuery->where('participants.id', $participantId));
                } elseif ($user->school_id) {
                    $eventQuery->where('school_id', $user->school_id);
                } else {
                    $eventQuery->whereRaw('1 = 0');
                }
            });
        }

        return $query->whereRaw('1 = 0');
    }

    protected static function scopedLearningEventOptions(Builder $query): Builder
    {
        return static::scopeLearningEventBuilder($query)->orderBy('title');
    }

    protected static function scopedLearningMeetingOptions(Builder $query): Builder
    {
        return match (auth()->user()?->role) {
            UserRole::SuperAdmin => $query,
            default => $query->whereHas('event', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
        };
    }

    /**
     * Lokasi yang boleh dikelola user non-Super Admin: lokasi akunnya sendiri, lokasi event yang
     * dibuat (admin daerah) atau diikuti (tutor/peserta), dan lokasi yang baru dibuat di sesi ini.
     *
     * @return array<int, int>
     */
    public static function managedSchoolIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $eventSchoolIds = match ($user->role) {
            UserRole::Admin => LearningEvent::query()->where('created_by', $user->id)->pluck('school_id'),
            UserRole::Tutor => $user->tutor
                ? LearningEvent::query()->whereHas('tutors', fn (Builder $query) => $query->where('tutors.id', $user->tutor->id))->pluck('school_id')
                : collect(),
            UserRole::Peserta => $user->participant
                ? LearningEvent::query()->whereHas('participants', fn (Builder $query) => $query->where('participants.id', $user->participant->id))->pluck('school_id')
                : collect(),
            default => collect(),
        };

        return collect([$user->school_id])
            ->merge($eventSchoolIds)
            ->merge(session('created_school_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** Catat lokasi yang baru dibuat user agar langsung bisa dipilih di form. */
    public static function rememberCreatedSchool(int $schoolId): void
    {
        session()->push('created_school_ids', $schoolId);
    }

    protected static function scopeSchoolBuilder(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user || $user->role === UserRole::SuperAdmin) {
            return $query;
        }

        return $query->whereKey(static::managedSchoolIds());
    }

    protected static function scopedSchoolOptions(): Collection
    {
        return static::scopeSchoolBuilder(School::query())->orderBy('name')->pluck('name', 'id');
    }

    protected static function scopedUserOptions(UserRole $role): Collection
    {
        $query = User::query()->where('role', $role->value)->orderBy('name');
        $user = auth()->user();

        if ($user?->role !== UserRole::SuperAdmin) {
            $query->whereIn('school_id', static::managedSchoolIds());
        }

        return $query->pluck('name', 'id');
    }

    protected static function scopedParticipantOptions(): Collection
    {
        $user = auth()->user();
        $query = Participant::query()->with('user');

        if ($user?->role === UserRole::Peserta) {
            $query->whereKey($user->participant?->id);
        } elseif ($user?->role !== UserRole::SuperAdmin) {
            $query->whereHas('learningEvents', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery));
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Participant $participant) => [$participant->id => $participant->user?->name ?? 'Peserta #' . $participant->id]);
    }

    protected static function scopedTutorOptions(): Collection
    {
        $user = auth()->user();
        $query = Tutor::query()->with('user');

        if ($user?->role === UserRole::Tutor) {
            $query->whereKey($user->tutor?->id);
        } elseif ($user?->role !== UserRole::SuperAdmin) {
            $query->whereHas('learningEvents', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery));
        }

        return $query
            ->get()
            ->mapWithKeys(fn (Tutor $tutor) => [$tutor->id => $tutor->user?->name ?? 'Tutor #' . $tutor->id]);
    }

    protected static function scopedTrainingSessionOptions(): Collection
    {
        $user = auth()->user();
        $query = TrainingSession::query()->orderByDesc('date')->orderBy('title');

        if ($user?->role !== UserRole::SuperAdmin) {
            $query->whereIn('school_id', static::managedSchoolIds());
        }

        return $query->pluck('title', 'id');
    }

    protected static function scopedAssessmentOptions(): Collection
    {
        return Assessment::query()
            ->whereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery))
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    /** Menu Admin RTIK Daerah dibatasi: Kelola Event dan Presensi Kehadiran (plus halaman Kode Absensi & Peserta Lengkap). */
    public static function shouldRegisterNavigation(): bool
    {
        if (! static::$shouldRegisterNavigation) {
            return false;
        }

        if (static::currentRole() === UserRole::Admin) {
            return in_array(static::class, [
                \App\Filament\Resources\LearningEventResource::class,
                \App\Filament\Resources\AttendanceResource::class,
            ], true);
        }

        return true;
    }

    public static function canViewAny(): bool
    {
        if (static::tutorTrainingLocked()) {
            return false;
        }

        return static::roleIn([...static::viewRoles(), ...static::manageRoles()]);
    }

    public static function canCreate(): bool
    {
        if (static::tutorTrainingLocked()) {
            return false;
        }

        return static::roleIn(static::manageRoles());
    }

    public static function canEdit(Model $record): bool
    {
        if (static::tutorTrainingLocked()) {
            return false;
        }

        return static::roleIn(static::manageRoles());
    }

    public static function canDelete(Model $record): bool
    {
        if (static::tutorTrainingLocked()) {
            return false;
        }

        return static::roleIn(static::manageRoles());
    }

    public static function canDeleteAny(): bool
    {
        if (static::tutorTrainingLocked()) {
            return false;
        }

        return static::roleIn(static::manageRoles());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->role === UserRole::SuperAdmin) {
            return $query;
        }

        return match (static::scopeType()) {
            'school' => $query->whereIn('school_id', static::managedSchoolIds()),
            'session' => $query->whereHas('session', fn ($q) => $q->whereIn('school_id', static::managedSchoolIds())),
            'school_self' => $query->whereIn('id', static::managedSchoolIds()),
            'participant_self' => $user->role === UserRole::Peserta
                ? $query->whereKey($user->participant?->id)
                : $query->whereHas('learningEvents', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'tutor_self' => $user->role === UserRole::Tutor
                ? $query->whereKey($user->tutor?->id)
                : $query->whereHas('learningEvents', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'participant' => $user->role === UserRole::Peserta
                ? $query->where('participant_id', $user->participant?->id)
                : $query->whereHas('participant.learningEvents', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'learning_event' => static::scopeLearningEventBuilder($query),
            'learning_meeting' => $query->whereHas('event', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'learning_material' => $query->whereHas('meeting.event', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'assessment' => $query->whereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'tot_assessment' => $user->role === UserRole::Tutor
                ? $query->where('tutor_id', $user->tutor?->id)
                : $query->whereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'evidence' => $query->whereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'payment' => $query->whereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery)),
            'certificate_template' => $query->where(function (Builder $templateQuery): void {
                $templateQuery
                    ->whereNull('learning_event_id')
                    ->orWhereHas('learningEvent', fn (Builder $eventQuery) => static::scopeLearningEventBuilder($eventQuery));
            }),
            default => $query,
        };
    }
}
