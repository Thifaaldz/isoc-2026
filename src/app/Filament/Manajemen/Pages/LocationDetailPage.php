<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Manajemen\Tables\ManagementTables;
use App\Support\ProgramMetrics;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

/** Semua data satu lokasi: ringkasan (tahapan, angka, 13 syarat, termin) lalu tab data peserta/tutor/bukti/sertifikat. */
class LocationDetailPage extends ManagementDataPage
{
    public const TABS = ['peserta' => 'Peserta', 'tutor' => 'Tutor', 'bukti' => 'Bukti dukung', 'sertifikat' => 'Sertifikat'];

    protected static ?string $title = 'Detail Lokasi';

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'detail-lokasi';

    protected static string $view = 'filament.manajemen.location-detail';

    #[Url(as: 'event')]
    public ?int $eventId = null;

    #[Url(as: 'tab')]
    public string $tab = 'peserta';

    public function mount(): void
    {
        $this->eventId ??= ProgramMetrics::get()->rows->first()['event']->id ?? null;
        $this->tab = array_key_exists($this->tab, self::TABS) ? $this->tab : 'peserta';
    }

    public function updatedEventId(): void
    {
        $this->resetTable();
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'peserta';
        $this->resetTable();
    }

    public function getRowProperty(): ?array
    {
        return ProgramMetrics::get()->row($this->eventId);
    }

    public function getSubheading(): ?string
    {
        return 'Ringkasan satu lokasi, lalu data lengkapnya per tab.';
    }

    public function table(Table $table): Table
    {
        $event = $this->eventId ?? 0;

        return match ($this->tab) {
            'tutor' => ManagementTables::tutors($table, $event),
            'bukti' => ManagementTables::evidence($table, $event),
            'sertifikat' => ManagementTables::certificates($table, $event),
            default => ManagementTables::participants($table, $event),
        };
    }
}
