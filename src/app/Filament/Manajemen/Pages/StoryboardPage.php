<?php

namespace App\Filament\Manajemen\Pages;

use App\Support\ProgramStoryboard;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Storyboard manajerial dalam bentuk laporan naratif: ringkasan eksekutif, gambaran pelaksanaan (Bab 1),
 * enam kriteria pelaporan (Bab 2: data peserta, data tutor, laporan per lokus, self-efficacy,
 * kenaikan nilai, phishing/misinformasi), lalu kesimpulan dan tindak lanjut (Bab 3). Read-only.
 */
class StoryboardPage extends Page
{
    use WithPagination;

    protected static ?string $title = 'Storyboard Program';

    protected static ?string $navigationLabel = 'Storyboard';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'storyboard';

    protected static string $view = 'filament.manajemen.storyboard';

    /** Filter daftar peserta (2.1). */
    public ?string $pLokasi = null;

    public ?string $pStatus = null;

    public string $pCari = '';

    /** Filter daftar tutor (2.2), terpisah dari filter peserta. */
    public ?string $tLokasi = null;

    public ?string $tStatus = null;

    public string $tCari = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canViewManagement();
    }

    public function getSubheading(): ?string
    {
        return 'Laporan pekerjaan Program Digital Safety Champions: ringkasan eksekutif, gambaran pelaksanaan, enam kriteria pelaporan, serta kesimpulan dan tindak lanjut.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('laporanPdf')
                ->label('Unduh Laporan PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->modalHeading('Unduh laporan akhir (PDF)')
                ->modalDescription('Laporan berisi sampul, ringkasan eksekutif, Bab 1–3, dan lampiran metodologi. Pilih lampiran daftar yang ingin disertakan.')
                ->modalSubmitActionLabel('Unduh PDF')
                ->modalWidth('lg')
                ->form([
                    Select::make('tutor')->label('Lampiran daftar tutor')
                        ->options(['all' => 'Semua tutor', 'filter' => 'Sesuai filter daftar tutor saat ini', 'none' => 'Tidak disertakan'])
                        ->default('all')->selectablePlaceholder(false)->required(),
                    Select::make('peserta')->label('Lampiran daftar peserta')
                        ->options(['none' => 'Tidak disertakan', 'filter' => 'Sesuai filter daftar peserta saat ini', 'all' => 'Semua peserta (dokumen bisa sangat tebal)'])
                        ->default('none')->selectablePlaceholder(false)->required()
                        ->helperText('Daftar semua peserta bisa mencapai puluhan halaman dan butuh waktu lebih lama.'),
                ])
                ->action(fn (array $data) => $this->downloadLaporanPdf($data['tutor'] ?? 'all', $data['peserta'] ?? 'none')),
        ];
    }

    /** Laporan akhir PDF seluruh storyboard; lampiran daftar mengikuti pilihan (semua / sesuai filter / tidak). */
    public function downloadLaporanPdf(string $tutorMode = 'all', string $pesertaMode = 'none'): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $s = ProgramStoryboard::get();
        [$pEvent, $pStatus, $pCari] = $this->pesertaFilter();
        [$tEvent, $tStatus, $tCari] = $this->tutorFilter();

        $tutorList = match ($tutorMode) {
            'filter' => $s->tutorsQuery($tEvent, $tCari, $tStatus)->get(),
            'none' => null,
            default => $s->tutorsQuery()->get(),
        };
        $participantList = match ($pesertaMode) {
            'filter' => $s->participantsQuery($pEvent, $pCari, $pStatus)->get(),
            'all' => $s->participantsQuery()->get(),
            default => null,
        };
        $tutorNote = $tutorMode === 'filter' ? 'Daftar tutor sesuai filter: ' . $this->describeFilter($tEvent, $tStatus ? ProgramStoryboard::STATUS_TUTOR[$tStatus] : null, $tCari) . '.' : 'Daftar seluruh penugasan tutor.';
        $participantNote = $pesertaMode === 'filter' ? 'Daftar peserta sesuai filter: ' . $this->describeFilter($pEvent, $pStatus ? ProgramStoryboard::STATUS_PESERTA[$pStatus] : null, $pCari) . '.' : 'Daftar seluruh peserta terdaftar.';
        $filterNote = collect([
            $tutorList !== null ? ($tutorMode === 'filter' ? 'Daftar tutor (sesuai filter)' : 'Daftar tutor (semua)') : null,
            $participantList !== null ? ($pesertaMode === 'filter' ? 'Daftar peserta (sesuai filter)' : 'Daftar peserta (semua)') : null,
        ])->filter()->implode(' · ');

        $pdf = Pdf::loadView('reports.storyboard', [
            'generatedAt' => now(),
            'totals' => $s->totals(),
            'reach' => $s->reach(),
            'funnel' => $s->funnel(),
            'ind' => $s->indicators(),
            'dist' => $s->distribution(),
            'themes' => $s->themes(),
            'trend' => $s->trend(),
            'comp' => $s->completeness(),
            'locations' => $s->locations(),
            'attention' => $s->attention(),
            'tutorList' => $tutorList,
            'participantList' => $participantList,
            'tutorNote' => $tutorNote,
            'participantNote' => $participantNote,
            'filterNote' => $filterNote,
        ])->setPaper('a4', 'portrait')->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);

        // Nomor halaman "Halaman X dari Y" ditulis langsung ke kanvas (CSS counter(pages) tidak didukung dompdf).
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text($canvas->get_width() - 51 - 72, $canvas->get_height() - 30, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 6.8, [0.39, 0.45, 0.55]);
        $output = $dompdf->output();

        return response()->streamDownload(fn () => print($output), 'laporan-akhir-dsc-' . now()->format('Ymd') . '.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function describeFilter(?int $event, ?string $status, string $cari): string
    {
        $parts = array_filter([
            $event ? 'lokasi ' . (ProgramStoryboard::get()->locationOptions()[$event] ?? '-') : 'semua lokasi',
            $status ? 'status ' . mb_strtolower($status) : null,
            $cari !== '' ? 'kata kunci "' . $cari . '"' : null,
        ]);

        return implode(', ', $parts);
    }

    public function updated(string $name): void
    {
        if (str_starts_with($name, 'p')) {
            $this->resetPage('pesertaPage');
        }
        if (str_starts_with($name, 't')) {
            $this->resetPage('tutorPage');
        }
    }

    public function resetPesertaFilter(): void
    {
        $this->reset('pLokasi', 'pStatus', 'pCari');
        $this->resetPage('pesertaPage');
    }

    public function resetTutorFilter(): void
    {
        $this->reset('tLokasi', 'tStatus', 'tCari');
        $this->resetPage('tutorPage');
    }

    /** @return array{0: ?int, 1: ?string, 2: string} event id, status, cari — sudah divalidasi. */
    private function pesertaFilter(): array
    {
        return [$this->validEvent($this->pLokasi), array_key_exists((string) $this->pStatus, ProgramStoryboard::STATUS_PESERTA) ? $this->pStatus : null, trim($this->pCari)];
    }

    private function tutorFilter(): array
    {
        return [$this->validEvent($this->tLokasi), array_key_exists((string) $this->tStatus, ProgramStoryboard::STATUS_TUTOR) ? $this->tStatus : null, trim($this->tCari)];
    }

    private function validEvent(?string $id): ?int
    {
        return filled($id) && array_key_exists((int) $id, ProgramStoryboard::get()->locationOptions()) ? (int) $id : null;
    }

    /** Unduh daftar peserta: hanya baris yang cocok dengan filter aktif. */
    public function downloadPeserta(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        [$event, $status, $cari] = $this->pesertaFilter();
        $gender = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

        return $this->csv(
            $this->filename('peserta', $event, $status, $cari),
            ['Nama', 'Lokasi', 'Sekolah', 'Kelas', 'Jenis kelamin', 'Email', 'No HP', 'Hadir', 'Pre-Test', 'Post-Test', 'Microsite', 'Sertifikat'],
            ProgramStoryboard::get()->participantsQuery($event, $cari, $status),
            fn ($r) => [$r->name, ProgramStoryboard::shortName($r->event_title), $r->sekolah, $r->grade, $gender[$r->gender] ?? '', $r->email, $r->phone,
                $r->hadir ? 'ya' : 'tidak', $r->nilai_pre !== null ? (float) $r->nilai_pre : '', $r->nilai_post !== null ? (float) $r->nilai_post : '',
                $r->microsite ? 'ya' : 'tidak', $r->sertifikat ? 'terbit' : 'belum'],
        );
    }

    /** Unduh daftar tutor: hanya baris yang cocok dengan filter aktif. Tanpa NIK/NPWP/rekening. */
    public function downloadTutor(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        [$event, $status, $cari] = $this->tutorFilter();

        return $this->csv(
            $this->filename('tutor', $event, $status, $cari),
            ['Nama', 'Lokasi', 'Lembaga', 'Email', 'No HP', 'Lulus ToT', 'Nilai ToT', 'Data honor'],
            ProgramStoryboard::get()->tutorsQuery($event, $cari, $status),
            fn ($r) => [$r->name, ProgramStoryboard::shortName($r->event_title), $r->institution, $r->email, $r->phone,
                $r->tot_completed ? 'ya' : 'tidak', $r->nilai_tot !== null ? (float) $r->nilai_tot : '', $r->honor_lengkap ? 'lengkap' : 'belum lengkap'],
        );
    }

    /** Unduh laporan kegiatan per lokus (2.3). */
    public function downloadLaporan(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);
        $rows = ProgramStoryboard::get()->locations();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Lokasi', 'Tanggal', 'Target', 'Terdaftar', 'Hadir', 'Hadir %', 'Rata-rata Pre-Test', 'Rata-rata Post-Test', 'Kenaikan per peserta', 'Microsite dibuat', 'Sertifikat terbit', 'Sertifikat %', 'Tutor', 'Tutor lulus ToT', 'Bukti disetujui', 'Laporan final']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['lokasi'], $r['tanggal']?->format('Y-m-d'), $r['target'], $r['terdaftar'], $r['hadir'], $r['pct_hadir'], $r['rata_pre'], $r['rata_post'], $r['kenaikan'], $r['microsite'], $r['sertifikat'], $r['pct_sertifikat'], $r['tutor'], $r['tutor_tot'], $r['bukti_disetujui'], $r['laporan_final']]);
            }
            fclose($out);
        }, 'laporan-per-lokus-dsc.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csv(string $filename, array $header, $query, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $query, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, $header);
            foreach ($query->cursor() as $row) {
                fputcsv($out, $map($row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filename(string $base, ?int $event, ?string $status, string $cari): string
    {
        $lokasi = $event ? Str::slug(ProgramStoryboard::get()->locationOptions()[$event] ?? '') : 'semua-lokasi';

        return collect(["{$base}-dsc", $lokasi, $status, $cari !== '' ? 'cari-' . Str::limit(Str::slug($cari), 20, '') : null])
            ->filter()->implode('_') . '.csv';
    }

    protected function getViewData(): array
    {
        $s = ProgramStoryboard::get();
        [$pEvent, $pStatus, $pCari] = $this->pesertaFilter();
        [$tEvent, $tStatus, $tCari] = $this->tutorFilter();

        return [
            'totals' => $s->totals(),
            'reach' => $s->reach(),
            'funnel' => $s->funnel(),
            'ind' => $s->indicators(),
            'dist' => $s->distribution(),
            'themes' => $s->themes(),
            'trend' => $s->trend(),
            'comp' => $s->completeness(),
            'locations' => $s->locations(),
            'attention' => $s->attention(),
            'lokasiOptions' => $s->locationOptions(),
            'statusPeserta' => ProgramStoryboard::STATUS_PESERTA,
            'statusTutor' => ProgramStoryboard::STATUS_TUTOR,
            'peserta' => $s->participantsQuery($pEvent, $pCari, $pStatus)->paginate(25, ['*'], 'pesertaPage'),
            'pesertaSummary' => $s->participantSummary($pEvent, $pCari, $pStatus),
            'pesertaFiltered' => $pEvent || $pStatus || $pCari !== '',
            'tutor' => $s->tutorsQuery($tEvent, $tCari, $tStatus)->paginate(20, ['*'], 'tutorPage'),
            'tutorSummary' => $s->tutorSummary($tEvent, $tCari, $tStatus),
            'tutorFiltered' => $tEvent || $tStatus || $tCari !== '',
        ];
    }
}
