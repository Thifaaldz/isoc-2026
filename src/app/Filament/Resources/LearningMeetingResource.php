<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\LearningMeetingResource\Pages;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LearningMeetingResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = LearningMeeting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Konten & Penilaian';

    protected static ?string $navigationLabel = 'Pertemuan & Materi';

    protected static ?string $modelLabel = 'Pertemuan & Materi';

    protected static ?string $pluralModelLabel = 'Pertemuan & Materi';

    protected static ?int $navigationSort = 2;

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
        return 'learning_meeting';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pilih Sumber Pertemuan')
                ->schema([
                    Forms\Components\Select::make('module_template_id')
                        ->label('Materi Event')
                        ->options(fn () => ModuleTemplate::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->default(fn () => request()->integer('module_template_id') ?: null)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Pertemuan ini akan menjadi bagian dari Materi Event yang dipilih.'),
                ])
                ->columns(1),

            Forms\Components\Section::make('Isi Pertemuan')
                ->schema([
                    Forms\Components\TextInput::make('order')->label('Urutan')->numeric()->default(1)->required(),
                    Forms\Components\TextInput::make('title')->label('Judul pertemuan')->required(),
                    Forms\Components\TextInput::make('duration_minutes')
                        ->label('Lama Waktu Materi (menit)')
                        ->numeric()
                        ->minValue(5)
                        ->default(25)
                        ->required()
                        ->helperText('Dipakai otomatis untuk membuat rundown event saat materi ini dipilih.'),
                    Forms\Components\Textarea::make('description')->label('Deskripsi')->rows(4)->columnSpanFull(),
                    Forms\Components\TextInput::make('task_title')->label('Judul Tugas')->columnSpanFull(),
                    Forms\Components\Textarea::make('task_description')->label('Instruksi Tugas')->rows(4)->columnSpanFull(),
                    Forms\Components\DateTimePicker::make('starts_at')->label('Jadwal'),
                    Forms\Components\Toggle::make('is_published')->label('Tampil di peserta')->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Materi Pertemuan')
                ->description('Tambahkan PDF, PPT, video upload, atau link video langsung dari pertemuan ini.')
                ->schema([
                    Forms\Components\Repeater::make('materials')
                        ->relationship()
                        ->label('Daftar Materi')
                        ->schema([
                            Forms\Components\TextInput::make('order')
                                ->label('Urutan')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            Forms\Components\TextInput::make('title')
                                ->label('Judul materi')
                                ->required(),
                            Forms\Components\Select::make('type')
                                ->label('Jenis')
                                ->options(LearningMaterial::TYPES)
                                ->default('pdf')
                                ->required(),
                            Forms\Components\TextInput::make('duration_minutes')
                                ->label('Durasi materi (menit)')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Opsional. Jika kosong, rundown memakai durasi pertemuan.'),
                            Forms\Components\FileUpload::make('file_path')
                                ->label('Upload file materi')
                                ->directory('learning-materials')
                                ->downloadable()
                                ->openable()
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/vnd.ms-powerpoint',
                                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                    'video/mp4',
                                    'video/webm',
                                    'video/ogg',
                                ]),
                            Forms\Components\TextInput::make('external_url')
                                ->label('Link video / materi')
                                ->url(),
                            Forms\Components\Toggle::make('is_published')
                                ->label('Tampil di peserta')
                                ->default(true),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Materi')
                        ->addActionLabel('Tambah Materi')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')->label('Urutan')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Pertemuan')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Durasi')->suffix(' menit')->sortable(),
                Tables\Columns\TextColumn::make('task_title')->label('Tugas')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('materials_count')->counts('materials')->label('Materi')->sortable(),
                Tables\Columns\TextColumn::make('assessments_count')->counts('assessments')->label('Kuis')->sortable(),
                Tables\Columns\TextColumn::make('starts_at')->label('Jadwal')->dateTime()->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Publish')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publish'),
            ])
            ->defaultSort('order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->whereNotNull('module_template_id');
        $templateId = request()->integer('materi_event');

        return $templateId ? $query->where('module_template_id', $templateId) : $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLearningMeeting::route('/'),
            'create' => Pages\CreateLearningMeeting::route('/create'),
            'edit' => Pages\EditLearningMeeting::route('/{record}/edit'),
        ];
    }
}
