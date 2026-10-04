<?php

namespace App\Providers;

use App\Http\Responses\Auth\RoleBasedLoginResponse;
use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Services\TutorMaterialMirror;
use Filament\Actions\MountableAction;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Livewire\Notifications;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponse::class, RoleBasedLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Tanggal (sertifikat, absensi, dashboard) berbahasa Indonesia: "04 Oktober 2026".
        \Carbon\Carbon::setLocale('id');

        Page::formActionsAlignment(Alignment::Right);
        Notifications::alignment(Alignment::End);
        Notifications::verticalAlignment(VerticalAlignment::End);
        Page::$reportValidationErrorUsing = function (ValidationException $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };
        MountableAction::configureUsing(function (MountableAction $action) {
            $action->modalFooterActionsAlignment(Alignment::Right);
        });

        // Dropdown native dengan CSS kustom: ikon panah tidak boleh berulang/menimpa teks di semua panel.
        FilamentView::registerRenderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => '<style>
            select:not([multiple]):not([size]):not(.learning-select) {
                background-position: right .6rem center !important;
                background-repeat: no-repeat !important;
                background-size: 1.25em 1.25em !important;
                padding-right: 2.5rem !important;
                text-overflow: ellipsis;
            }
        </style>');

        // Materi tutor auto-generated selalu mengikuti pertemuan & materi Materi Event peserta.
        $syncMeeting = function (LearningMeeting $meeting): void {
            if ($meeting->learning_event_id === null) {
                app(TutorMaterialMirror::class)->syncFromTemplateId($meeting->module_template_id);
            }
        };
        LearningMeeting::saved($syncMeeting);
        LearningMeeting::deleted($syncMeeting);

        $syncMaterial = function (LearningMaterial $material) use ($syncMeeting): void {
            if ($meeting = LearningMeeting::query()->find($material->learning_meeting_id)) {
                $syncMeeting($meeting);
            }
        };
        LearningMaterial::saved($syncMaterial);
        LearningMaterial::deleted($syncMaterial);
    }
}
