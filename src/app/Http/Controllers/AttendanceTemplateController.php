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

        return view('attendance.template', [
            'session' => $session,
            'participants' => $participants,
        ]);
    }
}
