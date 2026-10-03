<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EventCatalog;
use App\Filament\Pages\ParticipantLearning;
use App\Filament\Pages\ParticipantCompleteness;
use App\Filament\Pages\ParticipantTests;
use App\Filament\Pages\EventRundown;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\KpiOverview;
use App\Filament\Widgets\ParticipantDashboardOverview;
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

class PesertaPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('peserta')
            ->path('peserta')
            ->brandName('sena - Peserta')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->colors(['primary' => Color::Amber])
            ->navigationGroups([
                'Seminar & Materi',
                'Tugas Peserta',
                'Validasi & Sertifikat',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Pages\Dashboard::class, EventCatalog::class, ParticipantLearning::class, ParticipantTests::class, EventRundown::class, ParticipantCompleteness::class, Profile::class])
            ->widgets([ParticipantDashboardOverview::class, KpiOverview::class])
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
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
