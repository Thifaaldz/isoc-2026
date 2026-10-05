<?php

namespace App\Http\Controllers;

use App\Models\LearningEvent;
use App\Models\Tutor;
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

    public function show(Request $request, int $event, int $tutor): View
    {
        $learningEvent = LearningEvent::query()->with('school')->find($event);
        $tutorModel = $learningEvent?->tutors()->with('user')->whereKey($tutor)->first();
        $valid = $request->hasValidSignature() && $learningEvent && $tutorModel;

        return view('signatures.tutor-verify', [
            'valid' => $valid,
            'event' => $valid ? $learningEvent : null,
            'tutor' => $valid ? $tutorModel : null,
        ]);
    }
}
