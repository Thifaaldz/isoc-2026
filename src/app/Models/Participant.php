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
    public function micrositePractices(): HasMany { return $this->hasMany(MicrositePractice::class); }
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
        $this->syncInitialApprovalForEvent($event);

        $pivot = $this->learningEvents()
            ->where('learning_events.id', $event->id)
            ->first()
            ?->pivot;

        return ($pivot?->admin_approval_status ?? null) === 'approved'
            || ($pivot?->tutor_approval_status ?? null) === 'approved';
    }

    public function hasInitialProofForEvent(LearningEvent $event): bool
    {
        if (! $this->joined_wag) {
            return false;
        }

        return Evidence::query()
            ->where('learning_event_id', $event->id)
            ->where('uploaded_by', $this->user_id)
            ->where('type', 'follow_ig')
            ->whereNotNull('file_path')
            ->exists();
    }

    public function syncInitialApprovalForEvent(LearningEvent $event): bool
    {
        if (! $this->hasInitialProofForEvent($event)) {
            return false;
        }

        $this->learningEvents()->syncWithoutDetaching([
            $event->id => [
                'admin_approval_status' => 'approved',
                'admin_approved_at' => now(),
                'tutor_approval_status' => 'approved',
                'tutor_approved_at' => now(),
                'approval_notes' => 'Auto-approved: bukti follow IG dan checklist join WAG sudah lengkap.',
            ],
        ]);

        Evidence::query()
            ->where('learning_event_id', $event->id)
            ->where('uploaded_by', $this->user_id)
            ->where('type', 'follow_ig')
            ->where('status', '!=', 'approved')
            ->update([
                'status' => 'approved',
                'verified_at' => now(),
                'review_notes' => 'Auto-approved dari kelengkapan awal peserta.',
            ]);

        return true;
    }

    public function micrositeForEvent(?LearningEvent $event): ?MicrositePractice
    {
        if (! $event) {
            return null;
        }

        return $this->micrositePractices()
            ->where('learning_event_id', $event->id)
            ->latest()
            ->first();
    }

}
