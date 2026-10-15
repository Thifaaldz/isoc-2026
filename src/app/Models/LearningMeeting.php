<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMeeting extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        // Pertemuan bisa ditambah/dihapus langsung dari menu Pertemuan & Materi; jumlah pertemuan materi ikut menyesuaikan.
        $syncCount = function (LearningMeeting $meeting): void {
            if ($meeting->learning_event_id !== null || ! $meeting->module_template_id) {
                return;
            }

            ModuleTemplate::query()->whereKey($meeting->module_template_id)->update([
                'meeting_count' => static::query()->where('module_template_id', $meeting->module_template_id)->whereNull('learning_event_id')->count(),
            ]);
        };

        static::created($syncCount);
        static::deleted($syncCount);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'is_published' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(LearningEvent::class, 'learning_event_id');
    }

    public function moduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ModuleTemplate::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
