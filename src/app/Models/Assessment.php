<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['is_open' => 'boolean']; }
    public function attempts(): HasMany { return $this->hasMany(AssessmentAttempt::class); }

}
