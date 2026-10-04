<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MaterialPreview;
use App\Filament\Pages\EventRundown;
use App\Filament\Pages\EventAttendanceCode;
use App\Filament\Pages\ShareLinks;
use App\Filament\Pages\ParticipantApproval;
use App\Filament\Pages\Profile;
use App\Filament\Pages\TutorTraining;
use App\Filament\Widgets\KpiOverview;
use App\Http\Middleware\EnsureTutorTrainingCompleted;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TutorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('tutor')
            ->path('tutor')
            ->brandName('sena - Tutor')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->homeUrl(fn () => url('/tutor/pelatihan-tutor'))
            ->colors(['primary' => Color::Green])
            ->navigationGroups([
                'Pelatihan Tutor',
                'Seminar & Materi',
                'Tes & Nilai',
                'Peserta & Lokasi',
                'Absensi & Sesi',
                'Komunitas',
                'Validasi & Sertifikat',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([TutorTraining::class, Pages\Dashboard::class, EventRundown::class, EventAttendanceCode::class, ShareLinks::class, ParticipantApproval::class, MaterialPreview::class, Profile::class])
            ->widgets([Widgets\AccountWidget::class, KpiOverview::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureTutorTrainingCompleted::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
