<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Support\SchoolLocationFields;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SchoolResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah / Lokasi';

    protected static ?int $navigationSort = 6;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor];
    }

    public static function manageRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
    }

    public static function scopeType(): ?string
    {
        return 'school_self';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama Sekolah')->required(),
            Forms\Components\TextInput::make('npsn')->label('NPSN')->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('Jenjang')->options(['SMA' => 'SMA', 'SMK' => 'SMK'])->required()->default('SMA'),
            ...SchoolLocationFields::schema(),
            Forms\Components\Textarea::make('address')->label('Alamat'),
            Forms\Components\TextInput::make('maps_url')
                ->label('Link Google Maps')
                ->url()
                ->maxLength(255)
                ->placeholder('https://maps.google.com/...'),
            Forms\Components\TextInput::make('pic_name')->label('PIC Sekolah'),
            Forms\Components\TextInput::make('pic_phone')->label('Telepon PIC'),
            Forms\Components\TextInput::make('participant_target')->label('Target Peserta')->numeric()->default(100),
            Forms\Components\DatePicker::make('training_date')->label('Tanggal Pelatihan'),
            Forms\Components\Select::make('status')->label('Status')->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Jenjang')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('province')->label('Provinsi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->label('Kota')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('district')->label('Kecamatan')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('village')->label('Kelurahan')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('maps_url')
                    ->label('Maps')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka Maps' : '-')
                    ->url(fn (School $record) => $record->maps_url ?: null)
                    ->openUrlInNewTab()
                    ->icon(fn ($state) => filled($state) ? 'heroicon-o-map-pin' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('training_date')->label('Tanggal Pelatihan')->searchable()->sortable()->date(),
                Tables\Columns\TextColumn::make('participants_count')->counts('participants')->label('Peserta'),
                Tables\Columns\TextColumn::make('tutors_count')->counts('tutors')->label('Tutor'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenjang')
                    ->options(['SMA' => 'SMA', 'SMK' => 'SMK']),
                Tables\Filters\SelectFilter::make('province')
                    ->label('Provinsi')
                    ->options(fn () => static::scopeSchoolBuilder(School::query())->whereNotNull('province')->orderBy('province')->pluck('province', 'province')),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Kota / Kabupaten')
                    ->options(fn () => static::scopeSchoolBuilder(School::query())->whereNotNull('city')->orderBy('city')->pluck('city', 'city')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),
            ])
            ->actions([
                Tables\Actions\Action::make('open_maps')
                    ->label('Buka Maps')
                    ->icon('heroicon-o-map-pin')
                    ->url(fn (School $record) => $record->maps_url)
                    ->openUrlInNewTab()
                    ->visible(fn (School $record) => filled($record->maps_url)),
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
            'index' => Pages\ManageSchool::route('/'),
        ];
    }
}
