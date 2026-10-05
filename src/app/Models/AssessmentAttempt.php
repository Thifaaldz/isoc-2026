<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $guarded = [];

    /** Jenis tes yang mengisi indikator laporan (identifikasi ancaman & self-efficacy). */
    public const INDICATOR_TYPES = ['pre', 'post'];

    /**
     * Indikator laporan dihitung otomatis dari skor pre-test/post-test:
     * identifikasi ancaman digital (%) dan self-efficacy (0-100) = skor tes (pre = sebelum, post = setelah pelatihan).
     * Nilai yang diisi manual oleh admin tidak ditimpa.
     */
    protected static function booted(): void
    {
        static::saving(function (AssessmentAttempt $attempt): void {
            if ($attempt->score === null) {
                return;
            }

            $type = $attempt->relationLoaded('assessment')
                ? $attempt->assessment?->type
                : Assessment::query()->whereKey($attempt->assessment_id)->value('type');

            if (! in_array($type, self::INDICATOR_TYPES, true)) {
                return;
            }

            $attempt->threat_identification ??= $attempt->score;
            $attempt->self_efficacy ??= $attempt->score;
        });
    }

    protected function casts(): array { return ['answers' => 'array', 'submitted_at' => 'datetime']; }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function participant(): BelongsTo { return $this->belongsTo(Participant::class); }
    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

}
