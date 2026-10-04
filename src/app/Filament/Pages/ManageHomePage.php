<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\Partner;
use App\Support\HomePageContent;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/** Admin RTIK Pusat: kelola isi halaman utama (hero, tentang, misi, pengurus, program, event, mitra, footer). */
class ManageHomePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Halaman Utama';

    protected static ?string $title = 'Kelola Halaman Utama';

    protected static ?string $slug = 'halaman-utama';

    protected static string $view = 'filament.pages.manage-home-page';

    /** Ikon Material Symbols yang bisa dipilih untuk kartu misi & program. */
    private const ICONS = [
        'school' => 'Sekolah / pendidikan', 'auto_stories' => 'Buku', 'menu_book' => 'Modul', 'biotech' => 'Penelitian', 'science' => 'Sains',
        'volunteer_activism' => 'Pengabdian', 'live_tv' => 'Webinar', 'public' => 'Internet / global', 'hub' => 'Konektivitas', 'wifi' => 'Wi-Fi',
        'security' => 'Keamanan', 'verified_user' => 'Terverifikasi', 'policy' => 'Kebijakan', 'groups' => 'Komunitas', 'diversity_3' => 'Kolaborasi',
        'handshake' => 'Kemitraan', 'campaign' => 'Kampanye', 'lightbulb' => 'Ide', 'workspace_premium' => 'Penghargaan', 'devices' => 'Perangkat',
    ];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public function mount(): void
    {
        $this->form->fill(HomePageContent::get());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Lihat Halaman Utama')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(route('home'), shouldOpenInNewTab: true),
            Action::make('importPartners')
                ->label('Ambil dari Mitra Event')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Daftar mitra nasional di formulir diganti dengan semua mitra aktif dari menu Mitra Event beserta logonya. Klik "Simpan Perubahan" setelahnya.')
                ->action(function (): void {
                    $partners = Partner::query()->where('status', 'active')->orderBy('name')->get()
                        ->mapWithKeys(fn (Partner $partner) => [(string) Str::uuid() => [
                            'name' => $partner->name,
                            'url' => $partner->website_url,
                            // State FileUpload berbentuk [uuid => path].
                            'logo' => ($logo = $partner->logo_path ?: $partner->logo_url) ? [(string) Str::uuid() => $logo] : [],
                        ]])->all();

                    $this->data['partners']['national'] = $partners;

                    Notification::make()->title(count($partners) . ' mitra disalin')->body('Periksa lalu klik "Simpan Perubahan".')->success()->send();
                }),
            Action::make('reset')
                ->label('Kembalikan Default')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Semua perubahan halaman utama dihapus dan kembali ke isi bawaan (mengikuti isoc.id).')
                ->action(function (): void {
                    HomePageContent::reset();
                    $this->form->fill(HomePageContent::get());
                    Notification::make()->title('Halaman utama dikembalikan ke default')->success()->send();
                }),
        ];
    }

    public function save(): void
    {
        HomePageContent::save($this->form->getState());

        Notification::make()
            ->title('Halaman utama disimpan')
            ->body('Perubahan langsung tampil di halaman utama.')
            ->success()
            ->send();
    }

    private function icon(string $name = 'icon'): Forms\Components\Select
    {
        return Forms\Components\Select::make($name)
            ->label('Ikon')
            ->options(collect(self::ICONS)->mapWithKeys(fn ($label, $icon) => [$icon => "{$label} ({$icon})"]))
            ->searchable()
            ->default('school');
    }

    private function image(string $name, string $label): Forms\Components\FileUpload
    {
        return Forms\Components\FileUpload::make($name)
            ->label($label)
            ->image()
            ->imageEditor()
            ->disk('public')
            ->directory('homepage')
            ->maxSize(4096);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('Halaman Utama')->persistTabInQueryString()->tabs([
                    Forms\Components\Tabs\Tab::make('Hero')->icon('heroicon-o-sparkles')->schema([
                        Forms\Components\TextInput::make('hero.eyebrow')->label('Label kecil di atas judul')->maxLength(80),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('hero.headline_before')->label('Judul (awal)')->required(),
                            Forms\Components\TextInput::make('hero.headline_highlight')->label('Kata berwarna biru'),
                            Forms\Components\TextInput::make('hero.headline_after')->label('Judul (akhir)'),
                        ]),
                        Forms\Components\Textarea::make('hero.description')->label('Deskripsi')->rows(3),
                        $this->image('hero.image', 'Gambar latar (opsional)')->helperText('Kosongkan untuk memakai latar pola biru bawaan.'),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('hero.primary_button_text')->label('Tombol utama'),
                            Forms\Components\TextInput::make('hero.primary_button_url')->label('Link tombol utama')->placeholder('/events'),
                            Forms\Components\TextInput::make('hero.secondary_button_text')->label('Tombol kedua'),
                            Forms\Components\TextInput::make('hero.secondary_button_url')->label('Link tombol kedua')->placeholder('/login'),
                        ]),
                    ]),
                    Forms\Components\Tabs\Tab::make('Tentang Kami')->icon('heroicon-o-information-circle')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('about.eyebrow')->label('Label kecil'),
                            Forms\Components\TextInput::make('about.title')->label('Judul')->required(),
                        ]),
                        Forms\Components\Textarea::make('about.description')->label('Deskripsi')->rows(4),
                        Forms\Components\TextInput::make('about.vision')->label('Visi (teks biru tebal)'),
                        $this->image('about.image', 'Foto (opsional)')->helperText('Kosongkan untuk menampilkan logo ISOC.'),
                    ]),
                    Forms\Components\Tabs\Tab::make('Misi')->icon('heroicon-o-flag')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('mission.eyebrow')->label('Label kecil'),
                            Forms\Components\TextInput::make('mission.title')->label('Judul')->required(),
                        ]),
                        Forms\Components\Repeater::make('mission.pillars')->label('Pilar')
                            ->schema([$this->icon(), Forms\Components\TextInput::make('title')->label('Judul')->required(), Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(2)->columnSpanFull()])
                            ->columns(2)->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null)->reorderable()->defaultItems(0),
                    ]),
                    Forms\Components\Tabs\Tab::make('Pengurus')->icon('heroicon-o-user-group')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('board.eyebrow')->label('Label kecil'),
                            Forms\Components\TextInput::make('board.title')->label('Judul')->required(),
                        ]),
                        Forms\Components\Repeater::make('board.groups')->label('Kelompok pengurus')
                            ->schema([
                                Forms\Components\TextInput::make('name')->label('Nama kelompok')->required()->placeholder('Dewan Pengurus Harian'),
                                Forms\Components\Repeater::make('members')->label('Anggota')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')->label('Nama')->required(),
                                        Forms\Components\TextInput::make('role')->label('Jabatan'),
                                        $this->image('photo', 'Foto (opsional)')->avatar(),
                                    ])
                                    ->columns(3)->collapsible()->itemLabel(fn (array $state) => trim(($state['name'] ?? '') . ' · ' . ($state['role'] ?? ''), ' ·'))->reorderable()->defaultItems(0),
                            ])
                            ->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->reorderable()->defaultItems(0),
                    ]),
                    Forms\Components\Tabs\Tab::make('Program')->icon('heroicon-o-rectangle-stack')->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('programs.eyebrow')->label('Label kecil'),
                            Forms\Components\TextInput::make('programs.title')->label('Judul')->required(),
                        ]),
                        Forms\Components\Textarea::make('programs.description')->label('Deskripsi')->rows(2),
                        Forms\Components\Repeater::make('programs.items')->label('Kartu program')
                            ->schema([
                                $this->icon(),
                                Forms\Components\TextInput::make('title')->label('Judul')->required(),
                                Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(2)->columnSpanFull(),
                                Forms\Components\TagsInput::make('tags')->label('Tag (opsional)')->placeholder('Ketik lalu Enter'),
                                Forms\Components\Toggle::make('featured')->label('Kartu unggulan (lebar penuh, biru)')->live()->inline(false),
                                Forms\Components\TextInput::make('label')->label('Label kartu unggulan')->visible(fn (Forms\Get $get) => (bool) $get('featured')),
                            ])
                            ->columns(2)->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? null)->reorderable()->defaultItems(0),
                    ]),
                    Forms\Components\Tabs\Tab::make('Event')->icon('heroicon-o-calendar-days')->schema([
                        Forms\Components\Toggle::make('events.enabled')->label('Tampilkan seksi Event Mendatang')->helperText('Event diambil otomatis dari event yang sudah dipublish dan belum lewat.')->inline(false),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('events.eyebrow')->label('Label kecil'),
                            Forms\Components\TextInput::make('events.title')->label('Judul'),
                        ]),
                        Forms\Components\Textarea::make('events.description')->label('Deskripsi')->rows(2),
                        Forms\Components\TextInput::make('events.limit')->label('Jumlah event ditampilkan')->numeric()->minValue(1)->maxValue(12),
                    ]),
                    Forms\Components\Tabs\Tab::make('Mitra')->icon('heroicon-o-building-office-2')->schema([
                        Forms\Components\TextInput::make('partners.title')->label('Judul')->required(),
                        ...collect(['global' => 'Mitra global', 'national' => 'Mitra nasional'])->flatMap(fn ($label, $key) => [
                            Forms\Components\TextInput::make("partners.{$key}_label")->label("Label {$label}"),
                            Forms\Components\Repeater::make("partners.{$key}")->label($label)
                                ->schema([
                                    Forms\Components\TextInput::make('name')->label('Nama')->required(),
                                    Forms\Components\TextInput::make('url')->label('Website (opsional)')->url(),
                                    $this->image('logo', 'Logo (opsional)')->helperText('Tanpa logo, nama mitra yang ditampilkan.'),
                                ])
                                ->columns(3)->collapsible()->itemLabel(fn (array $state) => $state['name'] ?? null)->reorderable()->defaultItems(0),
                        ])->all(),
                    ]),
                    Forms\Components\Tabs\Tab::make('Footer & Kontak')->icon('heroicon-o-envelope')->schema([
                        Forms\Components\Textarea::make('footer.description')->label('Deskripsi organisasi')->rows(2),
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('footer.email')->label('Email sekretariat')->email(),
                            Forms\Components\TextInput::make('footer.location')->label('Lokasi'),
                            Forms\Components\TextInput::make('footer.instagram_handle')->label('Instagram (teks)')->placeholder('@isoc.id.jkt'),
                            Forms\Components\TextInput::make('footer.instagram_url')->label('Link Instagram')->url(),
                            Forms\Components\TextInput::make('footer.linkedin_url')->label('Link LinkedIn')->url(),
                            Forms\Components\TextInput::make('footer.copyright')->label('Teks hak cipta'),
                        ]),
                    ]),
                ]),
            ]);
    }
}
