<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\User;
use App\Services\TutorCertificateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Tutor mencetak sertifikat tutornya sendiri untuk event yang ia dampingi. */
class TutorCertificateController
{
    public function __construct(private readonly TutorCertificateService $certificates)
    {
    }

    public function preview(Request $request, LearningEvent $event): Response
    {
        $tutor = $this->assignedTutor($request, $event);

        return $this->certificates->pdf($event, $tutor)->stream($this->certificates->filename($event, $tutor));
    }

    public function download(Request $request, LearningEvent $event): Response
    {
        $tutor = $this->assignedTutor($request, $event);

        return $this->certificates->pdf($event, $tutor)->download($this->certificates->filename($event, $tutor));
    }

    /** Admin RTIK Pusat dan fasilitator pembuat event mengunduh sertifikat semua tutor event. */
    public function downloadEvent(Request $request, LearningEvent $event): Response
    {
        $this->authorizeManager($request, $event);
        abort_unless($this->certificates->tutors($event)->isNotEmpty(), 404, 'Belum ada tutor yang ditugaskan di event ini.');

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        return $this->certificates->eventPdf($event)->download('e-sertifikat-narasumber-' . Str::slug($event->title) . '.pdf');
    }

    public function managerPreview(Request $request, LearningEvent $event, int $tutor): Response
    {
        $tutor = $this->eventTutor($request, $event, $tutor);

        return $this->certificates->pdf($event, $tutor)->stream($this->certificates->filename($event, $tutor));
    }

    public function managerDownload(Request $request, LearningEvent $event, int $tutor): Response
    {
        $tutor = $this->eventTutor($request, $event, $tutor);

        return $this->certificates->pdf($event, $tutor)->download($this->certificates->filename($event, $tutor));
    }

    public function facilitatorPreview(Request $request, LearningEvent $event, ?int $user = null): Response
    {
        $facilitator = $this->eventFacilitator($request, $event, $user);

        return $this->certificates->facilitatorPdf($event, $facilitator)->stream($this->certificates->facilitatorFilename($event, $facilitator));
    }

    public function facilitatorDownload(Request $request, LearningEvent $event, ?int $user = null): Response
    {
        $facilitator = $this->eventFacilitator($request, $event, $user);

        return $this->certificates->facilitatorPdf($event, $facilitator)->download($this->certificates->facilitatorFilename($event, $facilitator));
    }

    /** Tanpa $userId: fasilitator pembuat event. Dengan $userId: fasilitator mana pun yang berhak (termasuk tambahan manual). */
    private function eventFacilitator(Request $request, LearningEvent $event, ?int $userId = null): User
    {
        // Fasilitator yang ditambahkan manual boleh mencetak sertifikatnya sendiri meski bukan pembuat event.
        if ($userId === null || (int) $request->user()?->id !== $userId) {
            $this->authorizeManager($request, $event);
        }

        $facilitator = $userId === null
            ? $this->certificates->facilitator($event)
            : $this->certificates->facilitators($event)->firstWhere('id', $userId);
        abort_unless($facilitator, 404, 'Fasilitator ini tidak punya sertifikat di event ini.');

        return $facilitator->loadMissing('school');
    }

    private function eventTutor(Request $request, LearningEvent $event, int $tutorId): Tutor
    {
        $this->authorizeManager($request, $event);

        $tutor = $this->certificates->tutors($event)->firstWhere('id', $tutorId);
        abort_unless($tutor, 404);

        return $tutor;
    }

    private function authorizeManager(Request $request, LearningEvent $event): void
    {
        $user = $request->user();

        abort_unless(
            $user?->role === UserRole::SuperAdmin
                || ($user?->role === UserRole::Admin && (int) $event->created_by === (int) $user->id),
            403,
        );
    }

    private function assignedTutor(Request $request, LearningEvent $event)
    {
        $tutor = $request->user()?->tutor;

        abort_unless($tutor && $this->certificates->tutors($event)->contains('id', $tutor->id), 403, 'Sertifikat tutor hanya untuk tutor yang ditugaskan di event ini.');

        return $tutor->loadMissing(['user', 'school']);
    }
}
