<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TotAssessment extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (TotAssessment $assessment): void {
            $assessment->is_perfect = (int) $assessment->score === 100;
            $assessment->completed_at ??= now();
        });

        static::saved(function (TotAssessment $assessment): void {
            if ($assessment->is_perfect) {
                $assessment->tutor?->update(['tot_completed' => true]);
            }

            $event = $assessment->learningEvent()->with(['tutors', 'totAssessments'])->first();

            if (! $event || $event->tutors->isEmpty()) {
                return;
            }

            $perfectTutorIds = $event->totAssessments
                ->where('is_perfect', true)
                ->pluck('tutor_id')
                ->unique();

            // Hanya majukan status dari tahap awal; jangan mundurkan event yang sudah lebih jauh
            // (mis. laporan final terkirim) ketika tutor menyimpan ulang ToT.
            if ($perfectTutorIds->count() >= $event->tutors->count()
                && in_array($event->workflow_status, ['verified_term_1', 'tot_in_progress'], true)) {
                $event->update(['workflow_status' => 'tot_completed']);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_perfect' => 'boolean',
        ];
    }

    public function learningEvent(): BelongsTo
    {
        return $this->belongsTo(LearningEvent::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
