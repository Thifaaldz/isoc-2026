<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\LearningMeetingResource;
use App\Models\Assessment;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/** Preview materi (PDF/PPT/video) dan tes untuk Pusat, Admin RTIK Daerah, dan Tutor. */
class MaterialPreview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationLabel = 'Preview Materi';

    protected static ?string $title = 'Preview Materi';

    protected static ?string $slug = 'preview-materi';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.material-preview';

    /** Format: template-{id}. */
    #[Url(as: 'materi')]
    public ?string $source = null;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return match (auth()->user()?->role) {
            UserRole::SuperAdmin => 'Konten & Penilaian',
            UserRole::Tutor => 'Pelatihan Tutor',
            default => 'Seminar',
        };
    }

    public static function getUrlForTemplate(ModuleTemplate $template): string
    {
        return static::getUrl(['materi' => 'template-' . $template->id]);
    }

    public function mount(): void
    {
        if (! array_key_exists((string) $this->source, $this->sourceOptions())) {
            $this->source = array_key_first($this->sourceOptions());
        }
    }

    /** @return array<string, string> */
    public function sourceOptions(): array
    {
        return $this->templateQuery()->orderBy('audience')->orderBy('name')->get()->toBase()
            ->mapWithKeys(fn (ModuleTemplate $template) => ['template-' . $template->id => $template->name . ' (' . (ModuleTemplate::AUDIENCES[$template->audience] ?? $template->audience) . ')'])
            ->all();
    }

    public function getSelectedProperty(): ?ModuleTemplate
    {
        [$type, $id] = array_pad(explode('-', (string) $this->source, 2), 2, null);

        return $type === 'template' ? $this->templateQuery()->find($id) : null;
    }

    public function getMeetingsProperty(): Collection
    {
        $selected = $this->selected;

        if (! $selected) {
            return collect();
        }

        $query = LearningMeeting::query()
            ->with(['materials' => fn ($materials) => $materials->orderBy('order'), 'assessments'])
            ->orderBy('order');

        return $query->where('module_template_id', $selected->id)->whereNull('learning_event_id')->get();
    }

    public function getAssessmentsProperty(): Collection
    {
        $selected = $this->selected;

        if (! $selected) {
            return collect();
        }

        $query = Assessment::query()->with('meeting')
            ->orderByRaw("case type when 'pre' then 0 when 'quiz' then 1 when 'post' then 2 else 3 end")
            ->orderBy('id');

        return $query->where(fn ($assessmentQuery) => $assessmentQuery
            ->where('module_template_id', $selected->id)
            ->whereNull('learning_event_id')
            ->orWhereIn('learning_meeting_id', $this->meetings->pluck('id')))
            ->get()->unique('id')->values();
    }

    /** @return array<int, array<string, mixed>> */
    public function questions(Assessment $assessment): array
    {
        $questions = $assessment->questions;

        if (is_string($questions)) {
            $questions = json_decode($questions, true);
        }

        return array_values(is_array($questions) ? $questions : []);
    }

    public function canSeeAnswerKey(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public function manageUrl(): ?string
    {
        $selected = $this->selected;

        return auth()->user()?->role === UserRole::SuperAdmin && $selected && ! $selected->isGeneratedForTutor()
            ? LearningMeetingResource::getUrl('index', ['materi_event' => $selected->id])
            : null;
    }

    private function templateQuery(): Builder
    {
        $user = auth()->user();
        $query = ModuleTemplate::query();

        return match ($user?->role) {
            UserRole::SuperAdmin => $query,
            UserRole::Admin => $query->where(fn ($templateQuery) => $templateQuery
                ->where(fn ($active) => $active->forParticipants()->where('is_active', true))
                ->orWhereHas('learningEvents', fn ($events) => $events->where('created_by', $user->id))),
            UserRole::Tutor => $query->where(fn ($templateQuery) => $templateQuery
                ->where(fn ($active) => $active->forTutors()->where('is_active', true))
                ->orWhereHas('learningEvents', fn ($events) => $events->whereHas('tutors', fn ($tutors) => $tutors->where('tutors.id', $user->tutor?->id)))),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
