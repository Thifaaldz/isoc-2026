<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $guarded = [];

    public function tutors(): HasMany { return $this->hasMany(Tutor::class); }
    public function participants(): HasMany { return $this->hasMany(Participant::class); }
    public function evidences(): HasMany { return $this->hasMany(Evidence::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function wagGroups(): HasMany { return $this->hasMany(WagGroup::class); }

}
