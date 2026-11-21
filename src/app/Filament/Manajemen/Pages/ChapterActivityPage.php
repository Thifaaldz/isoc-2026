<?php

namespace App\Filament\Manajemen\Pages;

use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Set;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan keseluruhan kegiatan per periode, mengikuti kolom kuning "Chapter Activity _Template.xlsx":
 * Activity Name, Activity Date, Number of Participants, Location, Pre/Post Test Achievement, Address, ditambah Type dan URL.
 * Unduhan Excel memakai file template itu sendiri (14 kolom tetap ada); kolom yang belum ada datanya dibiarkan kosong.
 * Semua angka dihitung dari data yang sudah ada (tanpa tabel/kolom baru).
 */
class ChapterActivityPage extends ManagementDataPage
{
    protected static ?string $title = 'Laporan Chapter Activity';

    protected static ?string $navigationLabel = 'Chapter Activity';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'chapter-activity';

    protected static string $view = 'filament.manajemen.chapter-activity';

    /** Salinan "docs/Chapter Activity _Template.xlsx"; export mengisi file ini, bukan membuat ulang. */
    private const TEMPLATE = 'templates/chapter-activity-template.xlsx';

    private const SHEET = 'xl/worksheets/sheet1.xml';

    private const FIRST_DATA_ROW = 3;

    private const TEMPLATE_LAST_ROW = 1000;

    /** Index cellXfs di styles.xml template: 3 = header kuning (FFF2CC), 2 = header putih. */
    private const YELLOW_HEADER_STYLE = 3;

    private const PLAIN_HEADER_STYLE = 2;

    private const STYLES = 'xl/styles.xml';

    private const WORKBOOK = 'xl/workbook.xml';

    /** Gaya sel data yang ditambahkan tidyStyles(): index cellXfs 4–7 (teks kiri, tanggal, rata tengah, nilai 0.0). */
    private const STYLE_TEXT = 4;

    private const STYLE_DATE = 5;

    private const STYLE_CENTER = 6;

    private const STYLE_SCORE = 7;

    private const COLUMN_STYLES = ['C' => self::STYLE_DATE, 'D' => self::STYLE_CENTER, 'E' => self::STYLE_CENTER, 'K' => self::STYLE_SCORE, 'L' => self::STYLE_SCORE];

    private const COLUMN_WIDTHS = [
        'A' => 10, 'B' => 50, 'C' => 15, 'D' => 11, 'E' => 14, 'F' => 22, 'G' => 14,
        'H' => 16, 'I' => 44, 'J' => 62, 'K' => 13, 'L' => 13, 'M' => 16, 'N' => 88,
    ];

    public function getSubheading(): ?string
    {
        return 'Rekap seluruh kegiatan dalam periode yang dipilih (default: tahun ini). Ubah periode lewat filter, lalu unduh Excel.';
    }

    public function dataHeading(): ?string
    {
        return 'Data Chapter Activity';
    }

    public function dataDescription(): ?string
    {
        return 'Participants = peserta yang tercatat hadir. Pre/Post Test = rata-rata nilai peserta. Kolom Location & Address bisa ditampilkan lewat tombol kolom.';
    }

    /** @return array{0: string, 1: string}|null rentang tanggal [dari, sampai] untuk preset, null = pilih sendiri. */
    public static function presetRange(?string $preset): ?array
    {
        $today = now();

        return match ($preset) {
            'this_year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'last_12_months' => [$today->copy()->subYear()->addDay()->toDateString(), $today->toDateString()],
            'last_year' => [$today->copy()->subYear()->startOfYear()->toDateString(), $today->copy()->subYear()->endOfYear()->toDateString()],
            default => null,
        };
    }

    public static function query(): Builder
    {
        $avg = fn (string $type) => "(SELECT AVG(aa.score) FROM assessment_attempts aa JOIN assessments a ON a.id = aa.assessment_id WHERE a.learning_event_id = learning_events.id AND a.type = '{$type}' AND aa.participant_id IS NOT NULL)";

        return LearningEvent::query()
            ->with('school')
            ->whereNotIn('workflow_status', ['cancelled', 'rejected'])
            ->select('learning_events.*')
            ->selectRaw("(SELECT COUNT(DISTINCT t.participant_id) FROM attendances t WHERE t.learning_event_id = learning_events.id AND t.status = 'hadir' AND t.participant_id IS NOT NULL) AS attended_count")
            ->selectRaw("(SELECT COUNT(*) FROM learning_event_participant lep WHERE lep.learning_event_id = learning_events.id) AS registered_count")
            ->selectRaw($avg('pre') . ' AS avg_pre')
            ->selectRaw($avg('post') . ' AS avg_post');
    }

