<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSession extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['date' => 'date']; }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }

}
