<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Support\MarkAttendanceActions;
use App\Models\EventParticipant;
use App\Models\LearningEvent;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Rekap peserta per event untuk Admin RTIK Daerah dan Tutor: ranking (post-test lalu pre-test), kehadiran,
 * nilai pre/post-test, praktik microsite, join WhatsApp Group, dan follow Instagram ISOC.
 */
class ParticipantRecapTable extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    /** @var array<int, array<int, int>>|null ranking per event: [event_id => [participant_id => peringkat]] */
    protected ?array $ranks = null;

    public static function canView(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Tutor], true);
    }

    protected function isTutor(): bool
    {
        return auth()->user()?->role === UserRole::Tutor;
    }

    protected function eventQuery(): Builder
    {
        $user = auth()->user();

        return match ($user?->role) {
            UserRole::Tutor => LearningEvent::query()->whereHas('tutors', fn (Builder $query) => $query->where('tutors.id', $user->tutor?->id ?? 0)),
            UserRole::Admin => LearningEvent::query()->where('created_by', $user->id),
            default => LearningEvent::query()->whereRaw('1 = 0'),
        };
    }

    public function table(Table $table): Table
    {
        $lep = 'learning_event_participant';
        $score = fn (string $type) => DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->whereColumn('assessments.learning_event_id', "{$lep}.learning_event_id")
            ->whereColumn('assessment_attempts.participant_id', "{$lep}.participant_id")
            ->where('assessments.type', $type)
            ->selectRaw('max(assessment_attempts.score)');

        $hasAttempt = fn (string $type) => function ($query) use ($type, $lep): void {
            $query->from('assessment_attempts')
                ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
                ->whereColumn('assessments.learning_event_id', "{$lep}.learning_event_id")
                ->whereColumn('assessment_attempts.participant_id', "{$lep}.participant_id")
                ->where('assessments.type', $type);
        };

        $exists = fn (string $table, ?callable $extra = null) => function ($query) use ($table, $extra, $lep): void {
            $query->from($table)
                ->whereColumn("{$table}.learning_event_id", "{$lep}.learning_event_id")
                ->whereColumn("{$table}.participant_id", "{$lep}.participant_id");
            $extra && $extra($query);
        };
        $attendedQuery = $exists('attendances', fn ($query) => $query->where('attendances.status', 'hadir'));
        $micrositeQuery = $exists('microsite_practices', fn ($query) => $query->whereNotNull('sid_url'));

        $query = EventParticipant::query()
            ->with(['participant.user', 'learningEvent'])
            ->whereIn("{$lep}.learning_event_id", $this->eventQuery()->select('id'))
            ->join('participants', 'participants.id', '=', "{$lep}.participant_id")
            ->select("{$lep}.*", 'participants.joined_wag', 'participants.followed_instagram')
            ->selectSub($score('pre'), 'pre_score')
            ->selectSub($score('post'), 'post_score')
            ->selectSub(DB::table('attendances')
                ->whereColumn('attendances.learning_event_id', "{$lep}.learning_event_id")
                ->whereColumn('attendances.participant_id', "{$lep}.participant_id")
                ->where('attendances.status', 'hadir')
                ->selectRaw('count(*) > 0'), 'attended')
            ->selectSub(DB::table('microsite_practices')
                ->whereColumn('microsite_practices.learning_event_id', "{$lep}.learning_event_id")
                ->whereColumn('microsite_practices.participant_id', "{$lep}.participant_id")
                ->whereNotNull('sid_url')
                ->selectRaw('max(sid_url)'), 'microsite_url');

        $tutor = $this->isTutor();
        $check = fn (string $name, string $label) => Tables\Columns\IconColumn::make($name)->label($label)->boolean()->sortable();
        $hasTest = fn (string $type) => Tables\Filters\TernaryFilter::make("has_{$type}")->label($type === 'pre' ? 'Pre-Test' : 'Post-Test')
            ->trueLabel('Sudah')->falseLabel('Belum')
            ->queries(true: fn (Builder $q) => $q->whereExists($hasAttempt($type)), false: fn (Builder $q) => $q->whereNotExists($hasAttempt($type)));

        // Kolom & filter sama untuk Admin RTIK Daerah dan Tutor; semua peserta tampil (filter "lengkap" opsional).
        return $table
            ->heading($tutor ? 'Rekap & Ranking Peserta' : 'Peserta Lengkap')
            ->description('Kehadiran, nilai pre/post-test, ranking, praktik microsite, join WhatsApp Group, dan follow Instagram peserta di event Anda.')
            ->query($query)
            ->defaultSort('post_score', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('rank')->label('Rank')->badge()->color('warning')->placeholder('-')
                    ->state(fn (EventParticipant $record) => $this->rankFor($record)),
                Tables\Columns\TextColumn::make('participant.user.name')->label('Nama')->searchable()
                    ->description(fn (EventParticipant $record) => $record->participant?->user?->email),
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->toggleable(),
                $check('attended', 'Hadir'),
                Tables\Columns\TextColumn::make('pre_score')->label('Pre-Test')->numeric(1)->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('post_score')->label('Post-Test')->numeric(1)->placeholder('-')->sortable(),
                Tables\Columns\TextColumn::make('microsite_url')->label('Microsite')->placeholder('Belum')
                    ->formatStateUsing(fn () => 'Buka')->url(fn (EventParticipant $record) => $record->microsite_url, true)->color('success'),
                $check('joined_wag', 'Join WAG'),
                $check('followed_instagram', 'Follow IG'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('learning_event_id')->label('Event')
                    ->options(fn () => $this->eventQuery()->orderBy('title')->pluck('title', 'id')),
                Tables\Filters\Filter::make('complete')->label('Hanya yang lengkap (hadir, pre & post-test, microsite, WAG, IG)')
                    ->query(fn (Builder $query) => $query
                        ->whereExists($attendedQuery)
                        ->whereExists($hasAttempt('pre'))
                        ->whereExists($hasAttempt('post'))
                        ->whereExists($micrositeQuery)
                        ->where('participants.joined_wag', true)
                        ->where('participants.followed_instagram', true)),
                Tables\Filters\TernaryFilter::make('attended')->label('Hadir')
                    ->queries(true: fn (Builder $q) => $q->whereExists($attendedQuery), false: fn (Builder $q) => $q->whereNotExists($attendedQuery)),
                $hasTest('pre'),
                $hasTest('post'),
                Tables\Filters\TernaryFilter::make('microsite')->label('Praktik microsite')
                    ->queries(true: fn (Builder $q) => $q->whereExists($micrositeQuery), false: fn (Builder $q) => $q->whereNotExists($micrositeQuery)),
                Tables\Filters\TernaryFilter::make('joined_wag')->label('Join WAG')
                    ->queries(true: fn (Builder $q) => $q->where('participants.joined_wag', true), false: fn (Builder $q) => $q->where('participants.joined_wag', false)),
                Tables\Filters\TernaryFilter::make('followed_instagram')->label('Follow IG')
                    ->queries(true: fn (Builder $q) => $q->where('participants.followed_instagram', true), false: fn (Builder $q) => $q->where('participants.followed_instagram', false)),
            ])
            // Absensi massal: semua peserta satu event, atau peserta yang dicentang.
            ->headerActions([
                MarkAttendanceActions::markAll(Tables\Actions\Action::class, fn () => $this->eventQuery()->orderBy('title')->pluck('title', 'id')),
            ])
            ->bulkActions([
                MarkAttendanceActions::markSelected(fn (EventParticipant $record) => $record->learning_event_id, fn (EventParticipant $record) => $record->participant_id),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    /** Peringkat dalam event: nilai post-test tertinggi, lalu pre-test; peserta tanpa post-test tidak diranking. */
    protected function rankFor(EventParticipant $record): ?int
    {
        $this->ranks ??= DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->whereIn('assessments.learning_event_id', $this->eventQuery()->select('id'))
            ->whereIn('assessments.type', ['pre', 'post'])
            ->groupBy('assessments.learning_event_id', 'assessment_attempts.participant_id')
            ->selectRaw("assessments.learning_event_id as event_id, assessment_attempts.participant_id,
                max(case when assessments.type = 'post' then assessment_attempts.score end) as post_score,
                max(case when assessments.type = 'pre' then assessment_attempts.score end) as pre_score")
            ->get()
            ->whereNotNull('post_score')
            ->groupBy('event_id')
            ->map(fn ($rows) => $rows->sortBy([['post_score', 'desc'], ['pre_score', 'desc']])->values()
                ->mapWithKeys(fn ($row, $index) => [$row->participant_id => $index + 1])->all())
            ->all();

        return $this->ranks[$record->learning_event_id][$record->participant_id] ?? null;
    }
}
