<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTemplate extends Model
{
    public const AUDIENCE_PESERTA = 'peserta';

    public const AUDIENCE_TUTOR = 'tutor';

    public const AUDIENCES = [
        self::AUDIENCE_PESERTA => 'Peserta (materi event)',
        self::AUDIENCE_TUTOR => 'Tutor (materi ToT)',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'meetings' => 'array',
            'is_active' => 'boolean',
            'meeting_count' => 'integer',
        ];
    }

    public function scopeForParticipants(Builder $query): Builder
    {
        return $query->where('audience', self::AUDIENCE_PESERTA);
    }

    public function scopeForTutors(Builder $query): Builder
    {
        return $query->where('audience', self::AUDIENCE_TUTOR);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function learningEvents(): HasMany
    {
        return $this->hasMany(LearningEvent::class);
    }

    public function learningMeetings(): HasMany
    {
        return $this->hasMany(LearningMeeting::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
