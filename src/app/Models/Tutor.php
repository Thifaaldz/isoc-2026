<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tutor extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['tot_completed' => 'boolean', 'is_cadre' => 'boolean']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function learningEvents(): BelongsToMany
    {
        return $this->belongsToMany(LearningEvent::class, 'learning_event_tutor')
            ->withPivot(['status', 'assigned_at'])
            ->withTimestamps();
    }

    public function totAssessments(): HasMany { return $this->hasMany(TotAssessment::class); }

}
