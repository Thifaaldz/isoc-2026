<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['paid_at' => 'date', 'amount' => 'decimal:2']; }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }

}
