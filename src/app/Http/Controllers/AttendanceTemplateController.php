<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\TrainingSession;
use Illuminate\Contracts\View\View;

class AttendanceTemplateController
{
    public function show(TrainingSession $session): View
    {
        // Daftar hadir memuat data pribadi peserta: hanya Pusat, atau admin/tutor yang mengelola lokasinya.
        $user = auth()->user();
        abort_unless(
            $user?->role === UserRole::SuperAdmin
                || (in_array($user?->role, [UserRole::Admin, UserRole::Tutor], true)
                    && in_array((int) $session->school_id, LearningEventResource::managedSchoolIds(), true)),
            403,
        );

        $session->load(['school']);

        $participants = $session->school
            ? $session->school->participants()->with('user')->orderBy('nis')->get()
            : collect();

        // TOR: absensi nama & tanda tangan basah untuk 3 tutor + 100 peserta per lokasi.
        $tutors = $session->school_id
            ? \App\Models\Tutor::query()
                ->with('user')
                ->whereHas('learningEvents', fn ($query) => $query->where('school_id', $session->school_id))
                ->get()
            : collect();

        return view('attendance.template', [
            'session' => $session,
            'participants' => $participants,
            'tutors' => $tutors,
            'tutorRows' => max(\App\Support\TorEventTemplate::DEFAULT_TUTORS, $tutors->count()),
            'participantRows' => max(\App\Support\TorEventTemplate::DEFAULT_PARTICIPANTS, $participants->count()),
        ]);
    }
}
