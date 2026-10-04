<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\WagGroupResource\Pages;
use App\Models\WagGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WagGroupResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = WagGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Absensi & Peserta';

    protected static ?string $modelLabel = 'WAG Mentoring';

    protected static ?string $pluralModelLabel = 'WAG Mentoring';

    protected static ?int $navigationSort = 5;

    /** Grup WhatsApp hanya dibuat Admin ISOC (RTIK Pusat); Admin RTIK Daerah dan tutor tidak membuat link WhatsApp. */
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
        return 'school';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')->label('Lokasi Event')->options(fn () => static::scopedSchoolOptions())->searchable()->preload()->required()
                ->helperText('Link grup tampil di dashboard peserta event pada lokasi ini.'),
            Forms\Components\TextInput::make('name')->label('Nama grup')->required(),
            Forms\Components\TextInput::make('invite_link')->label('Tautan undangan')->url(),
            Forms\Components\TextInput::make('member_count')->label('Jumlah anggota')->numeric()->default(0),
            Forms\Components\TextInput::make('active_members')->label('Anggota aktif')->numeric()->default(0),
            Forms\Components\Select::make('status')->label('Status')->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi Event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Grup')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('invite_link')
                    ->label('Link WhatsApp')
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka WhatsApp' : '-')
                    ->url(fn (WagGroup $record) => $record->invite_link ?: null)
                    ->openUrlInNewTab()
                    ->icon(fn ($state) => filled($state) ? 'heroicon-o-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('member_count')->label('Anggota')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('active_members')->label('Aktif')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('school_id')
                    ->label('Lokasi Event')
                    ->options(fn () => static::scopedSchoolOptions())
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active' => 'Aktif', 'inactive' => 'Nonaktif']),
            ])
            ->actions([
                Tables\Actions\Action::make('open_whatsapp')
                    ->label('Buka WAG')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (WagGroup $record) => $record->invite_link)
                    ->openUrlInNewTab()
                    ->visible(fn (WagGroup $record) => filled($record->invite_link)),
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
            'index' => Pages\ManageWagGroup::route('/'),
        ];
    }
}