    public static function location(LearningEvent $event): string
    {
        $school = $event->school;
        $place = static::joinPlaces([$school?->city, $school?->province]);

        if ($place === '' && $school) {
            $place = $school->name;
        }

        return $place !== '' ? $place : ($event->event_type === 'webinar' ? 'Online' : '-');
    }

    /** Label tipe sama dengan form event: Offline / Webinar / Hybrid. */
    public static function type(LearningEvent $event): string
    {
        $type = $event->event_type ?: 'offline';

        return LearningEventResource::eventTypeOptions()[$type] ?? ucfirst($type);
    }

    /** URL = link preview laporan kegiatan (PDF). Perlu login: Super Admin, Manajemen, fasilitator/tutor event. */
    public static function url(LearningEvent $event): string
    {
        return route('reports.events.activity.preview', $event);
    }

    public static function address(LearningEvent $event): string
    {
        $school = $event->school;

        if (! $school) {
            return $event->event_type === 'webinar' ? 'Online' : '-';
        }

        return static::joinPlaces([$school->name, $school->address, $school->village, $school->district, $school->city, $school->province]);
    }

    /**
     * Gabungkan nama tempat dengan rapi: "KOTA SURABAYA" → "Kota Surabaya", dan buang bagian yang sudah
     * tercakup bagian sesudahnya ("Surabaya, Jawa Timur, Kota Surabaya, Jawa Timur" → "Kota Surabaya, Jawa Timur").
     *
     * @param  array<int, ?string>  $parts
     */
    public static function joinPlaces(array $parts): string
    {
        // Kolom alamat sendiri bisa berisi koma ("Surabaya, Jawa Timur"), jadi dipecah dulu per bagian.
        $parts = collect($parts)->flatMap(fn ($p) => explode(',', (string) $p))
            ->map(fn (string $p) => trim($p))->filter()->map(fn (string $p) => static::tidyCase($p))->values();

        return $parts
            ->reject(fn (string $p, int $i) => $parts->slice($i + 1)->contains(
                fn (string $later) => preg_match('/\b' . preg_quote(mb_strtolower($p), '/') . '\b/u', mb_strtolower($later)) === 1,
            ))
            ->implode(', ');
    }

    /** Teks HURUF BESAR semua jadi Huruf Awal Besar; singkatan (DKI, SMK, ...) tetap kapital. */
    private static function tidyCase(string $text): string
    {
        if ($text !== mb_strtoupper($text) || $text === mb_strtolower($text)) {
            return $text;
        }

        $acronyms = ['DKI', 'DI', 'SMK', 'SMA', 'SMP', 'SD', 'MA', 'MAN', 'MTS', 'MI', 'RT', 'RW', 'DSC', 'NTB', 'NTT'];

        return collect(preg_split('/(\s+)/u', mb_strtolower($text), -1, PREG_SPLIT_DELIM_CAPTURE))
            ->map(fn (string $w) => in_array(mb_strtoupper($w), $acronyms, true) ? mb_strtoupper($w) : mb_convert_case($w, MB_CASE_TITLE))
            ->implode('');
    }

