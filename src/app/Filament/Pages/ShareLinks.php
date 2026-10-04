<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Support\QrImage;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/** Link dan QR code halaman pendaftaran tiap event (lokus) untuk dibagikan. */
class ShareLinks extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Link & QR Event';

    protected static ?string $title = 'Link & QR Event';

    protected static ?string $slug = 'link-qr';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.share-links';

    /** Tidak termasuk menu Admin RTIK Daerah (hanya Kelola Event, Presensi, dan Peserta Lengkap). */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role !== UserRole::Admin;
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Tutor], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->role === UserRole::Admin ? 'Seminar' : 'Seminar & Materi';
    }

    /** @return Collection<int, array{event: LearningEvent, url: string, qr: ?string}> */
    public function getEventLinksProperty(): Collection
    {
        $user = auth()->user();

        $query = match ($user?->role) {
            UserRole::Tutor => $user->tutor
                ? LearningEvent::query()->whereHas('tutors', fn ($tutorQuery) => $tutorQuery->where('tutors.id', $user->tutor->id))
                : null,
            UserRole::Admin => LearningEvent::query()->where('created_by', $user->id),
            default => null,
        };

        if (! $query) {
            return collect();
        }

        return $query->with('school')
            ->where('is_published', true)
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (LearningEvent $event): array {
                $url = route('event.register', $event->slug);

                return ['event' => $event, 'url' => $url, 'qr' => QrImage::png($url, 6)];
            });
    }
}
