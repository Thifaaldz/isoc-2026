<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Assessment;
use App\Models\LearningEvent;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;

/** Tutor: kunci jawaban seluruh bank soal pre-test dan post-test event yang ditugaskan (peserta mendapat soal acak dari bank ini). */
class AnswerKey extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Kunci Jawaban';

    protected static ?string $title = 'Kunci Jawaban Pre-Test & Post-Test';

    protected static ?string $slug = 'kunci-jawaban';

    protected static string $view = 'filament.pages.answer-key';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

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

    public function getEventsProperty(): EloquentCollection
    {
        $tutorId = auth()->user()?->tutor?->id;

        return $tutorId
            ? LearningEvent::query()
                ->whereHas('tutors', fn ($query) => $query->where('tutors.id', $tutorId))
                ->orderByDesc('starts_at')
                ->get()
            : new EloquentCollection();
    }

    /** @return EloquentCollection<int, Assessment> */
    public function getAssessmentsProperty(): EloquentCollection
    {
        if (! $this->events->firstWhere('id', (int) $this->selectedEventId)) {
            return new EloquentCollection();
        }

        return Assessment::query()
            ->where('learning_event_id', $this->selectedEventId)
            ->whereIn('type', ['pre', 'post'])
            ->orderByRaw("case type when 'pre' then 0 else 1 end")
            ->get();
    }

    /**
     * Soal beserta kunci jawabannya (huruf mengikuti urutan bank soal; di layar peserta urutan soal & opsi diacak).
     *
     * @return array<int, array{question: string, options: array<int, array{letter: string, text: string, is_correct: bool}>, answers: array<int, array{letter: string, text: string}>, search: string}>
     */
    public function questionsFor(Assessment $assessment): array
    {
        $questions = is_array($assessment->questions) ? $assessment->questions : (json_decode((string) $assessment->questions, true) ?? []);

        return collect($questions)
            ->map(function (array $question): array {
                $options = collect($question['options'] ?? [])
                    ->values()
                    ->map(fn (array $option, int $index) => [
                        'letter' => chr(65 + $index),
                        'text' => (string) ($option['text'] ?? ''),
                        'is_correct' => (bool) ($option['is_correct'] ?? false),
                    ]);
                $text = (string) ($question['question'] ?? '');

                return [
                    'question' => $text,
                    'options' => $options->all(),
                    'answers' => $options->where('is_correct', true)->map(fn (array $option) => ['letter' => $option['letter'], 'text' => $option['text']])->values()->all(),
                    'search' => mb_strtolower($text . ' ' . $options->pluck('text')->implode(' ')),
                ];
            })
            ->values()
            ->all();
    }
}
