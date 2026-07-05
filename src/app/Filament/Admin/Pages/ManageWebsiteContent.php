<?php

namespace App\Filament\Admin\Pages;

use App\Models\WebsiteSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageWebsiteContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Website Content';

    protected static ?string $title = 'Website Content';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.manage-website-content';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = WebsiteSetting::current();

        $this->form->fill([
            'name' => $setting->name,
            'content' => WebsiteSetting::contentWithDefaults($setting->content),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Pengaturan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Tabs::make('Konten Website')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Identitas')
                            ->schema([
                                Forms\Components\Section::make('Identitas Situs')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.site.title')
                                            ->label('Judul Browser')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('content.site.logo_url')
                                            ->label('URL Logo Eksternal')
                                            ->helperText('Dipakai jika tidak ada upload logo.')
                                            ->url(),
                                        Forms\Components\FileUpload::make('content.site.logo_path')
                                            ->label('Upload Logo')
                                            ->disk('public')
                                            ->directory('website/logo')
                                            ->image()
                                            ->imageEditor()
                                            ->optimize('webp')
                                            ->maxSize(2048),
                                        Forms\Components\Textarea::make('content.site.footer_description')
                                            ->label('Deskripsi Footer')
                                            ->rows(4)
                                            ->required(),
                                        Forms\Components\TextInput::make('content.site.copyright')
                                            ->label('Copyright')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                $this->navigationRepeater('content.navigation', 'Menu Navigasi'),
                                $this->navigationRepeater('content.legal_links', 'Link Legal Footer'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Hero & About')
                            ->schema([
                                Forms\Components\Section::make('Hero')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.hero.eyebrow')
                                            ->label('Eyebrow')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.hero.title')
                                            ->label('Judul Hero')
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('content.hero.highlight')
                                            ->label('Kata Highlight'),
                                        Forms\Components\Textarea::make('content.hero.description')
                                            ->label('Deskripsi')
                                            ->rows(4)
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('content.hero.background_url')
                                            ->label('URL Background Hero Eksternal')
                                            ->helperText('Dipakai jika tidak ada upload background.')
                                            ->url()
                                            ->columnSpanFull(),
                                        Forms\Components\FileUpload::make('content.hero.background_path')
                                            ->label('Upload Background Hero')
                                            ->disk('public')
                                            ->directory('website/hero')
                                            ->image()
                                            ->imageEditor()
                                            ->optimize('webp')
                                            ->maxSize(4096)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Tentang Kami')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.about.eyebrow')
                                            ->label('Eyebrow')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.about.title')
                                            ->label('Judul')
                                            ->required(),
                                        Forms\Components\Textarea::make('content.about.description')
                                            ->label('Deskripsi')
                                            ->rows(4)
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('content.about.vision')
                                            ->label('Visi')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.about.image_url')
                                            ->label('URL Gambar Eksternal')
                                            ->helperText('Dipakai jika tidak ada upload gambar.')
                                            ->url(),
                                        Forms\Components\FileUpload::make('content.about.image_path')
                                            ->label('Upload Gambar')
                                            ->disk('public')
                                            ->directory('website/about')
                                            ->image()
                                            ->imageEditor()
                                            ->optimize('webp')
                                            ->maxSize(4096),
                                    ])
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Pilar')
                            ->schema([
                                Forms\Components\Section::make('Header Pilar')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.pillars.eyebrow')
                                            ->label('Eyebrow')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.pillars.title')
                                            ->label('Judul')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Repeater::make('content.pillars.items')
                                    ->label('Daftar Pilar')
                                    ->schema($this->iconTitleDescriptionFields())
                                    ->columns(3)
                                    ->defaultItems(3)
                                    ->reorderableWithButtons()
                                    ->collapsible(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Pengurus')
                            ->schema([
                                Forms\Components\Section::make('Header Pengurus')
                                    ->description('Daftar grup dan anggota pengurus dikelola di menu Website > Grup Pengurus dan Website > Anggota Pengurus.')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.management.eyebrow')
                                            ->label('Eyebrow')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.management.title')
                                            ->label('Judul')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                        Forms\Components\Tabs\Tab::make('Program')
                            ->schema([
                                Forms\Components\Section::make('Header Program')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.programs.eyebrow')
                                            ->label('Eyebrow')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.programs.title')
                                            ->label('Judul')
                                            ->required(),
                                        Forms\Components\Textarea::make('content.programs.description')
                                            ->label('Deskripsi')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Repeater::make('content.programs.items')
                                    ->label('Daftar Program')
                                    ->schema([
                                        Forms\Components\TextInput::make('icon')
                                            ->label('Material Icon')
                                            ->required(),
                                        Forms\Components\Select::make('style')
                                            ->label('Gaya Kartu')
                                            ->options([
                                                'wide' => 'Lebar dengan tag',
                                                'primary' => 'Primary biru',
                                                'compact' => 'Compact',
                                                'wide-icon' => 'Lebar dengan ikon besar',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('badge')
                                            ->label('Badge'),
                                        Forms\Components\TextInput::make('title')
                                            ->label('Judul')
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Deskripsi')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\TagsInput::make('tags')
                                            ->label('Tags')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->reorderableWithButtons()
                                    ->collapsible(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Mitra')
                            ->schema([
                                Forms\Components\Section::make('Header Mitra')
                                    ->description('Daftar grup dan data mitra dikelola di menu Website > Grup Mitra dan Website > Mitra.')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.partners.title')
                                            ->label('Judul Section Mitra')
                                            ->required(),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Footer')
                            ->schema([
                                Forms\Components\Repeater::make('content.socials')
                                    ->label('Media Sosial')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->label('Label')
                                            ->required(),
                                        Forms\Components\TextInput::make('icon')
                                            ->label('Material Icon'),
                                        Forms\Components\TextInput::make('url')
                                            ->label('URL')
                                            ->url()
                                            ->required(),
                                        Forms\Components\TextInput::make('image_url')
                                            ->label('URL Icon Gambar Eksternal')
                                            ->helperText('Dipakai jika tidak ada upload icon.')
                                            ->url(),
                                        Forms\Components\FileUpload::make('image_path')
                                            ->label('Upload Icon')
                                            ->disk('public')
                                            ->directory('website/socials')
                                            ->image()
                                            ->imageEditor()
                                            ->optimize('webp')
                                            ->maxSize(1024),
                                    ])
                                    ->columns(4)
                                    ->reorderableWithButtons()
                                    ->collapsible(),
                                Forms\Components\Section::make('Kontak')
                                    ->schema([
                                        Forms\Components\TextInput::make('content.contact.title')
                                            ->label('Judul Kontak')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.contact.email')
                                            ->label('Email')
                                            ->email()
                                            ->required(),
                                        Forms\Components\TextInput::make('content.contact.instagram')
                                            ->label('Instagram Label')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.contact.instagram_url')
                                            ->label('Instagram URL')
                                            ->url()
                                            ->required(),
                                        Forms\Components\TextInput::make('content.contact.address')
                                            ->label('Alamat')
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewWebsite')
                ->label('Lihat Website')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('/')
                ->openUrlInNewTab(),
            Action::make('resetDefaults')
                ->label('Reset Default')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->form->fill([
                        'name' => 'ISOC Jakarta',
                        'content' => WebsiteSetting::defaultContent(),
                    ]);
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        WebsiteSetting::current()->update([
            'name' => $state['name'],
            'content' => $state['content'],
        ]);

        Notification::make()
            ->title('Konten website berhasil disimpan')
            ->success()
            ->send();
    }

    protected function navigationRepeater(string $name, string $label): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($name)
            ->label($label)
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->label('Label')
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->required(),
            ])
            ->columns(2)
            ->reorderableWithButtons()
            ->collapsible();
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function iconTitleDescriptionFields(): array
    {
        return [
            Forms\Components\TextInput::make('icon')
                ->label('Material Icon')
                ->required(),
            Forms\Components\TextInput::make('title')
                ->label('Judul')
                ->required(),
            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->required(),
        ];
    }
}