    private static function score($value): ?float
    {
        return $value === null ? null : round((float) $value, 1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(static::query())
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Activity Name')->searchable()->sortable()->wrap()
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn (string $state) => static::shortTitle($state))
                    ->description(fn (LearningEvent $r) => static::location($r)),
                Tables\Columns\TextColumn::make('starts_at')->label('Activity Date')->date('d M Y')->sortable()->placeholder('-')
                    ->description(fn (LearningEvent $r) => $r->starts_at?->format('H:i') . ' WIB'),
                Tables\Columns\TextColumn::make('event_type')->label('Type')->badge()->sortable()->alignCenter()
                    ->state(fn (LearningEvent $r) => static::type($r))
                    ->color(fn (LearningEvent $r) => match ($r->event_type) {
                        'webinar' => 'info',
                        'hybrid' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attended_count')->label('Participants')->numeric()->sortable()->alignCenter()
                    ->tooltip('Number of Participants: peserta yang tercatat hadir')
                    ->description(fn (LearningEvent $r) => 'dari ' . (int) $r->registered_count),
                Tables\Columns\TextColumn::make('avg_pre')->label('Pre Test')->numeric(1)->placeholder('-')->sortable()->alignCenter()
                    ->tooltip('Pre Test Achievement: rata-rata nilai'),
                Tables\Columns\TextColumn::make('avg_post')->label('Post Test')->numeric(1)->placeholder('-')->sortable()->alignCenter()
                    ->tooltip('Post Test Achievement: rata-rata nilai')
                    ->badge()->color(fn ($state) => $state === null ? 'gray' : ((float) $state >= 85 ? 'success' : 'warning')),
                Tables\Columns\TextColumn::make('location')->label('Location')->state(fn (LearningEvent $r) => static::location($r))->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')->label('Address')->state(fn (LearningEvent $r) => static::address($r))->wrap()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->form([
                        Forms\Components\Select::make('preset')->label('Periode')
                            ->options([
                                'this_year' => 'Tahun ini',
                                'last_12_months' => '12 bulan terakhir',
                                'last_year' => 'Tahun lalu',
                                'custom' => 'Pilih tanggal sendiri',
                            ])
                            ->default('this_year')
                            ->selectablePlaceholder(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if ($range = static::presetRange($state)) {
                                    $set('from', $range[0]);
                                    $set('until', $range[1]);
                                }
                            }),
                        Forms\Components\DatePicker::make('from')->label('Dari tanggal')
                            ->default(fn () => static::presetRange('this_year')[0])->native(false)->displayFormat('d M Y')
                            ->live()->afterStateUpdated(fn (Set $set) => $set('preset', 'custom')),
                        Forms\Components\DatePicker::make('until')->label('Sampai tanggal')
                            ->default(fn () => static::presetRange('this_year')[1])->native(false)->displayFormat('d M Y')
                            ->live()->afterStateUpdated(fn (Set $set) => $set('preset', 'custom')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $from) => $q->where('starts_at', '>=', Carbon::parse($from)->startOfDay()))
                        ->when($data['until'] ?? null, fn (Builder $q, $until) => $q->where('starts_at', '<=', Carbon::parse($until)->endOfDay())))
                    ->indicateUsing(function (array $data): ?string {
                        $from = filled($data['from'] ?? null) ? Carbon::parse($data['from'])->translatedFormat('d M Y') : null;
                        $until = filled($data['until'] ?? null) ? Carbon::parse($data['until'])->translatedFormat('d M Y') : null;

                        return match (true) {
                            $from && $until => "Periode: {$from} – {$until}",
                            (bool) $from => "Sejak {$from}",
                            (bool) $until => "Sampai {$until}",
                            default => null,
                        };
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\Action::make('laporan')
                    ->label('Laporan')
                    ->button()->outlined()->size(ActionSize::Small)->color('gray')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->tooltip('Buka laporan kegiatan (PDF). Link ini juga yang masuk ke kolom URL di Excel.')
                    ->url(fn (LearningEvent $r) => static::url($r), shouldOpenInNewTab: true),
            ])
            ->defaultSort('starts_at')
            ->striped()
            ->emptyStateHeading('Tidak ada kegiatan pada periode ini')
            ->emptyStateDescription('Ubah periode atau pilih tanggal sendiri.')
            ->paginated([10, 25, 50, 100]);
    }

    /**
     * Ringkasan seluruh hasil filter (bukan hanya halaman yang tampil), untuk kartu di atas tabel.
     *
     * @return array{events: int, attended: int, registered: int, pre: ?float, post: ?float, period: string}
     */
    public function summaryStats(): array
    {
        $rows = $this->getFilteredTableQuery()->get();
        $avg = fn (string $col) => ($v = $rows->whereNotNull($col)->avg($col)) === null ? null : round((float) $v, 1);

        return [
            'events' => $rows->count(),
            'attended' => (int) $rows->sum('attended_count'),
            'registered' => (int) $rows->sum('registered_count'),
            'pre' => $avg('avg_pre'),
            'post' => $avg('avg_post'),
            'period' => $this->periodLabel(),
        ];
    }

    private function periodLabel(): string
    {
        $filter = $this->getTableFilterState('periode') ?? [];
        $fmt = fn ($d) => filled($d) ? Carbon::parse($d)->translatedFormat('d M Y') : null;
        [$from, $until] = [$fmt($filter['from'] ?? null), $fmt($filter['until'] ?? null)];

        return match (true) {
            $from && $until => "{$from} – {$until}",
            (bool) $from => "Sejak {$from}",
            (bool) $until => "Sampai {$until}",
            default => 'Semua periode',
        };
    }

    /** "Digital Safety Champions - SMK X" → "SMK X" agar tabel ringkas; Excel tetap nama lengkap. */
    public static function shortTitle(string $title): string
    {
        return (string) str($title)->replaceStart('Digital Safety Champions - ', '');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Unduh Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->export()),
        ];
    }

    /**
     * Baris data untuk template: kunci = kolom Excel. Kolom template yang tidak ada di sini
     * (Action, Purpose, Is Mission Related, Focus Area, Upload File) sengaja dikosongkan.
     *
     * @return array<string, string|int|float|null>
     */
    public static function templateRow(LearningEvent $event): array
    {
        return [
            'B' => $event->title,
            'C' => $event->starts_at?->copy()->timezone(config('app.timezone')),
            'D' => static::type($event),
            'E' => (int) $event->attended_count,
            'I' => static::location($event),
            'J' => static::url($event),
            'K' => static::score($event->avg_pre),
            'L' => static::score($event->avg_post),
            'N' => static::address($event),
        ];
    }

    /**
     * Isi file "Chapter Activity _Template.xlsx": baris 1–2 (header 14 kolom) tetap, hanya warna kuning
     * header dihapus; data mulai baris 3. Gaya sel memakai style kosong template (s="1").
     */
    public static function fillTemplate(iterable $rows, string $path): void
    {
        copy(resource_path(self::TEMPLATE), $path);

        $zip = new \ZipArchive();
        $zip->open($path);
        $sheet = $zip->getFromName(self::SHEET);
        $zip->addFromString(self::STYLES, static::tidyStyles($zip->getFromName(self::STYLES)));

        $start = strpos($sheet, '<row r="' . self::FIRST_DATA_ROW . '"');
        $end = strpos($sheet, '</sheetData>');
        $columns = range('A', 'Z');
        $template = array_flip(range('A', 'N'));

        $rowXml = function (int $r, array $values, bool $data) use ($columns, $template): string {
            $cells = '';
            foreach ($columns as $col) {
                $value = $values[$col] ?? null;
                // Baris data: kolom template (A–N) bergaris; kolom di luar template tetap gaya kosong.
                $style = ! $data || ! isset($template[$col]) ? 1 : (self::COLUMN_STYLES[$col] ?? self::STYLE_TEXT);
                $ref = "{$col}{$r}";
                $cells .= match (true) {
                    $value === null || $value === '' => "<c r=\"{$ref}\" s=\"{$style}\"/>",
                    $value instanceof \DateTimeInterface => "<c r=\"{$ref}\" s=\"{$style}\"><v>" . static::excelDate($value) . '</v></c>',
                    is_int($value) || is_float($value) => "<c r=\"{$ref}\" s=\"{$style}\"><v>{$value}</v></c>",
                    default => "<c r=\"{$ref}\" s=\"{$style}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">"
                        . htmlspecialchars(preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) $value), ENT_XML1 | ENT_QUOTES, 'UTF-8')
                        . '</t></is></c>',
                };
            }

            return $data ? "<row r=\"{$r}\" ht=\"22\" customHeight=\"1\">{$cells}</row>" : "<row r=\"{$r}\">{$cells}</row>";
        };

        $body = '';
        $r = self::FIRST_DATA_ROW;
        foreach ($rows as $values) {
            $body .= $rowXml($r++, $values, true);
        }
        $lastDataRow = max(self::FIRST_DATA_ROW - 1, $r - 1);
        // Sisa baris kosong template tetap ada (template asli sampai baris 1000).
        for (; $r <= self::TEMPLATE_LAST_ROW; $r++) {
            $body .= $rowXml($r, [], false);
        }

        $head = substr($sheet, 0, $start);
        // Header kuning (style 3) jadi putih (style 2): font & garis bawah sama, tanpa warna. Header sedikit lebih tinggi.
        $head = str_replace(' s="' . self::YELLOW_HEADER_STYLE . '"', ' s="' . self::PLAIN_HEADER_STYLE . '"', $head);
        $head = str_replace('<row r="2">', '<row r="2" ht="30" customHeight="1">', $head);
        // Header tetap terlihat saat scroll (freeze baris 1–2).
        $head = str_replace('<sheetView workbookViewId="0"/>', '<sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A3" sqref="A3"/></sheetView>', $head);
        // Lebar kolom menyesuaikan isi.
        $cols = collect(self::COLUMN_WIDTHS)->map(function (float $width, string $col): string {
            $i = ord($col) - 64;

            return "<col min=\"{$i}\" max=\"{$i}\" width=\"{$width}\" customWidth=\"1\"/>";
        })->implode('');
        $head = str_replace('<sheetData>', "<cols>{$cols}</cols><sheetData>", $head);

        // Filter di baris header (A2:N baris data terakhir).
        // Lalu siap cetak: landscape, semua kolom muat satu lebar halaman.
        $tail = str_replace('</sheetData>', '</sheetData><autoFilter ref="A2:N' . $lastDataRow . '"/>'
            . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>', substr($sheet, $end));
        $head = str_replace('<outlinePr summaryBelow="0" summaryRight="0"/>', '<outlinePr summaryBelow="0" summaryRight="0"/><pageSetUpPr fitToPage="1"/>', $head);

        $zip->addFromString(self::SHEET, $head . $body . $tail);
        $zip->addFromString(self::WORKBOOK, str_replace(
            '<definedNames/>',
            '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">Sheet1!$A$2:$N$' . $lastDataRow . '</definedName></definedNames>',
            $zip->getFromName(self::WORKBOOK),
        ));
        $zip->close();
    }

    /** Tambah gaya sel data (garis tipis abu-abu, format tanggal & angka) ke styles.xml template. */
    private static function tidyStyles(string $styles): string
    {
        $styles = preg_replace('/(<styleSheet[^>]*>)/', '$1<numFmts count="2"><numFmt numFmtId="164" formatCode="dd mmm yyyy"/><numFmt numFmtId="165" formatCode="0.0"/></numFmts>', $styles, 1);

        $line = '<color rgb="FFD9D9D9"/>';
        $styles = str_replace('<borders count="2">', '<borders count="3">', $styles);
        $styles = str_replace('</borders>', "<border><left style=\"thin\">{$line}</left><right style=\"thin\">{$line}</right><top style=\"thin\">{$line}</top><bottom style=\"thin\">{$line}</bottom><diagonal/></border></borders>", $styles);

        $xf = fn (int $numFmt, string $align) => "<xf borderId=\"2\" fillId=\"0\" fontId=\"1\" numFmtId=\"{$numFmt}\" xfId=\"0\" applyAlignment=\"1\" applyBorder=\"1\" applyFont=\"1\" applyNumberFormat=\"1\"><alignment horizontal=\"{$align}\" vertical=\"center\" shrinkToFit=\"0\" wrapText=\"0\"/></xf>";
        $styles = str_replace('<cellXfs count="4">', '<cellXfs count="8">', $styles);

        return str_replace('</cellXfs>', $xf(0, 'left') . $xf(164, 'center') . $xf(1, 'center') . $xf(165, 'center') . '</cellXfs>', $styles);
    }

    /** Tanggal → nomor seri Excel (1900 date system). */
    private static function excelDate(\DateTimeInterface $date): int
    {
        // Ambil tanggal kalendernya saja (sesuai zona waktu tanggal tsb), lalu hitung hari sejak 1970-01-01.
        return intdiv(Carbon::parse($date->format('Y-m-d'), 'UTC')->getTimestamp(), 86400) + 25569;
    }

    public function export(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'isoc-chapter-');
        static::fillTemplate(
            $this->getFilteredSortedTableQuery()->lazy(200)->map(fn (LearningEvent $event) => static::templateRow($event)),
            $path,
        );

        $filter = $this->getTableFilterState('periode') ?? [];
        $suffix = collect([$filter['from'] ?? null, $filter['until'] ?? null])->filter()
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))->implode('_sd_') ?: 'semua';

        return response()
            ->download($path, "chapter-activity_{$suffix}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
            ->deleteFileAfterSend(true);
    }
}
