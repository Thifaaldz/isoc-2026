<?php

namespace App\Filament\Manajemen\Tables;

use App\Models\Certificate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\Tutor;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tabel data mentah untuk panel Manajemen: hanya baca (tanpa aksi ubah/hapus), bisa dicari, difilter, diurutkan.
 * Data sensitif (NIK, NPWP, rekening) sengaja tidak ditampilkan.
 * $eventId diisi saat tabel dipakai di halaman Detail Lokasi (data satu event).
 */
class ManagementTables
{
    public static function eventOptions(): array
    {
        return LearningEvent::query()->orderBy('title')->pluck('title', 'id')
            ->map(fn (string $title) => str($title)->replace('Digital Safety Champions - ', '')->toString())
            ->all();
    }

    private static function shortTitle(?string $title): string
    {
        return str($title ?? '-')->replace('Digital Safety Champions - ', '')->toString();
    }

    private static function eventFilter(string $relation): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('lokasi')
            ->label('Lokasi')
            ->options(fn () => static::eventOptions())
            ->searchable()
            ->query(fn (Builder $query, array $data) => $data['value']
                ? ($relation === '' ? $query->where('learning_event_id', $data['value']) : $query->whereHas($relation, fn (Builder $q) => $q->whereKey($data['value'])))
                : $query);
    }

    /** Peserta beserta kehadiran, nilai, microsite, follow IG, join WAG, dan sertifikat. */
    public static function participants(Table $table, ?int $eventId = null): Table
    {
        $eventClause = fn (string $col) => $eventId ? " AND {$col} = " . (int) $eventId : '';
        $score = fn (string $type) => DB::raw("(SELECT MAX(aa.score) FROM assessment_attempts aa JOIN assessments a ON a.id = aa.assessment_id WHERE aa.participant_id = participants.id AND a.type = '{$type}'" . $eventClause('a.learning_event_id') . ") AS {$type}_score");

        return $table
            ->query(Participant::query()
                ->with(['user', 'school', 'learningEvents'])
                ->when($eventId, fn (Builder $q) => $q->whereHas('learningEvents', fn (Builder $e) => $e->whereKey($eventId)))
                ->select('participants.*')
                ->addSelect($score('pre'), $score('post'))
                ->selectRaw("EXISTS(SELECT 1 FROM attendances t WHERE t.participant_id = participants.id AND t.status = 'hadir'" . $eventClause('t.learning_event_id') . ') AS attended')
                ->selectRaw("EXISTS(SELECT 1 FROM microsite_practices m WHERE m.participant_id = participants.id AND m.sid_url IS NOT NULL AND m.sid_url <> ''" . $eventClause('m.learning_event_id') . ') AS has_microsite')
                ->selectRaw("EXISTS(SELECT 1 FROM certificates c WHERE c.participant_id = participants.id AND c.status = 'issued'" . $eventClause('c.learning_event_id') . ') AS has_certificate'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->description(fn (Participant $r) => $r->user?->email)
                    ->searchable(['name', 'email'])->sortable()->wrap(),
                Tables\Columns\TextColumn::make('learningEvents.title')->label('Lokasi')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => static::shortTitle($state))->hidden((bool) $eventId),
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->placeholder('-')->toggleable()->wrap(),
                Tables\Columns\TextColumn::make('grade')->label('Kelas')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('gender')->label('JK')->formatStateUsing(fn ($state) => match ($state) { 'L' => 'L', 'P' => 'P', default => '-' })->toggleable(),
                Tables\Columns\IconColumn::make('attended')->label('Hadir')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('pre_score')->label('Pre')->numeric(1)->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('post_score')->label('Post')->numeric(1)->placeholder('-')->sortable()
                    ->color(fn ($state) => $state === null ? null : ($state >= 70 ? 'success' : 'danger')),
                Tables\Columns\IconColumn::make('has_microsite')->label('Microsite')->boolean(),
                Tables\Columns\IconColumn::make('followed_instagram')->label('IG')->boolean(),
                Tables\Columns\IconColumn::make('joined_wag')->label('WAG')->boolean(),
                Tables\Columns\IconColumn::make('has_certificate')->label('Sertifikat')->boolean(),
            ])
            ->filters(array_values(array_filter([
                $eventId ? null : static::eventFilter('learningEvents'),
                Tables\Filters\TernaryFilter::make('attended')->label('Hadir')
                    ->queries(true: fn (Builder $q) => $q->having('attended', 1), false: fn (Builder $q) => $q->having('attended', 0)),
                Tables\Filters\TernaryFilter::make('has_certificate')->label('Sertifikat terbit')
                    ->queries(true: fn (Builder $q) => $q->having('has_certificate', 1), false: fn (Builder $q) => $q->having('has_certificate', 0)),
                Tables\Filters\SelectFilter::make('gender')->label('Jenis kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan']),
            ])))
            ->defaultSort('user.name')
            ->emptyStateHeading('Belum ada peserta')
            ->paginated([10, 25, 50, 100]);
    }

    /** Tutor yang ditugaskan beserta lokasi, lembaga, dan nilai ToT. */
    public static function tutors(Table $table, ?int $eventId = null): Table
    {
        return $table
            ->query(Tutor::query()
                ->with(['user', 'learningEvents'])
                ->whereHas('learningEvents', fn (Builder $q) => $eventId ? $q->whereKey($eventId) : $q)
                ->withMax(['totAssessments as tot_score' => fn (Builder $q) => $eventId ? $q->where('learning_event_id', $eventId) : $q], 'score'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Nama')->description(fn (Tutor $r) => $r->user?->email)
                    ->searchable(['name', 'email'])->sortable(),
                Tables\Columns\TextColumn::make('learningEvents.title')->label('Lokasi')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => static::shortTitle($state))->hidden((bool) $eventId),
                Tables\Columns\TextColumn::make('institution')->label('Lembaga')->placeholder('-')->searchable()->wrap(),
                Tables\Columns\IconColumn::make('tot_completed')->label('Lulus ToT')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('tot_score')->label('Nilai ToT')->numeric(0)->placeholder('-')->sortable()
                    ->color(fn ($state) => $state === null ? null : ((float) $state >= 100 ? 'success' : 'warning')),
                Tables\Columns\IconColumn::make('profile_complete')->label('Profil honor lengkap')->boolean()
                    ->state(fn (Tutor $r) => filled($r->nik) && filled($r->npwp) && filled($r->bank_account_number))
                    ->tooltip('NIK, NPWP, dan rekening sudah diisi (nilainya tidak ditampilkan)'),
            ])
            ->filters(array_values(array_filter([
                $eventId ? null : static::eventFilter('learningEvents'),
                Tables\Filters\TernaryFilter::make('tot_completed')->label('Lulus ToT'),
            ])))
            ->emptyStateHeading('Belum ada tutor')
            ->paginated([10, 25, 50, 100]);
    }

    /** Semua berkas bukti dukung beserta status verifikasinya. */
    public static function evidence(Table $table, ?int $eventId = null): Table
    {
        return $table
            ->query(Evidence::query()
                ->with(['learningEvent', 'uploader'])
                ->whereNotIn('type', Evidence::LEGACY_TYPES)
                ->when($eventId, fn (Builder $q) => $q->where('learning_event_id', $eventId)))
            ->columns([
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Lokasi')->formatStateUsing(fn ($state) => static::shortTitle($state))
                    ->sortable()->hidden((bool) $eventId),
                Tables\Columns\TextColumn::make('type')->label('Jenis bukti')->formatStateUsing(fn ($state) => Evidence::TYPES[$state] ?? $state)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) { 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'revision' => 'Perlu revisi', default => 'Menunggu' })
                    ->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', 'revision' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('uploader.name')->label('Diunggah oleh')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Diunggah')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('verified_at')->label('Diverifikasi')->dateTime('d M Y H:i')->placeholder('-')->toggleable(),
                Tables\Columns\TextColumn::make('review_notes')->label('Catatan')->placeholder('-')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('berkas')->label('Berkas')
                    ->state(fn (Evidence $r) => ($r->fileUrl() || $r->link) ? 'Lihat' : null)
                    ->url(fn (Evidence $r) => $r->fileUrl() ?: $r->link, shouldOpenInNewTab: true)
                    ->icon('heroicon-m-arrow-top-right-on-square')->color('primary')->placeholder('-'),
            ])
            ->filters(array_values(array_filter([
                $eventId ? null : static::eventFilter(''),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'revision' => 'Perlu revisi']),
                Tables\Filters\SelectFilter::make('type')->label('Jenis bukti')->options(collect(Evidence::TYPES)->except(Evidence::LEGACY_TYPES)->all()),
            ])))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada bukti dukung')
            ->paginated([10, 25, 50, 100]);
    }

    /** Sertifikat peserta yang sudah dibuat sistem. */
    public static function certificates(Table $table, ?int $eventId = null): Table
    {
        return $table
            ->query(Certificate::query()
                ->with(['participant.user', 'learningEvent'])
                ->when($eventId, fn (Builder $q) => $q->where('learning_event_id', $eventId)))
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Nomor')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')->searchable(),
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Lokasi')->formatStateUsing(fn ($state) => static::shortTitle($state))->hidden((bool) $eventId),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) { 'issued' => 'Terbit', 'revoked' => 'Dicabut', default => 'Menunggu' })
                    ->color(fn ($state) => match ($state) { 'issued' => 'success', 'revoked' => 'danger', default => 'gray' }),
                Tables\Columns\TextColumn::make('eligibility_notes')->label('Catatan syarat')->placeholder('-')->limit(50)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')->label('Terbit')->dateTime('d M Y')->placeholder('-')->sortable(),
            ])
            ->filters(array_values(array_filter([
                $eventId ? null : static::eventFilter(''),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(['issued' => 'Terbit', 'pending' => 'Menunggu', 'revoked' => 'Dicabut']),
            ])))
            ->defaultSort('issued_at', 'desc')
            ->emptyStateHeading('Belum ada sertifikat')
            ->paginated([10, 25, 50, 100]);
    }
}
