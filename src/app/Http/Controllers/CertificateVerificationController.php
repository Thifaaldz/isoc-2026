<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\View\View;

class CertificateVerificationController
{
    public function show(string $certificateNumber): View
    {
        return view('certificates.verify', [
            'certificate' => Certificate::query()
                ->with('participant.user')
                ->where('number', $certificateNumber)
                ->where('status', 'issued')
                ->first(),
        ]);
    }
}
