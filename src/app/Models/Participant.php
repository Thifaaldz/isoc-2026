<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date', 'consent_at' => 'datetime',
            'followed_instagram' => 'boolean', 'joined_wag' => 'boolean',
            'registered_ecert' => 'boolean', 'is_cadre' => 'boolean',
        ];
    }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function attempts(): HasMany { return $this->hasMany(AssessmentAttempt::class); }

}
