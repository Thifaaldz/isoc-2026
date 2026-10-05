<?php

namespace App\Filament\Resources;

use App\Filament\Support\UploadTypes;
use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\LearningEvent;
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

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Validasi & Sertifikat';

    protected static ?string $modelLabel = 'Payment Terms';

    protected static ?string $pluralModelLabel = 'Payment Terms';

    protected static ?int $navigationSort = 5;

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
        return 'payment';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('learning_event_id')
                ->label('Event / Lokus')
                ->default(fn ($livewire) => $livewire->eventId ?? null)
                ->options(fn () => static::scopedLearningEventOptions(LearningEvent::query())->pluck('title', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('school_id')
                ->label('Lokasi')
                ->options(fn () => static::scopedSchoolOptions())
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('term')
                ->label('Termin')
                ->options([1 => 'Termin-1 Persiapan & Publish', 2 => 'Termin-2 Laporan Final'])
                ->required(),
            Forms\Components\TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->required(),
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(['pending' => 'Menunggu', 'eligible' => 'Eligible', 'paid' => 'Dibayar', 'revision' => 'Revisi'])
                ->default('pending')
                ->required(),
            Forms\Components\DatePicker::make('due_at')->label('Deadline / Reminder'),
            Forms\Components\DatePicker::make('paid_at')->label('Tanggal bayar'),
            Forms\Components\FileUpload::make('invoice_path')
                ->acceptedFileTypes(UploadTypes::documents())->label('Invoice')->disk('public')->directory('payments'),
            Forms\Components\FileUpload::make('receipt_path')
                ->acceptedFileTypes(UploadTypes::documents())->label('Kuitansi')->disk('public')->directory('payments'),
            Forms\Components\Repeater::make('checklist')
                ->label('Checklist Termin')
                ->schema([
                    Forms\Components\TextInput::make('label')->label('Item')->required(),
                    Forms\Components\Toggle::make('done')->label('Selesai'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('learningEvent.title')->label('Event')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('school.name')->label('Lokasi')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('term')->label('Termin')->badge(),
                Tables\Columns\TextColumn::make('amount')->label('Nominal')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => match ($state) {
                    'paid' => 'success',
                    'eligible' => 'info',
                    'revision' => 'warning',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('due_at')->label('Deadline')->date()->sortable(),
                Tables\Columns\TextColumn::make('approved_at')->label('Disetujui')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(55)
                    ->tooltip(fn (Payment $record) => $record->notes)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('term')
                    ->label('Termin')
                    ->options([1 => 'Termin-1', 2 => 'Termin-2']),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(['pending' => 'Menunggu', 'eligible' => 'Eligible', 'paid' => 'Dibayar', 'revision' => 'Revisi']),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Payment $record) => auth()->user()?->role === UserRole::SuperAdmin && $record->status !== 'paid')
                    ->action(fn (Payment $record) => $record->update([
                        'status' => 'eligible',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ])),
                Tables\Actions\Action::make('markPaid')
                    ->label('Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('info')
                    ->visible(fn (Payment $record) => auth()->user()?->role === UserRole::SuperAdmin && $record->status !== 'paid')
                    ->action(fn (Payment $record) => $record->update(['status' => 'paid', 'paid_at' => now()])),
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
