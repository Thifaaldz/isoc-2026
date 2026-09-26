<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\RoleScoped;
use App\Filament\Resources\EvidenceResource\Pages;
use App\Models\Evidence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvidenceResource extends Resource
{
    use RoleScoped;

    protected static ?string $model = Evidence::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?string $modelLabel = 'Bukti Dukung';

    protected static ?string $pluralModelLabel = 'Bukti Dukung';

    protected static ?int $navigationSort = 2;

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
        return 'school';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('school_id')->label('Sekolah')->relationship('school', 'name')->searchable()->preload()->required(),
            Forms\Components\Select::make('type')->label('Jenis bukti')->options(\App\Models\Evidence::TYPES)->required(),
            Forms\Components\FileUpload::make('file_path')->label('Berkas')->directory('evidences'),
            Forms\Components\TextInput::make('link')->label('Tautan (opsional)')->url(),
            Forms\Components\Select::make('status')->label('Status')->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])->default('pending'),
            Forms\Components\Textarea::make('review_notes')->label('Catatan verifikasi'),
            Forms\Components\Hidden::make('uploaded_by')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->label('Sekolah')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Jenis')->formatStateUsing(fn ($state) => \App\Models\Evidence::TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => match ($state) { 'approved' => 'success', 'rejected' => 'danger', default => 'warning' }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')->label('Setujui')->icon('heroicon-o-check')->color('success')
                    ->visible(fn ($record) => in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && $record->status !== 'approved')
                    ->action(fn ($record) => $record->update(['status' => 'approved', 'verified_by' => auth()->id(), 'verified_at' => now()])),
                Tables\Actions\Action::make('reject')->label('Tolak')->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn ($record) => in_array(auth()->user()?->role, [UserRole::SuperAdmin, UserRole::Admin], true) && $record->status !== 'rejected')
                    ->form([Forms\Components\Textarea::make('review_notes')->label('Alasan penolakan')->required()])
                    ->action(fn ($record, array $data) => $record->update(['status' => 'rejected', 'review_notes' => $data['review_notes'], 'verified_by' => auth()->id(), 'verified_at' => now()])),
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
            'index' => Pages\ManageEvidence::route('/'),
        ];
    }
}
