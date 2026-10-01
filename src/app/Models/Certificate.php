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

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

}
