<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\AssessmentAttempt;
use App\Models\EventParticipant;
use App\Models\LearningEvent;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Tutor: ringkasan nilai pre/post-test, peningkatan, ranking, dan progress tiap peserta di event yang ditugaskan. */
class TutorScores extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Nilai & Progress';

    protected static ?string $title = 'Nilai & Progress Peserta';

    protected static ?string $slug = 'nilai-progress';

    protected static string $view = 'filament.pages.tutor-scores';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    /** Cache per request agar ringkasan, ranking, dan tabel tidak menghitung ulang. */
    protected array $memo = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Tutor;
    }

    public function mount(): void
    {
        if (! $this->events->firstWhere('id', (int) $this->selectedEventId)) {
            $this->selectedEventId = $this->events->first()?->id;
        }
    }

    public function updatedSelectedEventId(): void
    {
        $this->memo = [];
        $this->resetTable();
    }

    public function getEventsProperty(): EloquentCollection
    {
        $tutorId = auth()->user()?->tutor?->id;

        return $this->memo['events'] ??= $tutorId
            ? LearningEvent::query()->whereHas('tutors', fn ($query) => $query->where('tutors.id', $tutorId))->orderByDesc('starts_at')->get()
            : new EloquentCollection();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    /** Jumlah kuis modul di event (bagian dari langkah progress). */
    public function quizTotal(): int
    {
        return $this->memo['quizTotal'] ??= $this->selectedEvent
            ? $this->selectedEvent->assessments()->where('type', 'quiz')->count()
            : 0;
    }

    protected function rowsQuery(): Builder
    {
        $lep = 'learning_event_participant';
        $score = fn (string $type) => DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->whereColumn('assessments.learning_event_id', "{$lep}.learning_event_id")
            ->whereColumn('assessment_attempts.participant_id', "{$lep}.participant_id")
            ->where('assessments.type', $type)
            ->selectRaw('max(assessment_attempts.score)');
        $related = fn (string $table) => DB::table($table)
            ->whereColumn("{$table}.learning_event_id", "{$lep}.learning_event_id")
            ->whereColumn("{$table}.participant_id", "{$lep}.participant_id");

        return EventParticipant::query()
            ->with('participant.user')
            ->where("{$lep}.learning_event_id", $this->selectedEvent?->id ?? 0)
            ->select("{$lep}.*")
            ->selectSub($score('pre'), 'pre_score')
            ->selectSub($score('post'), 'post_score')
            ->selectSub(DB::table('assessment_attempts')
                ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
                ->whereColumn('assessments.learning_event_id', "{$lep}.learning_event_id")
                ->whereColumn('assessment_attempts.participant_id', "{$lep}.participant_id")
                ->where('assessments.type', 'quiz')
                ->selectRaw('count(distinct assessments.id)'), 'quiz_done')
            ->selectSub($related('attendances')->where('status', 'hadir')->selectRaw('count(*) > 0'), 'attended')
            ->selectSub($related('microsite_practices')->whereNotNull('sid_url')->selectRaw('max(sid_url)'), 'microsite_url')
            ->selectSub($related('certificates')->selectRaw('max(status)'), 'certificate_status');
    }

    /** Semua baris event terpilih (untuk ringkasan, ranking, dan CSV). */
    public function allRows(): Collection
    {
        return $this->memo['rows'] ??= $this->rowsQuery()->get();
    }

    /** Peringkat: post-test tertinggi lalu pre-test; peserta tanpa post-test tidak diranking. */
    public function rankFor(EventParticipant $record): ?int
    {
        $this->memo['ranks'] ??= $this->allRows()
            ->whereNotNull('post_score')
            ->sortBy([['post_score', 'desc'], ['pre_score', 'desc']])
            ->values()
            ->mapWithKeys(fn (EventParticipant $row, int $index) => [$row->participant_id => $index + 1])
            ->all();

        return $this->memo['ranks'][$record->participant_id] ?? null;
    }

    /** @return array{done: int, total: int, percent: int} */
    public function progressFor(EventParticipant $record): array
    {
        $total = 3 + $this->quizTotal();
        $done = ($record->pre_score !== null ? 1 : 0)
            + min((int) $record->quiz_done, $this->quizTotal())
            + ($record->post_score !== null ? 1 : 0)
            + (filled($record->microsite_url) ? 1 : 0);

        return ['done' => $done, 'total' => $total, 'percent' => (int) round($done / $total * 100)];
    }

    public function getSummaryProperty(): array
    {
        $rows = $this->allRows();
        $pre = $rows->whereNotNull('pre_score');
        $post = $rows->whereNotNull('post_score');
        $both = $rows->filter(fn ($row) => $row->pre_score !== null && $row->post_score !== null);
        $avg = fn (Collection $items, string $key) => $items->isEmpty() ? null : round((float) $items->avg($key), 1);

        return [
            'participants' => $rows->count(),
            'pre_done' => $pre->count(),
            'post_done' => $post->count(),
            'pre_avg' => $avg($pre, 'pre_score'),
            'post_avg' => $avg($post, 'post_score'),
            'gain' => $both->isEmpty() ? null : round((float) $both->avg(fn ($row) => $row->post_score - $row->pre_score), 1),
            'improved' => $both->filter(fn ($row) => $row->post_score > $row->pre_score)->count(),
            'compared' => $both->count(),
            'attended' => $rows->where('attended', 1)->count(),
            'microsite' => $rows->filter(fn ($row) => filled($row->microsite_url))->count(),
            'certificates' => $rows->where('certificate_status', 'issued')->count(),
            'bands' => [
                ['label' => '80 - 100', 'count' => $post->where('post_score', '>=', 80)->count(), 'color' => 'rgb(22, 163, 74)'],
                ['label' => '60 - 79', 'count' => $post->filter(fn ($row) => $row->post_score >= 60 && $row->post_score < 80)->count(), 'color' => 'rgb(234, 179, 8)'],
                ['label' => '< 60', 'count' => $post->where('post_score', '<', 60)->count(), 'color' => 'rgb(239, 68, 68)'],
            ],
        ];
    }

    public static function formatScore($value): string
    {
        return $value === null ? '-' : rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');
    }

    public function table(Table $table): Table
    {
        $lep = 'learning_event_participant';
        $exists = fn (string $table, ?callable $extra = null) => function ($query) use ($table, $extra, $lep): void {
            $query->from($table)
                ->whereColumn("{$table}.learning_event_id", "{$lep}.learning_event_id")
                ->whereColumn("{$table}.participant_id", "{$lep}.participant_id");
            $extra && $extra($query);
        };
        $attempt = fn (string $type) => function ($query) use ($type, $lep): void {
            $query->from('assessment_attempts')
                ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
                ->whereColumn('assessments.learning_event_id', "{$lep}.learning_event_id")
                ->whereColumn('assessment_attempts.participant_id', "{$lep}.participant_id")
                ->where('assessments.type', $type);
        };
        $microsite = $exists('microsite_practices', fn ($query) => $query->whereNotNull('sid_url'));
        $scoreColor = fn ($state) => $state === null ? 'gray' : ($state >= 80 ? 'success' : ($state >= 60 ? 'warning' : 'danger'));

        return $table
            ->query($this->rowsQuery())
            // Sama dengan aturan ranking: post-test tertinggi, lalu pre-test; yang belum post-test di bawah.
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('post_score is null')
                ->orderByDesc('post_score')
                ->orderByDesc('pre_score'))
            ->searchPlaceholder('Cari nama atau email peserta')
            ->columns([
                Tables\Columns\TextColumn::make('rank')->label('#')
                    ->state(fn (EventParticipant $record) => $this->rankFor($record))
                    ->formatStateUsing(fn ($state) => match ((int) $state) { 1 => '🥇 1', 2 => '🥈 2', 3 => '🥉 3', default => (string) $state })
                    ->placeholder('-')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('participant.user.name')->label('Peserta')
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('participant.user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                    ->description(fn (EventParticipant $record) => $record->participant?->user?->email)
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('progress')->label('Progress')
                    ->state(fn (EventParticipant $record) => $this->progressFor($record)['percent'])
                    ->formatStateUsing(function (EventParticipant $record) {
                        $progress = $this->progressFor($record);
                        $color = $progress['percent'] >= 100 ? 'rgb(22, 163, 74)' : ($progress['percent'] >= 50 ? 'rgb(234, 179, 8)' : 'rgb(148, 163, 184)');

                        return new HtmlString('<div style="min-width: 130px;"><div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px;"><span>' . $progress['percent'] . '%</span><span style="opacity:.6;">' . $progress['done'] . '/' . $progress['total'] . ' langkah</span></div>'
                            . '<div style="background:rgba(148,163,184,.25);border-radius:999px;height:8px;overflow:hidden;"><div style="background:' . $color . ';height:100%;width:' . $progress['percent'] . '%;"></div></div></div>');
                    }),
                Tables\Columns\TextColumn::make('pre_score')->label('Pre-Test')->sortable()->badge()
                    ->formatStateUsing(fn ($state) => static::formatScore($state))->placeholder('Belum')->color($scoreColor),
                Tables\Columns\TextColumn::make('post_score')->label('Post-Test')->sortable()->badge()
                    ->formatStateUsing(fn ($state) => static::formatScore($state))->placeholder('Belum')->color($scoreColor),
                Tables\Columns\TextColumn::make('gain')->label('Peningkatan')
                    ->state(fn (EventParticipant $record) => $record->pre_score !== null && $record->post_score !== null ? $record->post_score - $record->pre_score : null)
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '▲ +' : ($state < 0 ? '▼ ' : '= ')) . static::formatScore($state))
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->placeholder('-')
                    ->weight('bold'),
                Tables\Columns\IconColumn::make('attended')->label('Hadir')->boolean(),
                Tables\Columns\IconColumn::make('microsite_url')->label('Microsite')
                    ->state(fn (EventParticipant $record) => filled($record->microsite_url))->boolean()
                    ->url(fn (EventParticipant $record) => $record->microsite_url ?: null, true)
                    ->tooltip(fn (EventParticipant $record) => $record->microsite_url ?: 'Belum mengisi link microsite'),
                Tables\Columns\TextColumn::make('certificate_status')->label('Sertifikat')->badge()
                    ->formatStateUsing(fn ($state) => match ($state) { 'issued' => 'Terbit', 'revoked' => 'Dicabut', default => 'Belum' })
                    ->color(fn ($state) => match ($state) { 'issued' => 'success', 'revoked' => 'danger', default => 'gray' })
                    ->placeholder('Belum'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status peserta')
                    ->options([
                        'no_pre' => 'Belum pre-test',
                        'no_post' => 'Belum post-test',
                        'no_microsite' => 'Belum isi microsite',
                        'complete' => 'Semua selesai',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'no_pre' => $query->whereNotExists($attempt('pre')),
                        'no_post' => $query->whereNotExists($attempt('post')),
                        'no_microsite' => $query->whereNotExists($microsite),
                        'complete' => $query->whereExists($attempt('pre'))->whereExists($attempt('post'))->whereExists($microsite),
                        default => $query,
                    }),
                Tables\Filters\TernaryFilter::make('attended')->label('Kehadiran')
                    ->trueLabel('Hadir')->falseLabel('Belum hadir')
                    ->queries(
                        true: fn (Builder $query) => $query->whereExists($exists('attendances', fn ($q) => $q->where('status', 'hadir'))),
                        false: fn (Builder $query) => $query->whereNotExists($exists('attendances', fn ($q) => $q->where('status', 'hadir'))),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (EventParticipant $record) => 'Riwayat tes - ' . $record->participant?->user?->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (EventParticipant $record) => view('filament.pages.partials.tutor-score-detail', [
                        'attempts' => AssessmentAttempt::query()
                            ->with('assessment')
                            ->where('participant_id', $record->participant_id)
                            ->whereHas('assessment', fn ($query) => $query->where('learning_event_id', $record->learning_event_id))
                            ->get()
                            ->sortBy(fn (AssessmentAttempt $attempt) => [match ($attempt->assessment?->type) { 'pre' => 0, 'quiz' => 1, default => 2 }, $attempt->submitted_at]),
                        'record' => $record,
                    ])),
            ])
            ->emptyStateHeading('Belum ada peserta')
            ->emptyStateDescription('Peserta yang mendaftar di event ini akan muncul di sini beserta nilainya.')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    /** Unduh rekap nilai event terpilih sebagai CSV (bisa dibuka di Excel). */
    public function exportCsv(): ?StreamedResponse
    {
        $event = $this->selectedEvent;

        if (! $event) {
            return null;
        }

        $rows = $this->allRows()->sortBy(fn ($row) => $this->rankFor($row) ?? PHP_INT_MAX);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Rank', 'Nama', 'Email', 'Pre-Test', 'Post-Test', 'Peningkatan', 'Progress (%)', 'Hadir', 'Microsite', 'Sertifikat'], ';');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $this->rankFor($row) ?? '-',
                    $row->participant?->user?->name,
                    $row->participant?->user?->email,
                    static::formatScore($row->pre_score),
                    static::formatScore($row->post_score),
                    $row->pre_score !== null && $row->post_score !== null ? static::formatScore($row->post_score - $row->pre_score) : '-',
                    $this->progressFor($row)['percent'],
                    $row->attended ? 'Ya' : 'Belum',
                    $row->microsite_url ?: '-',
                    $row->certificate_status === 'issued' ? 'Terbit' : 'Belum',
                ], ';');
            }

            fclose($out);
        }, 'nilai-' . $event->slug . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
