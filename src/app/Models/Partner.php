<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    protected $guarded = [];

    public function learningEvents(): BelongsToMany
    {
        return $this->belongsToMany(LearningEvent::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function logoSource(): ?string
    {
        if (filled($this->logo_path)) {
            if (str_starts_with($this->logo_path, 'http') || str_starts_with($this->logo_path, '/')) {
                return $this->logo_path;
            }

            if (Storage::disk('public')->exists($this->logo_path)) {
                return Storage::disk('public')->url($this->logo_path);
            }

            return asset($this->logo_path);
        }

        return $this->logo_url;
    }
}
