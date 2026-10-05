<?php

namespace App\Providers\Filament;

use App\Filament\Pages\MaterialPreview;
use App\Filament\Pages\CertificateDesignStudio;
use App\Filament\Pages\CreateSeminarShortcut;
use App\Filament\Pages\EventProgress;
use App\Filament\Pages\ManageHomePage;
use App\Filament\Pages\Profile;
use App\Filament\Resources;
use App\Filament\Widgets\ControlCenter;
use App\Filament\Widgets\LocationProgress;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use App\Filament\Widgets\EventApprovalReminders;
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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    /**
     * Sidebar Admin RTIK Pusat dikelompokkan per alur kerja. Urutan di sini = urutan menu;
     * item yang tidak boleh diakses otomatis disembunyikan.
     *
     * @var array<string, array<class-string>>
     */
    public const NAVIGATION = [
        'Program' => [
            Resources\LearningEventResource::class,
            Resources\EvidenceResource::class,
            Resources\PaymentResource::class,
        ],
        'Peserta & Tutor' => [
            Resources\ParticipantResource::class,
            Resources\AssessmentAttemptResource::class,
            Resources\AttendanceResource::class,
            Resources\MicrositePracticeResource::class,
            Resources\TutorResource::class,
            Resources\TotAssessmentResource::class,
            Resources\TrainingSessionResource::class,
        ],
        'Materi & Tes' => [
            Resources\ModuleTemplateResource::class,
            Resources\LearningMeetingResource::class,
            MaterialPreview::class,
            Resources\AssessmentResource::class,
        ],
        'Sertifikat' => [
            Resources\CertificateResource::class,
            Resources\CertificateTemplateResource::class,
            CertificateDesignStudio::class,
        ],
        'Pengaturan' => [
            ManageHomePage::class,
            Resources\SchoolResource::class,
            Resources\PartnerResource::class,
            Resources\WagGroupResource::class,
            Resources\PeerGroupResource::class,
            Resources\UserResource::class,
        ],
        'Akun' => [
            Profile::class,
        ],
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->brandName('sena - Admin RTIK Pusat')
            ->brandLogo(asset('images/sena-symbol.png'))
            ->brandLogoHeight('2.25rem')
            ->login(fn () => redirect('/login'))
            ->colors(['primary' => Color::Red])
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $builder
                ->items(Pages\Dashboard::getNavigationItems())
                ->groups(collect(self::NAVIGATION)
                    ->map(fn (array $classes, string $group) => NavigationGroup::make($group)->items(collect($classes)
                        ->filter(fn (string $class) => $class::shouldRegisterNavigation() && $class::canAccess())
                        ->values()
                        ->flatMap(fn (string $class, int $index) => collect($class::getNavigationItems())->map->sort($index))
                        ->all()))
                    ->filter(fn (NavigationGroup $group) => filled($group->getItems()))
                    ->values()
                    ->all()))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Pages\Dashboard::class, ManageHomePage::class, CreateSeminarShortcut::class, EventProgress::class, CertificateDesignStudio::class, MaterialPreview::class, Profile::class])
            // KPI di atas, lalu Update (kiri) & Reminder (kanan). Sign out tersedia di menu profil.
            ->widgets([ControlCenter::class, KpiOverview::class, LocationProgress::class, EventApprovalUpdates::class, EventApprovalReminders::class])
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
