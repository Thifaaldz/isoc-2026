<?php

namespace App\Services;

use App\Http\Controllers\TutorSignatureVerificationController;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\Tutor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Sertifikat tutor per event: bisa dicetak tanpa syarat oleh tutor yang ditugaskan di event.
 * Tidak disimpan di tabel certificates; PDF memakai template sertifikat tutor milik event,
 * dan QR mengarah ke verifikasi tutor bertanda tangan (signed URL).
 */
class TutorCertificateService
{
    public const COMPETENCY = 'TUTOR LITERASI DIGITAL SAFETY CHAMPIONS';

    /** Event tempat tutor ditugaskan (pendaftaran yang belum divalidasi tidak termasuk). */
    public function events(Tutor $tutor): Collection
    {
        return $tutor->learningEvents()->with('school')->orderBy('starts_at')->get();
    }

    public function number(LearningEvent $event, Tutor $tutor): string
    {
        return 'DSC-TUTOR/' . ($event->starts_at ?? now())->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $tutor->id, 5, '0', STR_PAD_LEFT);
    }

    public function filename(LearningEvent $event, Tutor $tutor): string
    {
        return 'sertifikat-tutor-' . Str::slug($tutor->user?->name ?? 'tutor') . '-' . Str::slug($event->title) . '.pdf';
    }

    /** Template tutor event; bila belum dipilih memakai template peserta event, lalu template default. */
    public function template(LearningEvent $event): ?CertificateTemplate
    {
        return $event->tutorCertificateTemplate
            ?? $event->certificateTemplate
            ?? CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->first();
    }

    public function pdf(LearningEvent $event, Tutor $tutor)
    {
        $certificate = new Certificate([
            'number' => $this->number($event, $tutor),
            'status' => 'issued',
            'issued_at' => $event->ends_at ?? $event->starts_at ?? now(),
        ]);
        $certificate->setRelation('learningEvent', $event);
        $certificate->setRelation('participant', null);
        $certificate->setRelation('certificateTemplate', $this->template($event));

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'qrSvg' => null,
            'holder' => [
                'name' => $tutor->user?->name ?? 'Nama Tutor',
                'institution' => $tutor->institution ?: $tutor->school?->name,
                'role' => 'Tutor / Fasilitator',
                'label' => 'Tutor',
                'competency' => self::COMPETENCY,
                'verify_url' => TutorSignatureVerificationController::url($event, $tutor),
            ],
        ])->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);
    }
}
