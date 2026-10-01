<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Services\CertificateEligibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CertificatePdfController extends Controller
{
    public function preview(Certificate $certificate): Response
    {
        $certificate = $this->eligibleCertificate($certificate);

        return $this->pdf($certificate)->stream($this->filename($certificate));
    }

    public function download(Certificate $certificate): Response
    {
        $certificate = $this->eligibleCertificate($certificate);

        return $this->pdf($certificate)->download($this->filename($certificate));
    }

    private function eligibleCertificate(Certificate $certificate): Certificate
    {
        abort_unless($this->canAccess($certificate), 403);

        $certificate = app(CertificateEligibilityService::class)->updateCertificate($certificate);

        abort_unless($certificate->eligibility_status === 'eligible', 403, 'Sertifikat belum eligible untuk dicetak.');

        if ($certificate->status !== 'issued') {
            $certificate->update([
                'status' => 'issued',
                'issued_at' => now(),
            ]);
        }

        return $certificate->refresh()->load(['participant.user', 'participant.school', 'learningEvent', 'certificateTemplate']);
    }

    private function canAccess(Certificate $certificate): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user->role === UserRole::Peserta) {
            return $certificate->participant_id === $user->participant?->id;
        }

        if (in_array($user->role, [UserRole::Admin, UserRole::Tutor], true)) {
            return $certificate->participant?->school_id === $user->school_id;
        }

        return false;
    }

    private function pdf(Certificate $certificate)
    {
        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qrSvg' => null,
        ])->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);
    }

    private function filename(Certificate $certificate): string
    {
        return Str::slug($certificate->number ?: 'sertifikat') . '.pdf';
    }
}
