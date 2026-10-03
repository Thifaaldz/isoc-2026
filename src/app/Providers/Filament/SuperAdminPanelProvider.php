<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MaterialPreview;
use App\Filament\Pages\CertificateDesignStudio;
use App\Filament\Pages\CreateSeminarShortcut;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\EventApprovalUpdates;
use App\Filament\Widgets\KpiOverview;
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

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->brandName('sena - Super Admin')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->colors(['primary' => Color::Red])
            ->navigationGroups([
                'Seminar',
                'Konten & Penilaian',
                'Penilaian',
                'Absensi & Peserta',
                'Tugas Peserta',
                'Komunitas',
                'Validasi & Sertifikat',
                'Administrasi',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Pages\Dashboard::class, CreateSeminarShortcut::class, CertificateDesignStudio::class, MaterialPreview::class, Profile::class])
            ->widgets([Widgets\AccountWidget::class, EventApprovalUpdates::class, KpiOverview::class])
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
