<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['checked_in_at' => 'datetime']; }
    public function session(): BelongsTo { return $this->belongsTo(TrainingSession::class, 'training_session_id'); }
    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }
    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

}
