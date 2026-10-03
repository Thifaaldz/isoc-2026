<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\LearningEvent;
use App\Services\CertificateEligibilityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

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

    /** Semua e-sertifikat terbit dalam satu event, dijadikan ZIP untuk bukti dukung laporan. */
    public function downloadEvent(LearningEvent $event): BinaryFileResponse
    {
        abort_unless($this->canAccessEvent($event), 403);
        abort_unless(class_exists(ZipArchive::class), Response::HTTP_INTERNAL_SERVER_ERROR, 'Ekstensi ZIP PHP belum aktif.');

        set_time_limit(0);

        $service = app(CertificateEligibilityService::class);
        $certificates = $event->participants()->with('user')->get()
            ->map(fn ($participant) => $service->ensureCertificate($participant, $event))
            ->filter(fn (Certificate $certificate) => $certificate->isIssued());

        abort_if($certificates->isEmpty(), 404, 'Belum ada e-sertifikat yang terbit untuk event ini.');

        $path = tempnam(sys_get_temp_dir(), 'isoc-certificates-');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);

        foreach ($certificates as $certificate) {
            $certificate->load(['participant.user', 'participant.school', 'learningEvent', 'certificateTemplate']);
            $name = Str::slug($certificate->participant?->user?->name ?: 'peserta') . '-' . $this->filename($certificate);
            $zip->addFromString($name, $this->pdf($certificate)->output());
        }

        $zip->close();

        return response()
            ->download($path, 'e-sertifikat-' . Str::slug($event->title) . '.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    private function eligibleCertificate(Certificate $certificate): Certificate
    {
        abort_unless($this->canAccess($certificate), 403);

        $certificate = app(CertificateEligibilityService::class)->updateCertificate($certificate);

        // Sertifikat yang sudah diterbitkan tetap berlaku walau aturan eligibility berubah;
        // pembatalan dilakukan lewat status "Dicabut".
        abort_unless($certificate->isIssued() || $certificate->isEligible(), 403, 'Sertifikat belum eligible untuk dicetak.');

        // Peserta baru bisa mencetak setelah admin menekan "Terbitkan"; admin, tutor, dan pusat
        // tetap bisa melihat preview tanpa mengubah status penerbitan.
        abort_if(
            auth()->user()?->role === UserRole::Peserta && ! $certificate->isIssued(),
            403,
            'Sertifikat belum diterbitkan oleh admin.',
        );

        return $certificate->load(['participant.user', 'participant.school', 'learningEvent', 'certificateTemplate']);
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

        return $this->canAccessEvent($certificate->learningEvent);
    }

    private function canAccessEvent(?LearningEvent $event): bool
    {
        $user = auth()->user();

        if ($user?->role === UserRole::SuperAdmin) {
            return true;
        }

        if ($user?->role === UserRole::Admin) {
            return $event && ($event->created_by === $user->id
                || in_array((int) $event->school_id, \App\Filament\Resources\LearningEventResource::managedSchoolIds(), true));
        }

        if ($user?->role === UserRole::Tutor) {
            return $event && $event->tutors()->where('tutors.user_id', $user->id)->exists();
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
