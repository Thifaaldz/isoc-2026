<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterial extends Model
{
    protected $guarded = [];

    public const TYPES = [
        'pdf' => 'PDF',
        'ppt' => 'PPT',
        'video' => 'Video',
        'link' => 'Link',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(LearningMeeting::class, 'learning_meeting_id');
    }
}
