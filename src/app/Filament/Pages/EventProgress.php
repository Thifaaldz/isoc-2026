<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\AssessmentAttemptResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\EvidenceResource;
use App\Filament\Resources\LearningEventResource;
use App\Filament\Resources\MicrositePracticeResource;
use App\Filament\Resources\ParticipantResource;
use App\Models\LearningEvent;
use App\Support\EventOverview;
use App\Support\TorEventTemplate;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

/** Dashboard Super Admin: progres satu event — checklist yang sudah dan belum terpenuhi (dibuka dari Progres per lokasi). */
class EventProgress extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $title = 'Progres Event';

    protected static ?string $slug = 'progres-event';

    protected static string $view = 'filament.pages.event-progress';

    protected static bool $shouldRegisterNavigation = false;

    #[Url(as: 'event')]
    public ?int $eventId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public function mount(): void
    {
        abort_unless($this->event, 404);
    }

    public function getEventProperty(): ?LearningEvent
    {
        return $this->eventId ? LearningEvent::query()->with(['school', 'creator'])->find($this->eventId) : null;
    }

    public function getTitle(): string
    {
        return 'Progres: ' . ($this->event?->school?->name ?? $this->event?->title ?? 'Event');
    }

    /** @return array<int, array{title: string, note: ?string, items: array<int, array{label: string, done: bool, detail: string, link: ?string}>}> */
    public function getSectionsProperty(): array
    {
        $event = $this->event;
        $overview = new EventOverview($event);
        $termOne = TorEventTemplate::checkedKeys($event->budget_items, 1);

        return [
            [
                'title' => 'Syarat laporan final',
                'note' => 'Sama dengan pengecekan saat fasilitator menekan "Submit Laporan Final".',
                'items' => array_map(fn (array $item) => $this->item($item), $overview->finalReportChecklist()),
            ],
            [
                'title' => 'Checklist Termin-1',
                'note' => TorEventTemplate::TERM_NOTES[1],
                'items' => collect(TorEventTemplate::TERM_CHECKLIST[1])
                    ->map(fn (string $label, string $key) => [
                        'label' => $label,
                        'done' => in_array($key, $termOne, true),
                        'detail' => in_array($key, $termOne, true) ? 'Dicentang di Checklist Termin' : 'Belum dicentang di Checklist Termin',
                        'link' => LearningEventResource::getUrl('edit', ['record' => $event]),
                    ])->values()->all(),
            ],
            [
                'title' => 'Checklist Termin-2',
                'note' => TorEventTemplate::TERM_NOTES[2],
                'items' => array_map(fn (array $item) => $this->item($item), $overview->termTwoChecklist()),
            ],
        ];
    }

    public function getOverviewProperty(): EventOverview
    {
        return new EventOverview($this->event);
    }

    /** Ubah item checklist EventOverview menjadi baris tampilan dengan tautan yang bisa dibuka Super Admin. */
    private function item(array $item): array
    {
        $event = ['event' => $this->event->id];
        $key = $item['key'];

        $link = match (true) {
            filled($item['evidence'] ?? null), str_starts_with($key, 'foto_'), in_array($key, ['video_slogan', 'absensi_basah', 'praktik_microsite'], true) => EvidenceResource::getUrl('index', $event),
            in_array($key, ['daftar_hadir'], true) => AttendanceResource::getUrl('index', $event),
            in_array($key, ['registrasi', 'follow_ig_wag'], true) => ParticipantResource::getUrl('index', $event),
            in_array($key, ['tes', 'daftar_nilai', 'ranking_peserta'], true) => AssessmentAttemptResource::getUrl('index', $event),
            $key === 'microsite_peserta' => MicrositePracticeResource::getUrl('index', $event),
            $key === 'sertifikat_peserta' => CertificateResource::getUrl(),
            default => LearningEventResource::getUrl('view', ['record' => $this->event]),
        };

        return ['label' => $item['label'], 'done' => (bool) $item['done'], 'detail' => (string) ($item['detail'] ?? ''), 'link' => $link];
    }
}
