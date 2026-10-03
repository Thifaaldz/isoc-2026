<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['answers' => 'array', 'submitted_at' => 'datetime']; }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

}
