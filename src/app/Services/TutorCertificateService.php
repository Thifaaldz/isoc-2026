<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Controllers\TutorSignatureVerificationController;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\User;
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
    public const COMPETENCY = 'NARASUMBER LITERASI DIGITAL SAFETY CHAMPIONS';

    private const PDF_OPTIONS = [
        'isRemoteEnabled' => true,
        'isHtml5ParserEnabled' => true,
    ];

    /** Event tempat tutor ditugaskan (pendaftaran yang belum divalidasi tidak termasuk). */
    public function events(Tutor $tutor): Collection
    {
        return $tutor->learningEvents()->with('school')->orderBy('starts_at')->get();
    }

    public function number(LearningEvent $event, Tutor $tutor): string
    {
        return 'DSC-NARASUMBER/' . ($event->starts_at ?? now())->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $tutor->id, 5, '0', STR_PAD_LEFT);
    }

    public function filename(LearningEvent $event, Tutor $tutor): string
    {
        return 'sertifikat-narasumber-' . Str::slug($tutor->user?->name ?? 'narasumber') . '-' . Str::slug($event->title) . '.pdf';
    }

    /** Template pilihan event; bila belum dipilih memakai template narasumber, lalu template peserta event dan default. */
    public function template(LearningEvent $event): ?CertificateTemplate
    {
        return $event->tutorCertificateTemplate
            ?? CertificateTemplate::query()->where('name', CertificateTemplate::TUTOR_TEMPLATE_NAME)->first()
            ?? $event->certificateTemplate
            ?? CertificateTemplate::query()->orderByDesc('is_default')->orderBy('name')->first();
    }

    /** Fasilitator event: Admin RTIK Daerah yang membuat event. */
    public function facilitator(LearningEvent $event): ?User
    {
        $creator = $event->creator;

        return $creator?->role === UserRole::Admin ? $creator : null;
    }

    public function facilitatorNumber(LearningEvent $event, User $facilitator): string
    {
        return 'DSC-NARASUMBER-F/' . ($event->starts_at ?? now())->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) $facilitator->id, 5, '0', STR_PAD_LEFT);
    }

    public function facilitatorFilename(LearningEvent $event, User $facilitator): string
    {
        return 'sertifikat-narasumber-' . Str::slug($facilitator->name ?? 'fasilitator') . '-' . Str::slug($event->title) . '.pdf';
    }

    public function facilitatorPdf(LearningEvent $event, User $facilitator)
    {
        return Pdf::loadView('certificates.pdf', $this->holderData(
            $event,
            $this->facilitatorNumber($event, $facilitator),
            $facilitator->name ?? 'Nama Fasilitator',
            $facilitator->school?->name,
            TutorSignatureVerificationController::facilitatorUrl($event, $facilitator),
        ))->setOptions(self::PDF_OPTIONS);
    }

    public function pdf(LearningEvent $event, Tutor $tutor)
    {
        return Pdf::loadView('certificates.pdf', $this->viewData($event, $tutor))->setOptions(self::PDF_OPTIONS);
    }

    /** Sertifikat semua tutor yang ditugaskan di event, satu halaman per tutor, dalam satu PDF. */
    public function eventPdf(LearningEvent $event)
    {
        $head = null;
        $bodies = $event->tutors()->with(['user', 'school'])->get()
            ->sortBy(fn (Tutor $tutor) => $tutor->user?->name)
            ->values()
            ->map(function (Tutor $tutor, int $index) use ($event, &$head): string {
                $html = view('certificates.pdf', $this->viewData($event, $tutor))->render();
                $head ??= Str::before($html, '<body>');
                $body = Str::beforeLast(Str::after($html, '<body>'), '</body>');

                return $index === 0 ? $body : '<div style="page-break-before: always;"></div>' . $body;
            })
            ->implode('');

        return Pdf::loadHTML($head . '<body>' . $bodies . '</body></html>')->setOptions(self::PDF_OPTIONS);
    }

    private function viewData(LearningEvent $event, Tutor $tutor): array
    {
        return $this->holderData(
            $event,
            $this->number($event, $tutor),
            $tutor->user?->name ?? 'Nama Tutor',
            $tutor->institution ?: $tutor->school?->name,
            TutorSignatureVerificationController::url($event, $tutor),
        );
    }

    private function holderData(LearningEvent $event, string $number, string $name, ?string $institution, string $verifyUrl): array
    {
        $certificate = new Certificate([
            'number' => $number,
            'status' => 'issued',
            'issued_at' => $event->ends_at ?? $event->starts_at ?? now(),
        ]);
        $certificate->setRelation('learningEvent', $event);
        $certificate->setRelation('participant', null);
        $certificate->setRelation('certificateTemplate', $this->template($event));

        return [
            'certificate' => $certificate,
            'qrSvg' => null,
            'holder' => [
                'name' => $name,
                'institution' => $institution,
                'role' => 'Narasumber',
                'label' => 'Narasumber',
                'competency' => self::COMPETENCY,
                'verify_url' => $verifyUrl,
            ],
        ];
    }
}
