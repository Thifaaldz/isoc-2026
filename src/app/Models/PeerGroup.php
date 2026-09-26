<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerGroup extends Model
{
    protected $guarded = [];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

}
