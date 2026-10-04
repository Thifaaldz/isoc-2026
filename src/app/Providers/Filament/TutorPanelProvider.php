<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AnswerKey;
use App\Filament\Pages\TutorScores;
use App\Filament\Pages\MaterialPreview;
use App\Filament\Pages\EventRundown;
use App\Filament\Pages\EventAttendanceCode;
use App\Filament\Pages\ShareLinks;
use App\Filament\Widgets\ParticipantRecapTable;
use App\Filament\Pages\ParticipantApproval;
use App\Filament\Pages\Profile;
use App\Filament\Pages\TutorTraining;
use App\Filament\Widgets\KpiOverview;
use App\Http\Middleware\EnsureTutorTrainingCompleted;
use App\Filament\Resources;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
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
    /**
     * Sidebar tutor mengikuti alur kegiatan. Urutan di sini = urutan menu; item yang tidak boleh diakses otomatis disembunyikan.
     *
     * @var array<string, array<class-string>>
     */
    public const NAVIGATION = [
        'Sebelum Kegiatan' => [
            TutorTraining::class,
            Resources\TotAssessmentResource::class,
            Resources\TutorResource::class,
        ],
        'Kegiatan' => [
            EventRundown::class,
            MaterialPreview::class,
            AnswerKey::class,
            ShareLinks::class,
            EventAttendanceCode::class,
            Resources\ParticipantResource::class,
        ],
        'Sesudah Kegiatan' => [
            TutorScores::class,
            Resources\MicrositePracticeResource::class,
            Resources\EvidenceResource::class,
        ],
        'Akun' => [
            Profile::class,
        ],
    ];

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
            ->pages([TutorTraining::class, Pages\Dashboard::class, AnswerKey::class, TutorScores::class, EventRundown::class, EventAttendanceCode::class, ShareLinks::class, ParticipantApproval::class, MaterialPreview::class, Profile::class])
            ->widgets([Widgets\AccountWidget::class, KpiOverview::class, ParticipantRecapTable::class])
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
