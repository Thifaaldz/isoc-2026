<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MaterialPreview;
use App\Filament\Pages\CreateSeminarShortcut;
use App\Filament\Pages\EventAttendanceCode;
use App\Filament\Pages\FinalReport;
use App\Filament\Pages\ParticipantRecap;
use App\Filament\Pages\ShareLinks;
use App\Filament\Pages\ParticipantApproval;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\EventMonitoring;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->brandName('sena - Fasilitator')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->colors(['primary' => Color::Blue])
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
            ->pages([Pages\Dashboard::class, CreateSeminarShortcut::class, ParticipantApproval::class, EventAttendanceCode::class, ParticipantRecap::class, FinalReport::class, ShareLinks::class, MaterialPreview::class, Profile::class])
            ->widgets([Widgets\AccountWidget::class, KpiOverview::class, EventMonitoring::class])
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
