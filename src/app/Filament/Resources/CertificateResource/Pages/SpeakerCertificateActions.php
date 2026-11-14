<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Models\LearningEvent;
use App\Models\User;
use App\Services\TutorCertificateService;
use App\Support\SpeakerCertificates;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

/** Aksi Admin RTIK Pusat untuk menambah / menghapus sertifikat narasumber manual bagi tutor dan fasilitator. */
class SpeakerCertificateActions
{
    public static function canManage(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    /** @param ManageTutorCertificate|ManageFacilitatorCertificate $page */
    public static function create(string $role, $page): Action
    {
        $isTutor = $role === SpeakerCertificates::ROLE_TUTOR;

        return Action::make('addSpeakerCertificate')
            ->label($isTutor ? 'Tambah Sertifikat Tutor' : 'Tambah Sertifikat Fasilitator')
            ->icon('heroicon-o-plus')
            ->visible(fn () => static::canManage())
            ->modalHeading($isTutor ? 'Tambah Sertifikat Tutor' : 'Tambah Sertifikat Fasilitator')
            ->modalDescription('Sertifikat narasumber langsung terbit untuk akun yang dipilih. Nomor sertifikat dibuat otomatis oleh sistem.')
            ->modalSubmitActionLabel('Tambah')
            ->fillForm(fn () => ['learning_event_id' => $page->eventId])
            ->form([
                Forms\Components\Select::make('learning_event_id')
                    ->label('Event')
                    ->options(fn () => CertificateResource::eventOptions())
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label($isTutor ? 'Akun Tutor' : 'Akun Fasilitator')
                    ->options(fn () => $isTutor ? static::tutorOptions() : static::facilitatorOptions())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data) use ($role, $isTutor, $page): void {
                abort_unless(static::canManage(), 403);

                $event = LearningEvent::query()->findOrFail($data['learning_event_id']);
                $userId = (int) $data['user_id'];
                $service = app(TutorCertificateService::class);

                $user = User::query()->whereKey($userId)->where('role', $isTutor ? UserRole::Tutor : UserRole::Admin)
                    ->when($isTutor, fn ($query) => $query->has('tutor'))
                    ->firstOrFail();
                $alreadyHas = $isTutor
                    ? $service->tutors($event)->contains('user_id', $user->id)
                    : $service->facilitators($event)->contains('id', $user->id);

                $page->eventId = $event->id;

                if ($alreadyHas) {
                    Notification::make()->title($user->name . ' sudah punya sertifikat di event ini.')->warning()->send();

                    return;
                }

                SpeakerCertificates::add($event->id, $user->id, $role);

                Notification::make()->title('Sertifikat untuk ' . $user->name . ' berhasil ditambahkan.')->success()->send();
            });
    }

    public static function delete(): Action
    {
        return Action::make('deleteManual')
            ->label('Hapus')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->size('sm')
            ->button()
            ->visible(fn () => static::canManage())
            ->requiresConfirmation()
            ->modalHeading('Hapus sertifikat tambahan?')
            ->modalDescription('Sertifikat yang ditambahkan manual ini tidak bisa dicetak lagi oleh pemiliknya.')
            ->action(function (array $arguments): void {
                abort_unless(static::canManage(), 403);

                SpeakerCertificates::remove((string) ($arguments['id'] ?? ''));

                Notification::make()->title('Sertifikat tambahan dihapus.')->success()->send();
            });
    }

    private static function tutorOptions(): array
    {
        return User::query()
            ->where('role', UserRole::Tutor)
            // Hanya akun yang sudah punya profil tutor (sertifikat memakai data instansi tutor).
            ->has('tutor')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user) => [$user->id => $user->name . ' (' . $user->email . ')'])
            ->all();
    }

    private static function facilitatorOptions(): array
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user) => [$user->id => $user->name . ' (' . $user->email . ')'])
            ->all();
    }
}
