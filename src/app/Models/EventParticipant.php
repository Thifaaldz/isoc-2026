<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Baris pendaftaran peserta di satu event (tabel pivot learning_event_participant), dipakai tabel rekap peserta. */
class EventParticipant extends Model
{
    protected $table = 'learning_event_participant';

    protected $guarded = [];

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }
    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }
}
