<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pendaftaran tutor ke event lewat form publik, disimpan di tabel pivot learning_event_tutor (tanpa tabel baru).
 * Status "pending" menunggu validasi Admin RTIK Pusat; "rejected" ditolak. Keduanya tidak dihitung sebagai tutor event.
 * Setelah disetujui statusnya menjadi "assigned" seperti tutor yang ditugaskan dari wizard.
 */
class TutorRegistration extends Model
{
    public const PENDING = 'pending';

    public const REJECTED = 'rejected';

    public const ASSIGNED = 'assigned';

    /** Status pivot yang belum menjadi tutor event. */
    public const UNASSIGNED = [self::PENDING, self::REJECTED];

    public const STATUSES = [
        self::PENDING => 'Menunggu validasi',
        self::ASSIGNED => 'Disetujui',
        self::REJECTED => 'Ditolak',
    ];

    protected $table = 'learning_event_tutor';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function tutor(): BelongsTo { return $this->belongsTo(Tutor::class); }

    public function learningEvent(): BelongsTo { return $this->belongsTo(LearningEvent::class); }

    /** Jadikan pendaftaran tutor (pending/ditolak) sebagai tutor event; false bila belum ada pendaftaran. */
    public static function promote(int $eventId, int $tutorId): bool
    {
        return (bool) static::query()
            ->where('learning_event_id', $eventId)
            ->where('tutor_id', $tutorId)
            ->whereIn('status', self::UNASSIGNED)
            ->update(['status' => self::ASSIGNED, 'assigned_at' => now(), 'updated_at' => now()]);
    }
}
