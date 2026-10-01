<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $guarded = [];

    protected function casts(): array { return ['is_open' => 'boolean', 'questions' => 'array']; }
    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }
    public function meeting(): BelongsTo { return $this->belongsTo(LearningMeeting::class, 'learning_meeting_id'); }
    public function moduleTemplate(): BelongsTo { return $this->belongsTo(ModuleTemplate::class); }
    public function attempts(): HasMany { return $this->hasMany(AssessmentAttempt::class); }

}
