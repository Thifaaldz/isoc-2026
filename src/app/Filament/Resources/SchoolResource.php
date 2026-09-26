<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\SchoolResource\Pages;
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

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah / Lokasi';

    protected static ?int $navigationSort = 1;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor, UserRole::Peserta];
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
            Forms\Components\TextInput::make('city')->label('Kota'),
            Forms\Components\TextInput::make('province')->label('Provinsi'),
            Forms\Components\Textarea::make('address')->label('Alamat'),
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
                Tables\Columns\TextColumn::make('city')->label('Kota')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('training_date')->label('Tanggal Pelatihan')->searchable()->sortable()->date(),
                Tables\Columns\TextColumn::make('participants_count')->counts('participants')->label('Peserta'),
                Tables\Columns\TextColumn::make('tutors_count')->counts('tutors')->label('Tutor'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
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
            'index' => Pages\ManageSchool::route('/'),
        ];
    }
}
