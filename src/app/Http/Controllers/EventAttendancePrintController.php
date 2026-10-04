<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Support\TorEventTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Cetak absensi per event: template absensi basah (TOR) dan rekap absensi online/kode. */
class EventAttendancePrintController
{
    /** Daftar hadir tanda tangan basah: 3 tutor + 100 peserta dengan nama yang sudah terdaftar. */
    public function wet(LearningEvent $learningEvent): View
    {
        abort_unless($this->canAccess($learningEvent), 403);

        $learningEvent->load(['school', 'tutors.user', 'participants.user']);
        $participants = $learningEvent->participants->sortBy(fn ($participant) => $participant->user?->name)->values();
        $tutors = $learningEvent->tutors->values();

        return view('attendance.template', [
            'sheetTitle' => $learningEvent->title,
            'locationName' => $learningEvent->school?->name,
            'dateText' => $this->dateText($learningEvent),
            'participants' => $participants,
            'tutors' => $tutors,
            'tutorRows' => max(TorEventTemplate::DEFAULT_TUTORS, $tutors->count()),
            'participantRows' => max(TorEventTemplate::DEFAULT_PARTICIPANTS, $participants->count()),
        ]);
    }

    /** Rekap absensi yang diisi peserta lewat kode absensi; ?mode=online|offline untuk memfilter. */
    public function digital(Request $request, LearningEvent $learningEvent): View
    {
        abort_unless($this->canAccess($learningEvent), 403);

        $mode = in_array($request->query('mode'), ['online', 'offline'], true) ? $request->query('mode') : null;
        $learningEvent->load(['school', 'tutors.user', 'participants.user']);

        $attendances = Attendance::query()
            ->with('participant.user')
            ->where('learning_event_id', $learningEvent->id)
            ->whereNotNull('participant_id')
            ->when($mode, fn ($query) => $query->where('mode', $mode))
            ->orderBy('checked_in_at')
            ->get();

        $checkedInIds = Attendance::query()
            ->where('learning_event_id', $learningEvent->id)
            ->whereNotNull('participant_id')
            ->pluck('participant_id');

        $absent = $learningEvent->participants
            ->reject(fn ($participant) => $checkedInIds->contains($participant->id))
            ->sortBy(fn ($participant) => $participant->user?->name)
            ->values();

        $all = Attendance::query()->where('learning_event_id', $learningEvent->id)->whereNotNull('participant_id')->get(['mode']);

        return view('attendance.digital', [
            'event' => $learningEvent,
            'mode' => $mode,
            'dateText' => $this->dateText($learningEvent),
            'attendances' => $attendances,
            'absent' => $mode ? collect() : $absent,
            'summary' => [
                'registered' => $learningEvent->participants->count(),
                'offline' => $all->where('mode', 'offline')->count(),
                'online' => $all->where('mode', 'online')->count(),
                'absent' => $absent->count(),
            ],
        ]);
    }

    private function dateText(LearningEvent $event): string
    {
        if (! $event->starts_at) {
            return '-';
        }

        return $event->starts_at->translatedFormat('l, d F Y') . ' | ' . $event->starts_at->format('H:i')
            . ($event->ends_at ? ' - ' . $event->ends_at->format('H:i') : '') . ' WIB';
    }

    private function canAccess(LearningEvent $event): bool
    {
        $user = auth()->user();

        return match ($user?->role) {
            UserRole::SuperAdmin => true,
            UserRole::Admin => (int) $event->created_by === (int) $user->id,
            UserRole::Tutor => $event->tutors()->where('tutors.user_id', $user->id)->exists(),
            default => false,
        };
    }
}
