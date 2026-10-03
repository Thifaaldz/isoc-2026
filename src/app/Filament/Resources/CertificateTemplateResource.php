<?php

namespace App\Filament\Resources;

use App\Filament\Support\UploadTypes;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\CertificateTemplateResource\Pages;
use App\Models\CertificateTemplate;
use Filament\Forms;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateTemplateResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = CertificateTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $modelLabel = 'Desain Sertifikat';

    protected static ?string $pluralModelLabel = 'Desain Sertifikat';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function scopeType(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Kanvas')
                    ->description('Atur ukuran sertifikat dan background desain.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Desain')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('orientation')
                            ->label('Orientasi')
                            ->options([
                                'landscape' => 'Landscape',
                                'portrait' => 'Portrait',
                            ])
                            ->default('landscape')
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Jadikan desain default')
                            ->default(false),
                        Forms\Components\TextInput::make('width_mm')
                            ->label('Lebar Kanvas (mm)')
                            ->numeric()
                            ->default(297)
                            ->required(),
                        Forms\Components\TextInput::make('height_mm')
                            ->label('Tinggi Kanvas (mm)')
                            ->numeric()
                            ->default(210)
                            ->required(),
                        Forms\Components\ColorPicker::make('background_color')
                            ->label('Warna Background')
                            ->default('#ffffff'),
                        Forms\Components\FileUpload::make('background_image')
                            ->label('Background Gambar')
                            ->image()
                            ->acceptedFileTypes(UploadTypes::IMAGES)
                            ->imageEditor()
                            ->disk('public')
                            ->directory('certificate-backgrounds')
                            ->visibility('public'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Elemen Sertifikat')
                    ->description('Elemen bisa diseret untuk urutan layer. Posisi X/Y dan ukuran memakai milimeter dari kiri atas kanvas.')
                    ->schema([
                        Forms\Components\Repeater::make('elements')
                            ->label('Daftar Elemen')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Jenis Elemen')
                                    ->options(static::elementTypeOptions())
                                    ->default('custom_text')
                                    ->required()
                                    ->live()
                                    ->native(false),
                                Forms\Components\TextInput::make('label')
                                    ->label('Nama Layer')
                                    ->maxLength(100),
                                Forms\Components\Textarea::make('content')
                                    ->label('Tulisan')
                                    ->rows(3)
                                    ->helperText('Bisa pakai token: {{participant_name}}, {{event_title}}, {{certificate_number}}, {{issued_date}}, {{school_name}}, {{class_name}}.')
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), ['custom_text', 'signature'], true))
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Upload Gambar')
                                    ->image()
                                    ->acceptedFileTypes(UploadTypes::IMAGES)
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('certificate-elements')
                                    ->visibility('public')
                                    ->visible(fn (Forms\Get $get) => in_array($get('type'), static::imageElementTypes(), true))
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('x')
                                    ->label('X')
                                    ->numeric()
                                    ->default(20)
                                    ->required(),
                                Forms\Components\TextInput::make('y')
                                    ->label('Y')
                                    ->numeric()
                                    ->default(20)
                                    ->required(),
                                Forms\Components\TextInput::make('width')
                                    ->label('Lebar')
                                    ->numeric()
                                    ->default(80)
                                    ->required(),
                                Forms\Components\TextInput::make('height')
                                    ->label('Tinggi')
                                    ->numeric()
                                    ->default(15)
                                    ->required(),
                                Forms\Components\TextInput::make('font_size')
                                    ->label('Ukuran Font')
                                    ->numeric()
                                    ->default(14)
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('type'), array_merge(static::imageElementTypes(), ['event_partner_logos', 'white_box', 'qr_code']), true)),
                                Forms\Components\Select::make('font_weight')
                                    ->label('Ketebalan')
                                    ->options([
                                        '400' => 'Normal',
                                        '600' => 'Semi Bold',
                                        '700' => 'Bold',
                                    ])
                                    ->default('400')
                                    ->native(false)
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('type'), array_merge(static::imageElementTypes(), ['event_partner_logos', 'white_box', 'qr_code']), true)),
                                Forms\Components\Select::make('align')
                                    ->label('Rata Teks')
                                    ->options([
                                        'left' => 'Kiri',
                                        'center' => 'Tengah',
                                        'right' => 'Kanan',
                                    ])
                                    ->default('center')
                                    ->native(false)
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('type'), array_merge(static::imageElementTypes(), ['event_partner_logos', 'white_box', 'qr_code']), true)),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Warna Teks')
                                    ->default('#111827')
                                    ->visible(fn (Forms\Get $get) => ! in_array($get('type'), array_merge(static::imageElementTypes(), ['event_partner_logos', 'white_box', 'qr_code']), true)),
                            ])
                            ->defaultItems(0)
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? (static::elementTypeOptions()[$state['type'] ?? 'custom_text'] ?? 'Elemen'))
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Desain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('orientation')->label('Orientasi')->badge(),
                Tables\Columns\TextColumn::make('elements')->label('Elemen')->formatStateUsing(fn ($state) => static::countElements($state)),
                Tables\Columns\IconColumn::make('is_default')->label('Default')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default'),
            ])
            ->actions([
                Tables\Actions\Action::make('studio')
                    ->label('Studio')
                    ->icon('heroicon-o-sparkles')
                    ->url(fn (CertificateTemplate $record): string => route(
                        'filament.' . Filament::getCurrentPanel()->getId() . '.pages.certificate-design-studio',
                        ['templateId' => $record->id],
                    )),
                Tables\Actions\Action::make('previewTemplate')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CertificateTemplate $record): string => route('certificate-templates.preview-pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCertificateTemplate::route('/'),
        ];
    }

    public static function elementTypeOptions(): array
    {
        return [
            'custom_text' => 'Tulisan Bebas',
            'participant_name' => 'Nama Peserta',
            'event_title' => 'Nama Event',
            'certificate_number' => 'Nomor Sertifikat',
            'issued_date' => 'Tanggal Terbit',
            'school_name' => 'Nama Sekolah',
            'class_name' => 'Nama Kelas',
            'tutor_name' => 'Nama Tutor',
            'tutor_institution' => 'Lembaga Tutor',
            'organizer_name' => 'Nama Lembaga',
            'sena_logo' => 'Logo Sena',
            'logo' => 'Logo Utama',
            'partner_logo' => 'Logo Mitra',
            'event_partner_logos' => 'Logo Mitra Event',
            'white_box' => 'Kotak Penutup / Shape',
            'uploaded_logo' => 'Upload Logo',
            'image' => 'Gambar Bebas / Logo Tambahan',
            'signature' => 'Teks Tanda Tangan',
            'signature_image' => 'Gambar Tanda Tangan',
            'uploaded_signature' => 'Upload Tanda Tangan',
            'signature_line' => 'Garis Tanda Tangan',
            'qr_code' => 'QR Verifikasi',
        ];
    }

    public static function imageElementTypes(): array
    {
        return ['sena_logo', 'logo', 'partner_logo', 'uploaded_logo', 'signature_image', 'uploaded_signature', 'image'];
    }

    private static function countElements(mixed $state): int
    {
        if (is_string($state)) {
            $state = json_decode($state, true);
        }

        return is_countable($state) ? count($state) : 0;
    }
}
