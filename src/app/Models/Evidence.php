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
        'foto_sesi' => 'Foto kegiatan per sesi',
        'follow_ig' => 'Follow IG @isoc.id.jkt',
        'join_wag' => 'Join WAG Mentoring',
        'foto_wajib' => 'Foto wajib',
        'video_slogan' => 'Video slogan ISOC',
        'praktik_microsite' => 'Praktik microsite s.id',
        'modul_tersampaikan' => 'Bukti 6 modul tersampaikan',
        'rab_final' => 'RAB final',
        'kuitansi_invoice' => 'Kuitansi / invoice',
        'bukti_logistik' => 'Bukti pembelian logistik',
        'laporan_akhir' => 'Laporan akhir',
    ];

    protected function casts(): array { return ['verified_at' => 'datetime']; }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

}
