<?php

namespace App\Http\Controllers;

use App\Models\LearningEvent;
use App\Models\Tutor;
use App\Models\User;
use App\Services\TutorCertificateService;
use App\Support\QrImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/** Tanda tangan elektronik tutor (QR Code) di laporan kegiatan: QR berisi signed URL ke halaman verifikasi ini. */
class TutorSignatureVerificationController
{
    /** URL verifikasi bertanda tangan (tidak bisa dipalsukan dengan mengganti ID). */
    public static function url(LearningEvent $event, Tutor $tutor): string
    {
        return URL::signedRoute('signatures.tutor.verify', ['event' => $event->id, 'tutor' => $tutor->id]);
    }

    /** QR PNG (data URI) untuk kolom tanda tangan tutor di PDF. */
    public static function qr(LearningEvent $event, Tutor $tutor): ?string
    {
        return QrImage::png(static::url($event, $tutor), 4);
    }

    public static function facilitatorUrl(LearningEvent $event, User $facilitator): string
    {
        return URL::signedRoute('signatures.facilitator.verify', ['event' => $event->id, 'user' => $facilitator->id]);
    }

    /** Narasumber yang belum terdaftar di sistem: dikenali dari ID entri sertifikat manual. */
    public static function externalUrl(LearningEvent $event, string $id): string
    {
        return URL::signedRoute('signatures.speaker.verify', ['event' => $event->id, 'speaker' => $id]);
    }

    public function show(Request $request, int $event, int $tutor): View
    {
        $learningEvent = LearningEvent::query()->with('school')->find($event);
        $tutorModel = $learningEvent ? app(TutorCertificateService::class)->tutors($learningEvent)->firstWhere('id', $tutor) : null;
        $valid = $request->hasValidSignature() && $learningEvent && $tutorModel;

        return view('signatures.tutor-verify', [
            'valid' => $valid,
            'event' => $valid ? $learningEvent : null,
            'name' => $tutorModel?->user?->name,
            'institution' => $tutorModel?->institution,
            'totStatus' => $tutorModel ? ($tutorModel->tot_completed ? 'Lulus ToT' : 'Belum ToT') : null,
        ]);
    }

    public function showFacilitator(Request $request, int $event, int $user): View
    {
        $learningEvent = LearningEvent::query()->with(['school', 'creator.school'])->find($event);
        $facilitator = $learningEvent ? app(TutorCertificateService::class)->facilitators($learningEvent)->firstWhere('id', $user) : null;
        $valid = $request->hasValidSignature() && $facilitator;

        return view('signatures.tutor-verify', [
            'valid' => $valid,
            'event' => $valid ? $learningEvent : null,
            'name' => $facilitator?->name,
            'institution' => $facilitator?->school?->name,
            'totStatus' => null,
        ]);
    }

    public function showExternal(Request $request, int $event, string $speaker): View
    {
        $learningEvent = LearningEvent::query()->with('school')->find($event);
        $entry = $learningEvent ? app(TutorCertificateService::class)->externalSpeaker($learningEvent, $speaker) : null;
        $valid = $request->hasValidSignature() && $entry;

        return view('signatures.tutor-verify', [
            'valid' => $valid,
            'event' => $valid ? $learningEvent : null,
            'name' => $entry['name'] ?? null,
            'institution' => $entry['institution'] ?? null,
            'totStatus' => null,
        ]);
    }
}
