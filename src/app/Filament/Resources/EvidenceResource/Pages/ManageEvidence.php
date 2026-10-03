<?php

namespace App\Filament\Resources\EvidenceResource\Pages;

use App\Filament\Resources\EvidenceResource;
use App\Filament\Support\UploadTypes;
use App\Models\Evidence;
use App\Models\LearningEvent;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\HtmlString;

class ManageEvidence extends ManageRecords
{
    protected static string $resource = EvidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->requiredPhotosAction(),
            Actions\CreateAction::make()
                ->label('Upload Bukti Dukung')
                ->modalWidth('5xl'),
        ];
    }

    /** Wizard foto wajib a-g: tiap langkah berisi instruksi, jumlah minimal, dan progres foto event. */
    private function requiredPhotosAction(): Actions\Action
    {
        $photoSteps = collect(Evidence::REQUIRED_PHOTOS)->map(fn (array $photo, string $type) => Forms\Components\Wizard\Step::make($type)
            ->label("Foto {$photo['step']}")
            ->description("Minimal {$photo['min']} foto")
            ->icon('heroicon-o-camera')
            ->schema([
                Forms\Components\Placeholder::make("info_{$type}")
                    ->label("{$photo['step']}. {$photo['title']}")
                    ->content(fn (Forms\Get $get) => new HtmlString(
                        e($photo['instruction'])
                        . '<br><span class="text-sm text-gray-500">' . e($this->progressText($type, $get('learning_event_id'))) . '</span>'
                    )),
                Forms\Components\FileUpload::make("photos.{$type}")
                    ->label('Upload foto')
                    ->helperText("Unggah minimal {$photo['min']} foto. Bisa memilih beberapa foto sekaligus; foto yang sudah terkumpul sebelumnya ikut dihitung.")
                    ->multiple()
                    ->image()
                    ->acceptedFileTypes(UploadTypes::IMAGES)
                    ->disk('public')
                    ->directory('evidences')
                    ->maxSize(10240)
                    ->panelLayout('grid'),
            ]))->values()->all();

        return Actions\Action::make('uploadRequiredPhotos')
            ->label('Upload Foto Wajib')
            ->icon('heroicon-o-camera')
            ->color('success')
            ->modalWidth('5xl')
            ->modalHeading('Upload Foto Wajib Kegiatan')
            ->modalDescription('Kumpulkan foto wajib a-g untuk setiap lokasi kegiatan. Foto dikirim ke Admin RTIK Pusat untuk diverifikasi.')
            ->steps([
                Forms\Components\Wizard\Step::make('event')
                    ->label('Event / Lokus')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Select::make('learning_event_id')
                            ->label('Event')
                            ->options(fn () => EvidenceResource::eventOptions())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        Forms\Components\Placeholder::make('checklist')
                            ->label('Checklist foto wajib')
                            ->content(fn (Forms\Get $get) => $this->checklistHtml($get('learning_event_id'))),
                    ]),
                ...$photoSteps,
            ])
            ->action(function (array $data): void {
                $event = LearningEvent::query()->find($data['learning_event_id']);
                $created = 0;

                foreach (array_keys(Evidence::REQUIRED_PHOTOS) as $type) {
                    foreach ((array) ($data['photos'][$type] ?? []) as $path) {
                        Evidence::query()->create([
                            'learning_event_id' => $event?->id,
                            'school_id' => $event?->school_id,
                            'type' => $type,
                            'file_path' => $path,
                            'status' => 'pending',
                            'uploaded_by' => auth()->id(),
                        ]);
                        $created++;
                    }
                }

                $remaining = collect(Evidence::REQUIRED_PHOTOS)
                    ->filter(fn (array $photo, string $type) => Evidence::photoCounts($event?->id)[$type] < $photo['min'])
                    ->map(fn (array $photo) => "foto {$photo['step']}")
                    ->values();

                Notification::make()
                    ->title($created . ' foto wajib diunggah')
                    ->body($remaining->isEmpty()
                        ? 'Semua foto wajib sudah terkumpul dan menunggu verifikasi Admin RTIK Pusat.'
                        : 'Masih kurang: ' . $remaining->implode(', ') . '.')
                    ->color($remaining->isEmpty() ? 'success' : 'warning')
                    ->send();
            });
    }

    private function progressText(string $type, mixed $eventId): string
    {
        if (! $eventId) {
            return 'Pilih event terlebih dahulu untuk melihat progres.';
        }

        $min = Evidence::REQUIRED_PHOTOS[$type]['min'];
        $collected = Evidence::photoCounts((int) $eventId)[$type];
        $approved = Evidence::photoCounts((int) $eventId, approvedOnly: true)[$type];

        return "Sudah terkumpul {$collected}/{$min} foto ({$approved} disetujui).";
    }

    private function checklistHtml(mixed $eventId): HtmlString
    {
        $collected = Evidence::photoCounts($eventId ? (int) $eventId : null);
        $approved = Evidence::photoCounts($eventId ? (int) $eventId : null, approvedOnly: true);

        $rows = collect(Evidence::REQUIRED_PHOTOS)->map(function (array $photo, string $type) use ($collected, $approved, $eventId) {
            $done = $collected[$type] >= $photo['min'];
            $status = $eventId
                ? ($done ? '✅' : '⬜') . " {$collected[$type]}/{$photo['min']} terkumpul, {$approved[$type]} disetujui"
                : "minimal {$photo['min']} foto";

            return '<li><strong>' . e($photo['step'] . '. ' . $photo['title']) . '</strong> - ' . e($status) . '</li>';
        })->implode('');

        return new HtmlString('<ul class="list-none space-y-1 text-sm">' . $rows . '</ul>');
    }
}
