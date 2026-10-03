<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    /** Background bawaan (relatif ke folder public) bila template belum punya background sendiri. */
    public const DEFAULT_BACKGROUND = 'certificate-templates/esertifikat-litdig-2026.png';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'elements' => 'array',
            'is_default' => 'boolean',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
        ];
    }

    public function learningEvent(): BelongsTo
    {
        return $this->belongsTo(LearningEvent::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
