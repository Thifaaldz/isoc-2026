<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $guarded = [];

    public function session(): BelongsTo { return $this->belongsTo(TrainingSession::class, 'training_session_id'); }
    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

}
