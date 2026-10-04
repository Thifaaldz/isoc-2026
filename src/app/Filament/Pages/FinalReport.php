<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\EvidenceResource;
use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use App\Filament\Support\UploadTypes;
use App\Models\Evidence;
use App\Services\SystemProofGenerator;
use App\Support\EventOverview;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Attributes\Url;

/** Admin RTIK Daerah: cek kelengkapan, preview, dan kirim laporan final event untuk pencairan Termin-2. */
class FinalReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Seminar';

    protected static ?string $navigationLabel = 'Laporan Final';

    protected static ?string $title = 'Laporan Final';

    protected static ?string $slug = 'laporan-final';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.final-report';

    #[Url(as: 'event')]
    public ?int $selectedEventId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function mount(): void
    {
        if (! $this->events->firstWhere('id', (int) $this->selectedEventId)) {
            $this->selectedEventId = $this->events->first()?->id;
        }
    }

    public function getEventsProperty(): EloquentCollection
    {
        return LearningEvent::query()->where('created_by', auth()->id())->orderBy('starts_at')->get();
    }

    public function getSelectedEventProperty(): ?LearningEvent
    {
        return $this->events->firstWhere('id', (int) $this->selectedEventId);
    }

    public function overview(): ?EventOverview
    {
        return $this->selectedEvent ? new EventOverview($this->selectedEvent) : null;
    }

    /** Tombol kirim laporan tampil selama laporan belum dikirim / perlu revisi; pengiriman tetap dicek kelengkapannya. */
    public function canSubmit(): bool
    {
        $event = $this->selectedEvent;

        return $event
            && in_array($event->workflow_status, ['verified_term_1', 'tot_completed', 'field_training_completed', 'verified_term_2'], true)
            && in_array($event->final_report_status ?? 'draft', ['draft', 'revision'], true);
    }

    public function submit(): void
    {
        if ($this->canSubmit()) {
            LearningEventResource::submitFinalReport($this->selectedEvent);
        }
    }

    /** Upload bukti dukung langsung dari checklist; status menunggu verifikasi Admin RTIK Pusat. */
    public function uploadEvidenceAction(): Action
    {
        $type = fn (array $arguments) => (string) ($arguments['type'] ?? '');
        $isPhoto = fn (array $arguments) => isset(Evidence::REQUIRED_PHOTOS[$type($arguments)]);

        return Action::make('uploadEvidence')
            ->label('Upload')
            ->icon('heroicon-o-arrow-up-tray')
            ->size('xs')
            ->color('gray')
            ->modalHeading(fn (array $arguments) => 'Upload ' . (Evidence::TYPES[$type($arguments)] ?? 'bukti dukung'))
            ->modalDescription(fn (array $arguments) => $isPhoto($arguments)
                ? Evidence::REQUIRED_PHOTOS[$type($arguments)]['instruction'] . ' Minimal ' . Evidence::REQUIRED_PHOTOS[$type($arguments)]['min'] . ' foto.'
                : 'Bukti dikirim ke Admin RTIK Pusat untuk diverifikasi.')
            ->modalSubmitActionLabel('Upload')
            ->form(fn (array $arguments) => array_values(array_filter([
                Forms\Components\FileUpload::make('files')
                    ->label($isPhoto($arguments) ? 'Foto' : 'Berkas')
                    ->multiple($isPhoto($arguments))
                    ->image($isPhoto($arguments))
                    ->acceptedFileTypes($isPhoto($arguments) ? UploadTypes::IMAGES : UploadTypes::evidence())
                    ->disk('public')
                    ->directory('evidences')
                    ->maxSize(51200)
                    ->panelLayout($isPhoto($arguments) ? 'grid' : null)
                    ->helperText(match (true) {
                        $isPhoto($arguments) => 'Upload foto, atau isi link Google Drive di bawah.',
                        $type($arguments) === 'video_slogan' => 'Upload video, atau isi link YouTube / Google Drive di bawah.',
                        default => 'Upload berkas, atau isi tautan di bawah.',
                    })
                    // Cukup salah satu: berkas atau link.
                    ->required(fn (Forms\Get $get) => blank($get('link'))),
                Forms\Components\TextInput::make('link')
                    ->label(match (true) {
                        $isPhoto($arguments) => 'Link Google Drive folder foto (opsional)',
                        $type($arguments) === 'video_slogan' => 'Link YouTube / Google Drive (opsional)',
                        default => 'Link Google Drive (opsional)',
                    })
                    ->placeholder($type($arguments) === 'video_slogan' ? 'https://youtu.be/... atau https://drive.google.com/...' : 'https://drive.google.com/...')
                    ->helperText($isPhoto($arguments) ? 'Link Drive yang disetujui Pusat dianggap memenuhi jumlah foto minimal.' : null)
                    ->url()
                    ->live(onBlur: true),
            ])))
            ->action(function (array $data, array $arguments) use ($type): void {
                $event = $this->selectedEvent;
                $evidenceType = $type($arguments);

                if (! $event || ! isset(Evidence::TYPES[$evidenceType])) {
                    return;
                }

                $files = array_values(array_filter((array) ($data['files'] ?? [])));
                $rows = $files === [] ? [null] : $files;

                foreach ($rows as $index => $path) {
                    Evidence::query()->create([
                        'learning_event_id' => $event->id,
                        'school_id' => $event->school_id,
                        'type' => $evidenceType,
                        'file_path' => $path,
                        'link' => $index === 0 ? ($data['link'] ?? null) : null,
                        'status' => 'pending',
                        'uploaded_by' => auth()->id(),
                    ]);
                }

                Notification::make()
                    ->title(count($rows) . ' bukti diunggah')
                    ->body('Menunggu verifikasi Admin RTIK Pusat. Checklist tercentang setelah disetujui.')
                    ->success()
                    ->send();
            });
    }

    public ?string $proofPreviewUrl = null;

    public ?string $proofPreviewTitle = null;

    /** Pilih sumber bukti ("system" = generate dari data sistem, "manual" = upload/tautan) untuk absensi atau microsite. */
    public function setProofMode(string $kind, string $mode): void
    {
        $event = $this->selectedEvent;

        if (! $event || ! isset(SystemProofGenerator::KINDS[$kind]) || ! in_array($mode, ['system', 'manual'], true) || $this->reportLocked()) {
            return;
        }

        $event->update([SystemProofGenerator::KINDS[$kind]['column'] => $mode]);

        if ($mode === 'manual') {
            app(SystemProofGenerator::class)->remove($event, $kind);

            return;
        }

        // Generate dari sistem: PDF langsung dibuat, disimpan sebagai bukti (langsung memenuhi syarat), lalu dipreview.
        $this->generateProof($kind);
    }

    public function generateProof(string $kind): void
    {
        $event = $this->selectedEvent;

        if (! $event || ! isset(SystemProofGenerator::KINDS[$kind]) || $event->proofMode($kind) !== 'system' || $this->reportLocked()) {
            return;
        }

        $generator = app(SystemProofGenerator::class);
        $evidence = $generator->generate($event, $kind);

        Notification::make()
            ->title(SystemProofGenerator::KINDS[$kind]['label'] . ' tersimpan')
            ->body('PDF dibuat otomatis dari data sistem dan syarat laporan sudah terpenuhi.')
            ->success()
            ->send();

        $this->previewProof($kind, $generator->url($evidence));
    }

    public function previewProof(string $kind, ?string $url = null): void
    {
        $generator = app(SystemProofGenerator::class);
        $evidence = $this->selectedEvent && isset(SystemProofGenerator::KINDS[$kind]) ? $generator->current($this->selectedEvent, $kind) : null;

        $this->proofPreviewUrl = $url ?? ($evidence ? $generator->url($evidence) : null);
        $this->proofPreviewTitle = 'Preview ' . strtolower(SystemProofGenerator::KINDS[$kind]['label'] ?? 'bukti') . ' (generate sistem)';

        if ($this->proofPreviewUrl) {
            $this->dispatch('open-modal', id: 'system-proof-preview');
        }
    }

    public function systemProof(string $kind): ?Evidence
    {
        return $this->selectedEvent ? app(SystemProofGenerator::class)->current($this->selectedEvent, $kind) : null;
    }

    public function reportLocked(): bool
    {
        return in_array($this->selectedEvent?->final_report_status, ['submitted', 'approved'], true);
    }

    public function getCheckedInProperty()
    {
        return $this->selectedEvent?->checkedInAttendances()->get() ?? collect();
    }

    public function getMicrositesProperty()
    {
        return $this->selectedEvent ? app(SystemProofGenerator::class)->microsites($this->selectedEvent) : collect();
    }

    public function evidenceUrl(): string
    {
        return EvidenceResource::getUrl();
    }
}
