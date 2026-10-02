<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\Partner;
use App\Models\School;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CertificateTemplatePreviewController extends Controller
{
    public function show(CertificateTemplate $template): Response
    {
        abort_unless(auth()->user()?->role === UserRole::SuperAdmin, 403);

        $event = new LearningEvent([
            'title' => 'Nama Kegiatan / Seminar',
            'starts_at' => now(),
        ]);
        $event->setRelation('partners', Partner::query()->where('status', 'active')->limit(4)->get());
        $event->setRelation('certificateTemplate', $template);

        $school = new School([
            'name' => 'Nama Sekolah / Instansi',
        ]);

        $user = new User([
            'name' => 'Nama Peserta',
            'email' => 'peserta@example.test',
        ]);

        $participant = new Participant([
            'grade' => 'Kelas / Peran',
        ]);
        $participant->setRelation('user', $user);
        $participant->setRelation('school', $school);

        $certificate = new Certificate([
            'number' => 'PREVIEW/' . str_pad((string) $template->id, 4, '0', STR_PAD_LEFT),
            'issued_at' => now(),
            'status' => 'issued',
            'eligibility_status' => 'eligible',
        ]);
        $certificate->setRelation('participant', $participant);
        $certificate->setRelation('learningEvent', $event);
        $certificate->setRelation('certificateTemplate', $template);

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qrSvg' => null,
        ])->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ])->stream('preview-template-' . Str::slug($template->name) . '.pdf');
    }
}
