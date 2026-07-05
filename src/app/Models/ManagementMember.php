<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementMember extends Model
{
    protected $fillable = [
        'management_group_id',
        'name',
        'position',
        'photo_url',
        'photo_path',
        'email',
        'linkedin_url',
        'bio',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ManagementGroup::class, 'management_group_id');
    }

    public function getPhotoSrcAttribute(): ?string
    {
        if ($this->photo_path) {
            return asset('storage/' . $this->photo_path);
        }

        return $this->photo_url;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
