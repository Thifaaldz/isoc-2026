<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'eligibility_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Sertifikat hanya boleh berstatus terbit bila syarat eligibility benar-benar terpenuhi.
        static::saving(function (Certificate $certificate): void {
            if ($certificate->status !== 'issued' || ! $certificate->isDirty('status')) {
                return;
            }

            $result = ($certificate->participant && $certificate->learningEvent)
                ? app(\App\Services\CertificateEligibilityService::class)->check($certificate->participant, $certificate->learningEvent)
                : ['eligible' => false, 'notes' => ['Peserta atau event belum ditentukan.']];

            if (! $result['eligible']) {
                $certificate->status = $certificate->getOriginal('status') ?: 'pending';
                $certificate->issued_at = $certificate->getOriginal('issued_at');
                $certificate->eligibility_status = 'blocked';
                $certificate->eligibility_notes = implode("\n", $result['notes']);
            }
        });
    }

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function isEligible(): bool
    {
        return $this->eligibility_status === 'eligible';
    }

    /** Peserta hanya bisa mencetak sertifikat yang sudah diterbitkan admin (dicek eligible saat diterbitkan). */
    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }
}
