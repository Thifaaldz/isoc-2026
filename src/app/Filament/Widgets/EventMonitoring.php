<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Pages\EventAttendanceCode;
use App\Filament\Pages\ParticipantLearning;
use App\Filament\Pages\ParticipantRecap;
use App\Filament\Resources\AssessmentAttemptResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\MicrositePracticeResource;
use App\Models\LearningEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Dashboard Admin RTIK Daerah: monitoring kehadiran, praktik microsite, pre-test, dan post-test per event. */
class EventMonitoring extends Widget
{
    protected static string $view = 'filament.widgets.event-monitoring';

    protected int | string | array $columnSpan = 'full';

    // Tepat di bawah KPI.
    protected static ?int $sort = -6;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getViewData(): array
    {
        $events = LearningEvent::query()
            ->with(['school', 'assessments'])
            ->where('created_by', auth()->id())
            ->whereNotIn('workflow_status', ['cancelled', 'rejected'])
            ->orderByDesc('starts_at')
            ->get();

        return ['events' => $events->map(fn (LearningEvent $event) => $this->monitor($event))];
    }

    private function monitor(LearningEvent $event): array
    {
        $participants = DB::table('learning_event_participant')
            ->join('participants', 'participants.id', '=', 'learning_event_participant.participant_id')
            ->join('users', 'users.id', '=', 'participants.user_id')
            ->where('learning_event_participant.learning_event_id', $event->id)
            ->orderBy('users.name')
            ->pluck('users.name', 'participants.id');
        $ids = $participants->keys();

        $attended = DB::table('attendances')
            ->where('learning_event_id', $event->id)
            ->where('status', 'hadir')
            ->whereIn('participant_id', $ids)
            ->distinct()
            ->pluck('participant_id');

        $microsite = DB::table('microsite_practices')
            ->where('learning_event_id', $event->id)
            ->whereNotNull('sid_url')
            ->where('sid_url', '!=', '')
            ->whereIn('participant_id', $ids)
            ->distinct()
            ->pluck('participant_id');

        // Nilai terbaik per peserta (post-test boleh diulang).
        $bestScores = fn (string $type) => DB::table('assessment_attempts')
            ->join('assessments', 'assessments.id', '=', 'assessment_attempts.assessment_id')
            ->where('assessments.learning_event_id', $event->id)
            ->where('assessments.type', $type)
            ->whereIn('assessment_attempts.participant_id', $ids)
            ->groupBy('assessment_attempts.participant_id')
            ->selectRaw('assessment_attempts.participant_id, max(assessment_attempts.score) as best')
            ->pluck('best', 'participant_id');

        $pre = $bestScores('pre');
        $post = $bestScores('post');
        $passing = (float) ($event->assessments->firstWhere('type', 'post')?->passing_score ?: ParticipantLearning::DEFAULT_PASSING_SCORE);
        $missing = fn (Collection $doneIds) => $participants->except($doneIds->all())->values();
        $average = fn (Collection $scores) => $scores->isEmpty() ? null : round((float) $scores->avg(), 1);

        return [
            'event' => $event,
            'total' => $participants->count(),
            'metrics' => [
                [
                    'key' => 'kehadiran',
                    'label' => 'Kehadiran Peserta',
                    'icon' => 'heroicon-o-finger-print',
                    'done' => $attended->count(),
                    'note' => null,
                    'missing' => $missing($attended),
                    'missingLabel' => 'Belum absen',
                    'url' => AttendanceResource::getUrl('index', ['event' => $event->id]),
                    'extraUrl' => ['Kode Absensi', EventAttendanceCode::getUrl(['event' => $event->id])],
                ],
                [
                    'key' => 'microsite',
                    'label' => 'Praktik Microsite',
                    'icon' => 'heroicon-o-link',
                    'done' => $microsite->count(),
                    'note' => null,
                    'missing' => $missing($microsite),
                    'missingLabel' => 'Belum menyematkan link s.id',
                    'url' => MicrositePracticeResource::getUrl('index', ['event' => $event->id]),
                    'extraUrl' => null,
                ],
                [
                    'key' => 'pre',
                    'label' => 'Pre-Test',
                    'icon' => 'heroicon-o-clipboard-document-list',
                    'done' => $pre->count(),
                    'note' => $average($pre) !== null ? 'Rata-rata ' . $this->number($average($pre)) : null,
                    'missing' => $missing($pre->keys()),
                    'missingLabel' => 'Belum pre-test',
                    'url' => AssessmentAttemptResource::getUrl('index', ['event' => $event->id]),
                    'extraUrl' => null,
                ],
                [
                    'key' => 'post',
                    'label' => 'Post-Test',
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'done' => $post->count(),
                    'note' => $average($post) !== null
                        ? 'Rata-rata ' . $this->number($average($post)) . ' · lulus ' . $post->filter(fn ($score) => (float) $score >= $passing)->count() . ' (≥ ' . $this->number($passing) . ')'
                        : null,
                    'missing' => $missing($post->keys()),
                    'missingLabel' => 'Belum post-test',
                    'url' => AssessmentAttemptResource::getUrl('index', ['event' => $event->id]),
                    'extraUrl' => null,
                ],
            ],
            'recapUrl' => ParticipantRecap::getUrl(),
        ];
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }
}
