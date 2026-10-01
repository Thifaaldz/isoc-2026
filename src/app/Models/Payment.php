<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'due_at' => 'date',
            'approved_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'checklist' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }

}
