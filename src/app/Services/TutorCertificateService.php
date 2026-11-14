<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Controllers\TutorSignatureVerificationController;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\User;
use App\Support\SpeakerCertificates;
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

    /** Event tempat tutor ditugaskan (pendaftaran yang belum divalidasi tidak termasuk) atau diberi sertifikat manual. */
    public function events(Tutor $tutor): Collection
    {
        return LearningEvent::query()
            ->with('school')
            ->where(fn ($query) => $query
                ->whereIn('id', $tutor->learningEvents()->select('learning_events.id'))
                ->orWhereIn('id', SpeakerCertificates::eventIds(SpeakerCertificates::ROLE_TUTOR, (int) $tutor->user_id)))
            ->orderBy('starts_at')
            ->get();
    }

    /** Tutor yang berhak atas sertifikat event: tutor yang ditugaskan dan tutor yang ditambahkan manual. */
    public function tutors(LearningEvent $event): Collection
    {
        return Tutor::query()
            ->with(['user', 'school'])
            ->where(fn ($query) => $query
                ->whereIn('id', $event->tutors()->select('tutors.id'))
                ->orWhereIn('user_id', SpeakerCertificates::userIds(SpeakerCertificates::ROLE_TUTOR, $event->id)))
            ->get()
            ->sortBy(fn (Tutor $tutor) => $tutor->user?->name)
            ->values();
    }

    /** Fasilitator yang berhak atas sertifikat event: pembuat event dan fasilitator yang ditambahkan manual. */
    public function facilitators(LearningEvent $event): \Illuminate\Support\Collection
    {
        $manual = User::query()
            ->with('school')
            ->where('role', UserRole::Admin)
            ->whereIn('id', SpeakerCertificates::userIds(SpeakerCertificates::ROLE_FACILITATOR, $event->id))
            ->get();

        return collect([$this->facilitator($event)?->loadMissing('school')])
            ->filter()
            ->concat($manual)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /** Event yang memberi fasilitator sertifikat: event buatannya dan event yang ditambahkan manual. */
    public function facilitatorEvents(User $facilitator): Collection
    {
        if ($facilitator->role !== UserRole::Admin) {
            return new Collection();
        }

        return LearningEvent::query()
            ->where(fn ($query) => $query
                ->where('created_by', $facilitator->id)
                ->orWhereIn('id', SpeakerCertificates::eventIds(SpeakerCertificates::ROLE_FACILITATOR, $facilitator->id)))
            ->orderByDesc('starts_at')
            ->get();
    }

    /** @return \Illuminate\Support\Collection<int, array> sertifikat manual event, dikunci dengan user_id. */
    public function manualCertificates(LearningEvent $event, string $role): \Illuminate\Support\Collection
    {
        return SpeakerCertificates::where($role, eventId: $event->id)->keyBy('user_id');
    }

    /** @return \Illuminate\Support\Collection<int, array> narasumber belum terdaftar di sistem untuk event ini. */
    public function externalSpeakers(LearningEvent $event, string $role): \Illuminate\Support\Collection
    {
        return SpeakerCertificates::external($role, $event->id);
    }

    /** Entri narasumber belum terdaftar milik event; null bila tidak ada / sudah dihapus. */
    public function externalSpeaker(LearningEvent $event, string $id): ?array
    {
        $entry = SpeakerCertificates::find($id);

        return $entry && SpeakerCertificates::isExternal($entry) && (int) $entry['learning_event_id'] === $event->id ? $entry : null;
    }

    public function externalNumber(LearningEvent $event, array $entry): string
    {
        return 'DSC-NARASUMBER-' . ($entry['role'] === SpeakerCertificates::ROLE_FACILITATOR ? 'XF' : 'XT') . '/' . ($event->starts_at ?? now())->format('Y') . '/' . str_pad((string) $event->id, 4, '0', STR_PAD_LEFT) . '/' . str_pad((string) ($entry['seq'] ?? 0), 5, '0', STR_PAD_LEFT);
    }

    public function externalFilename(LearningEvent $event, array $entry): string
    {
        return 'sertifikat-narasumber-' . Str::slug($entry['name'] ?? 'narasumber') . '-' . Str::slug($event->title) . '.pdf';
    }

    public function externalPdf(LearningEvent $event, array $entry)
    {
        return Pdf::loadView('certificates.pdf', $this->externalViewData($event, $entry))->setOptions(self::PDF_OPTIONS);
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

    /** Sertifikat semua tutor event (termasuk narasumber tutor yang belum terdaftar), satu halaman per orang, dalam satu PDF. */
    public function eventPdf(LearningEvent $event)
    {
        $head = null;
        $bodies = $this->tutors($event)
            ->map(fn (Tutor $tutor) => $this->viewData($event, $tutor))
            ->concat($this->externalSpeakers($event, SpeakerCertificates::ROLE_TUTOR)->map(fn (array $entry) => $this->externalViewData($event, $entry)))
            ->values()
            ->map(function (array $data, int $index) use (&$head): string {
                $html = view('certificates.pdf', $data)->render();
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

    private function externalViewData(LearningEvent $event, array $entry): array
    {
        return $this->holderData(
            $event,
            $this->externalNumber($event, $entry),
            $entry['name'],
            $entry['institution'] ?? null,
            TutorSignatureVerificationController::externalUrl($event, $entry['id']),
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
