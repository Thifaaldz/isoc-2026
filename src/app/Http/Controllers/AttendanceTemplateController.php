<?php

namespace App\Http\Controllers;

use App\Models\TrainingSession;
use Illuminate\Contracts\View\View;

class AttendanceTemplateController
{
    public function show(TrainingSession $session): View
    {
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
