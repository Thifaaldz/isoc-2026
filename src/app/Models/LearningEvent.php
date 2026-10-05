<?php

namespace App\Models;

use App\Services\SystemProofGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningEvent extends Model
{
    protected $guarded = [];

    /** Jumlah modul (pertemuan) dari Materi Event yang dibawakan dalam satu event. */
    public const MODULES_PER_EVENT = 6;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'local_updated_at' => 'datetime',
            'publish_approved_at' => 'datetime',
            'final_report_submitted_at' => 'datetime',
            'final_report_approved_at' => 'datetime',
            'registration_open' => 'boolean',
            'is_published' => 'boolean',
            'participant_rows' => 'array',
            'tutor_rows' => 'array',
            'selected_tutor_ids' => 'array',
            'selected_meeting_ids' => 'array',
            'budget_items' => 'array',
            'rundown_items' => 'array',
            'evidence_checklist' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function moduleTemplate(): BelongsTo
    {
        return $this->belongsTo(ModuleTemplate::class);
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Participant::class)
            ->withPivot([
                'status',
                'registered_at',
                'admin_approval_status',
                'admin_approved_by',
                'admin_approved_at',
                'tutor_approval_status',
                'tutor_approved_by',
                'tutor_approved_at',
                'approval_notes',
            ])
            ->withTimestamps();
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class, 'learning_event_tutor')
            ->withPivot(['status', 'assigned_at'])
            ->withTimestamps();
    }

    public function partners(): BelongsToMany
    {
        // Relasi polos untuk form/sync. Jangan beri order kolom pivot di sini: Select Filament memakai
        // LEFT JOIN ke pivot sehingga opsi mitra menjadi duplikat.
        return $this->belongsToMany(Partner::class)
            ->withPivot(['sort_order', 'show_on_certificate'])
            ->withTimestamps();
    }

    /** Mitra urut sesuai sort_order, lalu urutan dipilih di form; dipakai untuk logo (sertifikat, katalog). */
    public function orderedPartners(): BelongsToMany
    {
        return $this->partners()
            ->orderByPivot('sort_order')
            ->orderBy('learning_event_partner.id');
    }

    /** Mitra yang logonya dicentang untuk tampil di sertifikat. */
    public function certificatePartners(): BelongsToMany
    {
        return $this->orderedPartners()->wherePivot('show_on_certificate', true);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(LearningMeeting::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function certificateTemplates(): HasMany
    {
        return $this->hasMany(CertificateTemplate::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    public function totAssessments(): HasMany
    {
        return $this->hasMany(TotAssessment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Peserta yang sudah absen hadir lewat kode absensi (dipakai bila bukti absensi "generate sistem"). */
    public function checkedInAttendances()
    {
        return $this->attendances()
            ->with('participant.user')
            ->whereNotNull('participant_id')
            ->where('status', 'hadir')
            ->orderBy('checked_in_at');
    }

    /** Mode bukti laporan final ("system" / "manual" / null) untuk absensi atau microsite. */
    public function proofMode(string $kind): ?string
    {
        return $this->getAttribute(SystemProofGenerator::KINDS[$kind]['column']);
    }

    /** Syarat bukti laporan final: generate sistem = PDF sistem sudah tersimpan; manual = bukti upload/tautan disetujui. */
    public function proofComplete(string $kind): bool
    {
        $proofs = $this->evidences()->where('type', SystemProofGenerator::KINDS[$kind]['type'])->where('status', 'approved');
        $systemPath = SystemProofGenerator::path($kind, $this);

        // Bukti manual bisa berupa link saja (file_path kosong), jadi NULL harus ikut dihitung.
        return $this->proofMode($kind) === 'system'
            ? $proofs->where('file_path', $systemPath)->exists()
            : $proofs->where(fn ($query) => $query->whereNull('file_path')->orWhere('file_path', '!=', $systemPath))->exists();
    }

    public function attendanceProofComplete(): bool
    {
        return $this->proofComplete('attendance');
    }
}
