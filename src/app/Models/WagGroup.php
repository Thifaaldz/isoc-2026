<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WagGroup extends Model
{
    protected $guarded = [];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

}
