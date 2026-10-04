<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\TrainingSession;
use Illuminate\Contracts\View\View;

class AttendanceTemplateController
{
    public function show(TrainingSession $session): View
    {
        // Daftar hadir memuat data pribadi: Pusat, atau fasilitator/tutor yang punya event di lokasi ini.
        $user = auth()->user();
        $events = \App\Models\LearningEvent::query()
            ->where('school_id', $session->school_id)
            ->when($user?->role === UserRole::Admin, fn ($query) => $query->where('created_by', $user->id))
            ->when($user?->role === UserRole::Tutor, fn ($query) => $query->whereHas('tutors', fn ($tutors) => $tutors->where('tutors.id', $user->tutor?->id)));

        abort_unless(
            $user?->role === UserRole::SuperAdmin
                || (in_array($user?->role, [UserRole::Admin, UserRole::Tutor], true) && $session->school_id && (clone $events)->exists()),
            403,
        );

        $session->load(['school']);
        $eventIds = $events->pluck('id');

        // Hanya peserta & tutor dari event yang boleh dilihat user ini (Pusat: semua event di lokasi).
        $participants = \App\Models\Participant::query()
            ->with('user')
            ->whereHas('learningEvents', fn ($query) => $query->whereIn('learning_events.id', $eventIds))
            ->orderBy('nis')
            ->get();

        // TOR: absensi nama & tanda tangan basah untuk 3 tutor + 100 peserta per lokasi.
        $tutors = \App\Models\Tutor::query()
            ->with('user')
            ->whereHas('learningEvents', fn ($query) => $query->whereIn('learning_events.id', $eventIds))
            ->get();

        return view('attendance.template', [
            'sheetTitle' => $session->title,
            'locationName' => $session->school?->name,
            'dateText' => ($session->date?->translatedFormat('l, d F Y') ?? '-') . ' | ' . $session->start_time . ' - ' . $session->end_time . ' WIB',
            'participants' => $participants,
            'tutors' => $tutors,
            'tutorRows' => max(\App\Support\TorEventTemplate::DEFAULT_TUTORS, $tutors->count()),
            'participantRows' => max(\App\Support\TorEventTemplate::DEFAULT_PARTICIPANTS, $participants->count()),
        ]);
    }
}
