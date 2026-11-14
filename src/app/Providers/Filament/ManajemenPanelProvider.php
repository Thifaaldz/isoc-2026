<?php

namespace App\Providers\Filament;

use App\Filament\Manajemen\Pages\EvidencePage;
use App\Filament\Manajemen\Pages\LocationDetailPage;
use App\Filament\Manajemen\Pages\LocationProgressPage;
use App\Filament\Manajemen\Pages\ManagementDashboard;
use App\Filament\Manajemen\Pages\ParticipantScoresPage;
use App\Filament\Manajemen\Pages\ReportsTermsPage;
use App\Filament\Manajemen\Pages\TutorsPage;
use App\Filament\Manajemen\Widgets;
use App\Enums\UserRole;
use App\Filament\Pages\Profile;
use Filament\Navigation\NavigationItem;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel Manajemen: dashboard manajerial read-only untuk memantau progres & KPI seluruh program.
 * Sengaja TIDAK memakai discoverResources: role ini tidak boleh membuka resource yang bisa mengubah data.
 */
class ManajemenPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('manajemen')
            ->path('manajemen')
            ->brandName('sena - Manajemen')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->colors(['primary' => Color::Indigo])
            ->navigationGroups(['Monitoring', 'Akun'])
            // Admin RTIK Pusat yang membuka panel ini bisa kembali ke panelnya sendiri.
            ->navigationItems([
                NavigationItem::make('Kembali ke Admin Pusat')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url('/superadmin')
                    ->group('Akun')
                    ->visible(fn (): bool => auth()->user()?->role === UserRole::SuperAdmin),
            ])
            ->pages([ManagementDashboard::class, LocationProgressPage::class, LocationDetailPage::class, ParticipantScoresPage::class, TutorsPage::class, EvidencePage::class, ReportsTermsPage::class, Profile::class])
            ->widgets([
                Widgets\ManagementKpi::class,
                Widgets\ManagementStages::class,
                Widgets\ManagementProgress::class,
                Widgets\ManagementFunnel::class,
                Widgets\ManagementLocationChart::class,
                Widgets\ManagementScoreChart::class,
                Widgets\ManagementReports::class,
                Widgets\ManagementAlerts::class,
                Widgets\ManagementTrendChart::class,
                Widgets\ManagementLocationProgressChart::class,
                Widgets\ManagementDemographics::class,
                Widgets\ManagementTutorSummary::class,
                Widgets\ManagementEvidenceSummary::class,
            ])
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
