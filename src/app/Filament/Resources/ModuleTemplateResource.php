<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\ModuleTemplateResource\Pages;
use App\Models\ModuleTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ModuleTemplateResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = ModuleTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Konten & Penilaian';

    protected static ?string $navigationLabel = 'Materi Event';

    protected static ?string $modelLabel = 'Materi Event';

    protected static ?string $pluralModelLabel = 'Materi Event';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Materi Event')
                ->schema([
                    Forms\Components\Hidden::make('created_by')->default(fn () => auth()->id()),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Materi Event')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('audience')
                        ->label('Tipe Materi')
                        ->options(ModuleTemplate::AUDIENCES)
                        ->default(ModuleTemplate::AUDIENCE_PESERTA)
                        ->required()
                        ->native(false)
                        ->helperText('Peserta: dipilih admin daerah di wizard event dan menjadi modul/tes peserta. Tutor: tampil di halaman ToT Awal tutor.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif / bisa dipilih admin')
                        ->default(true),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('purpose')
                        ->label('Tujuan Materi')
                        ->helperText('Jelaskan tujuan pembelajaran dan hasil yang diharapkan dari materi event ini.')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('meeting_count')
                        ->label('Jumlah Pertemuan')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(30)
                        ->default(1)
                        ->live(onBlur: true)
                        ->helperText('Sistem akan membuat daftar pertemuan. Lengkapi isi pertemuan dan materi dari menu Pertemuan & Materi.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Materi Event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ModuleTemplate::AUDIENCES[$state] ?? $state)
                    ->color(fn (?string $state) => $state === ModuleTemplate::AUDIENCE_TUTOR ? 'warning' : 'success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purpose')->label('Tujuan')->limit(55)->toggleable(),
                Tables\Columns\TextColumn::make('meeting_count')->label('Target Pertemuan')->sortable(),
                Tables\Columns\TextColumn::make('learning_meetings_count')->counts('learningMeetings')->label('Terbuat'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Tipe Materi')
                    ->options(ModuleTemplate::AUDIENCES),
            ])
            ->recordUrl(fn (ModuleTemplate $record) => static::getUrl('edit', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-play-circle')
                    ->color('info')
                    ->url(fn (ModuleTemplate $record) => \App\Filament\Pages\MaterialPreview::getUrlForTemplate($record)),
                Tables\Actions\EditAction::make()->label('Buka'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleTemplates::route('/'),
            'create' => Pages\CreateModuleTemplate::route('/create'),
            'edit' => Pages\EditModuleTemplate::route('/{record}/edit'),
        ];
    }
}
