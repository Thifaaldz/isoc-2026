<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Akun "daerah" milik Admin RTIK Daerah: tutor dan peserta yang terdaftar di event yang dibuat admin tersebut.
 * Dipakai halaman Akun & Password untuk reset password massal maupun per akun.
 */
class RegionalAccountService
{
    /** Role yang password-nya boleh direset Admin RTIK Daerah. */
    public const ROLES = [UserRole::Tutor, UserRole::Peserta];

    /** Akun tutor & peserta di event admin; $role membatasi ke satu role. */
    public function accountsQuery(User $admin, ?UserRole $role = null): Builder
    {
        $events = LearningEvent::query()->where('created_by', $admin->id)->select('id');

        $participantUsers = DB::table('participants')
            ->join('learning_event_participant', 'learning_event_participant.participant_id', '=', 'participants.id')
            ->whereIn('learning_event_participant.learning_event_id', $events)
            ->select('participants.user_id');

        $tutorUsers = DB::table('tutors')
            ->join('learning_event_tutor', 'learning_event_tutor.tutor_id', '=', 'tutors.id')
            ->whereIn('learning_event_tutor.learning_event_id', $events)
            ->select('tutors.user_id');

        return User::query()
            ->whereKeyNot($admin->id)
            ->whereIn('role', collect($role ? [$role] : self::ROLES)->map->value->all())
            ->where(fn (Builder $query) => $query
                ->whereIn('id', $participantUsers)
                ->orWhereIn('id', $tutorUsers));
    }

    /**
     * Ganti password akun daerah. $userIds kosong = semua akun (sesuai $role); id di luar daerah admin diabaikan.
     *
     * @param  iterable<int>  $userIds
     */
    public function resetPasswords(User $admin, string $password, iterable $userIds = [], ?UserRole $role = null): int
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter();
        $users = $this->accountsQuery($admin, $role)
            ->when($ids->isNotEmpty(), fn (Builder $query) => $query->whereKey($ids->all()))
            ->get();

        // Simpan per model agar cast "hashed" meng-hash password dan remember token lama tidak berlaku.
        $users->each(fn (User $user) => $user->forceFill(['password' => $password, 'remember_token' => null])->save());

        return $users->count();
    }
}
