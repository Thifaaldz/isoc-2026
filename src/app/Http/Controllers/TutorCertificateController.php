<?php

namespace App\Http\Controllers;

use App\Models\LearningEvent;
use App\Services\TutorCertificateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    private function assignedTutor(Request $request, LearningEvent $event)
    {
        $tutor = $request->user()?->tutor;

        abort_unless($tutor && $event->tutors()->whereKey($tutor->id)->exists(), 403, 'Sertifikat tutor hanya untuk tutor yang ditugaskan di event ini.');

        return $tutor->loadMissing(['user', 'school']);
    }
}
