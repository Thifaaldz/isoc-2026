<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?string $modelLabel = 'Pembayaran Termin';

    protected static ?string $pluralModelLabel = 'Pembayaran Termin';

    protected static ?int $navigationSort = 4;

    public static function viewRoles(): array
    {
        return [UserRole::SuperAdmin, UserRole::Admin];
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
        return $form->schema([
            Forms\Components\Select::make('school_id')->label('Sekolah')->relationship('school', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('term')->label('Termin')->options([1 => 'Termin 1', 2 => 'Termin 2'])->required(),
            Forms\Components\TextInput::make('amount')->label('Nominal (Rp)')->numeric()->prefix('Rp'),
            Forms\Components\Select::make('status')->label('Status')->options(['pending' => 'Menunggu', 'eligible' => 'Layak dibayar', 'paid' => 'Dibayar'])->default('pending'),
            Forms\Components\DatePicker::make('paid_at')->label('Tanggal bayar'),
            Forms\Components\Textarea::make('notes')->label('Catatan'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('term')->label('Termin')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->searchable()->sortable()->money('IDR'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('paid_at')->label('Dibayar')->searchable()->sortable()->date(),
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
            'index' => Pages\ManagePayment::route('/'),
        ];
    }
}
