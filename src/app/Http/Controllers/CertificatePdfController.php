<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\LearningEvent;
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

    /** Semua e-sertifikat terbit dalam satu event, digabung menjadi satu PDF panjang untuk dicetak / bukti dukung. */
    public function downloadEvent(LearningEvent $event): Response
    {
        abort_unless($this->canAccessEvent($event), 403);

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $service = app(CertificateEligibilityService::class);
        $certificates = $event->participants()->with('user')->get()
            ->map(fn ($participant) => $service->ensureCertificate($participant, $event))
            ->filter(fn (Certificate $certificate) => $certificate->isIssued())
            ->sortBy(fn (Certificate $certificate) => $certificate->participant?->user?->name)
            ->values();

        abort_if($certificates->isEmpty(), 404, 'Belum ada e-sertifikat yang terbit untuk event ini.');

        // Setiap sertifikat dirender dengan template yang sama; isi <body> digabung berurutan dalam satu dokumen.
        $head = null;
        $bodies = $certificates->map(function (Certificate $certificate, int $index) use (&$head): string {
            $certificate->load(['participant.user', 'participant.school', 'learningEvent', 'certificateTemplate']);
            $html = view('certificates.pdf', ['certificate' => $certificate, 'qrSvg' => null])->render();
            $head ??= Str::before($html, '<body>');
            $body = Str::beforeLast(Str::after($html, '<body>'), '</body>');

            return $index === 0 ? $body : '<div style="page-break-before: always;"></div>' . $body;
        })->implode('');

        $pdf = Pdf::loadHTML($head . '<body>' . $bodies . '</body></html>')->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download('e-sertifikat-' . Str::slug($event->title) . '.pdf');
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
            // Fasilitator hanya mengakses event yang ia buat (bukan event fasilitator lain di lokasi yang sama).
            return $event && (int) $event->created_by === (int) $user->id;
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
