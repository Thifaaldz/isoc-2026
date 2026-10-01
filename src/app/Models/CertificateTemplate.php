<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
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
