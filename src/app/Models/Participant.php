<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    public function learningEvents(): BelongsToMany
    {
        return $this->belongsToMany(LearningEvent::class)
            ->withPivot([
                'status',
                'registered_at',
                'admin_approval_status',
                'admin_approved_by',
                'admin_approved_at',
                'tutor_approval_status',
                'tutor_approved_by',
                'tutor_approved_at',
                'approval_notes',
            ])
            ->withTimestamps();
    }

    public function isApprovedForEvent(LearningEvent $event): bool
    {
        if ($event->audience_type !== 'general') {
            return true;
        }

        $pivot = $this->learningEvents()
            ->where('learning_events.id', $event->id)
            ->first()
            ?->pivot;

        return ($pivot?->admin_approval_status ?? null) === 'approved'
            || ($pivot?->tutor_approval_status ?? null) === 'approved';
    }

}
