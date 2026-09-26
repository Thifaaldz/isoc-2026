<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $guarded = [];

    public const TYPES = [
        'absensi_basah' => 'Absensi basah',
        'follow_ig' => 'Follow IG @isoc.id.jkt',
        'join_wag' => 'Join WAG Mentoring',
        'registrasi_ecert' => 'Registrasi e-Certificate',
        'foto_wajib' => 'Foto wajib',
        'video_slogan' => 'Video slogan ISOC',
    ];
    protected function casts(): array { return ['verified_at' => 'datetime']; }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }

}
