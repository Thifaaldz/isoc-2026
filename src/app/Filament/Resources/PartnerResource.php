<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Mitra';

    protected static ?string $navigationLabel = 'Mitra Event';

    protected static ?string $modelLabel = 'Mitra';

    protected static ?string $pluralModelLabel = 'Mitra';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil Mitra')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Mitra')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'international' => 'Internasional',
                            'national' => 'Nasional',
                            'local' => 'Lokal',
                            'school' => 'Sekolah / Kampus',
                            'community' => 'Komunitas',
                        ])
                        ->default('national')
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Keterangan')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('website_url')
                        ->label('Website')
                        ->url()
                        ->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])
                        ->default('active')
                        ->required()
                        ->native(false),
                ])
                ->columns(2),
            Forms\Components\Section::make('Logo')
                ->description('Logo ini akan otomatis muncul di sertifikat jika mitra dipilih pada event.')
                ->schema([
                    Forms\Components\FileUpload::make('logo_path')
                        ->label('Upload Logo Mitra')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('partners')
                        ->visibility('public')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('logo_url')
                        ->label('Atau URL Logo')
                        ->url()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (Partner $record) => $record->logo_url ?: asset('images/sena-symbol.png')),
                Tables\Columns\TextColumn::make('name')->label('Mitra')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->badge()->sortable(),
                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->limit(28)
                    ->url(fn (?string $state) => $state)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('learning_events_count')->counts('learningEvents')->label('Event'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'international' => 'Internasional',
                        'national' => 'Nasional',
                        'local' => 'Lokal',
                        'school' => 'Sekolah / Kampus',
                        'community' => 'Komunitas',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),
            ])
            ->actions([
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
            'index' => Pages\ManagePartner::route('/'),
        ];
    }
}
