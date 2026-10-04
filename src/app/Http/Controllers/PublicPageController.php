<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Support\HomePageContent;
use App\Support\PublicEvent;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    /** Halaman utama mengikuti https://isoc.id/; isinya dikelola Admin RTIK Pusat (Pengaturan > Halaman Utama). */
    public function home(): View
    {
        $content = HomePageContent::get();
        $events = collect();
        $eventsTotal = 0;

        if ($content['events']['enabled'] ?? true) {
            $upcoming = LearningEvent::query()
                ->where('is_published', true)
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
            $eventsTotal = (clone $upcoming)->count();
            $events = $upcoming
                ->withCount('participants')
                ->with('school')
                ->orderBy('starts_at')
                ->limit(max(1, (int) ($content['events']['limit'] ?? 6)))
                ->get()
                ->map(fn (LearningEvent $event) => $this->publicEventFromModel($event));
        }

        return view('pages.home', [
            'content' => $content,
            'events' => $events,
            'eventsTotal' => $eventsTotal,
            'settings' => $this->settings(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about', [
            'sections' => [
                'hero' => $this->section('Tentang Kami', config('app.name', 'sena'), 'Komunitas lokal Internet Society yang mendorong internet terbuka, aman, terpercaya, dan bermanfaat untuk semua.'),
                'history' => $this->section('Gerakan Global, Aksi Lokal', 'Sejarah & Peran Kami', 'Internet Society hadir sebagai jaringan global yang memperjuangkan internet terbuka. Chapter Jakarta menerjemahkan semangat itu dalam edukasi, advokasi, kolaborasi, dan program literasi digital di Indonesia.'),
                'community' => $this->section('Komunitas', 'Ruang Kolaborasi', 'Kami mempertemukan pelajar, pendidik, profesional, pembuat kebijakan, dan komunitas teknis.'),
                'ecosystem' => $this->section('Ekosistem Internet', 'Membangun Kepercayaan Digital', 'Fokus kami adalah memperkuat kapasitas masyarakat agar mampu menggunakan internet secara aman, produktif, dan bertanggung jawab.'),
                'focus_areas' => $this->section('Fokus', 'Area Kerja', 'Agenda kami bergerak dari literasi digital sampai tata kelola internet.'),
                'vision_mission' => $this->section('Visi & Misi', 'Internet yang Terbuka untuk Semua', 'Kami percaya internet terbaik adalah internet yang mudah diakses, aman, dan memberi ruang partisipasi.'),
                'resources' => $this->section('Sumber Daya', 'Belajar Bersama', 'Akses materi dan publikasi untuk memperluas wawasan digital.'),
                'team' => $this->section('Tim', 'Penggerak Komunitas', 'Orang-orang yang menjaga program tetap berjalan.'),
            ],
            'items' => [
                'community' => collect([
                    $this->item('Anggota Komunitas', 'Ruang belajar dan berbagi praktik baik.', 'groups', 'blue'),
                    $this->item('Sekolah Mitra', 'Kolaborasi implementasi program literasi digital.', 'school', 'teal'),
                    $this->item('Pemangku Kepentingan', 'Dialog lintas sektor untuk internet yang lebih sehat.', 'handshake', 'blue'),
                ]),
                'focus_areas' => collect([
                    $this->item('Internet Terbuka', 'Mendorong akses dan tata kelola yang inklusif.', 'public', 'blue'),
                    $this->item('Keamanan Digital', 'Meningkatkan kesadaran keamanan dan perlindungan data.', 'security', 'teal'),
                    $this->item('Literasi Pelajar', 'Membekali generasi muda dengan keterampilan digital.', 'school', 'blue'),
                ]),
                'vision_mission' => collect([
                    $this->item('Visi', 'Internet yang terbuka, aman, dan dapat dipercaya oleh semua orang.', 'visibility', 'teal'),
                    $this->item('Misi', 'Menguatkan edukasi, komunitas, dan kolaborasi untuk dampak nyata.', 'flag', 'teal'),
                ]),
                'resources' => collect([
                    $this->item('Publikasi', 'Materi bacaan dan rujukan tentang internet.', 'article', 'blue', url: route('resources')),
                    $this->item('Program', 'Informasi kegiatan dan pembelajaran.', 'category', 'teal', url: route('programs')),
                    $this->item('Event', 'Kegiatan dan pendaftaran peserta.', 'event', 'blue', url: route('events')),
                ]),
            ],
            'teamMembers' => collect(),
            'settings' => $this->settings(),
        ]);
    }

    public function programs(): View
    {
        $modules = Module::query()->where('is_published', true)->orderBy('number')->get();

        return view('pages.programs', [
            'sections' => [
                'hero' => $this->section('Program', 'Digital Safety Champions', 'Program literasi digital untuk membantu peserta memahami keamanan internet, privasi, asesmen, dan praktik digital yang bertanggung jawab.'),
                'global_programs' => $this->section('Pilar', 'Program Utama', 'Pendekatan program mengikuti semangat Internet Society: terbuka, terpercaya, dan inklusif.'),
                'featured_program' => $this->section('Pendaftaran', 'Jalur Peserta', 'Peserta mendaftar melalui halaman event, otomatis dibuatkan akun, lalu diarahkan ke panel peserta untuk melanjutkan pembelajaran.', 'Daftar Sekarang', route('event.register', $this->event())),
                'local_programs' => $this->section('Materi', 'Modul Pembelajaran', 'Materi yang tersedia di panel peserta.'),
                'collaboration' => $this->section('Kolaborasi', 'Bersama Sekolah dan Komunitas', 'Program dirancang agar sekolah, tutor, dan peserta dapat bergerak dalam satu ekosistem pembelajaran.'),
                'impact_multipliers' => $this->section('Dampak', 'Dari Peserta Menjadi Penggerak', 'Peserta didorong menjadi agen perubahan di lingkungan sekolah dan komunitasnya.'),
            ],
            'items' => [
                'hero' => collect([
                    $this->item('Belajar', 'Akses modul pembelajaran terstruktur.', 'menu_book', 'teal'),
                    $this->item('Praktik', 'Kerjakan asesmen dan praktik microsite.', 'task_alt', 'teal'),
                ]),
                'global_programs' => collect([
                    $this->item('Open Internet', 'Memahami internet sebagai ruang terbuka dan inklusif.', 'public', 'blue'),
                    $this->item('Online Safety', 'Mengenali risiko dan praktik aman di ruang digital.', 'shield', 'teal'),
                    $this->item('Digital Citizenship', 'Membangun kebiasaan digital yang bertanggung jawab.', 'diversity_3', 'blue'),
                ]),
                'local_programs' => $modules->map(fn ($module) => $this->item(
                    'Modul ' . $module->number . ': ' . $module->title,
                    $module->description ?? 'Materi pembelajaran peserta.',
                    'article',
                    $module->number % 2 ? 'blue' : 'teal'
                )),
                'collaboration' => collect([
                    $this->item('Sekolah', 'Mendampingi implementasi program.', 'school', 'teal'),
                    $this->item('Tutor', 'Mengawal pembelajaran dan validasi.', 'co_present', 'teal'),
                    $this->item('Komunitas', 'Membuka ruang berbagi praktik baik.', 'groups', 'teal'),
                ]),
                'impact_multipliers' => collect([
                    $this->item('Cadre Peserta', 'Peserta terbaik dapat menjadi penggerak literasi digital.', 'workspace_premium', 'blue'),
                    $this->item('E-Sertifikat', 'Pencapaian peserta terdokumentasi melalui sertifikat digital.', 'verified', 'teal'),
                ]),
            ],
            'settings' => $this->settings(),
        ]);
    }

    public function events(): View
    {
        $publishedEvents = LearningEvent::query()
            ->withCount('participants')
            ->with('school')
            ->where('is_published', true)
            ->where('status', 'active')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (LearningEvent $event) => $this->publicEventFromModel($event))
            // Event yang masih bisa didaftari tampil lebih dulu, event yang sudah lewat di akhir.
            ->sortBy(fn (PublicEvent $event) => [$event->isPast() ? 1 : 0, $event->canRegister() ? 0 : 1, $event->date?->timestamp ?? PHP_INT_MAX])
            ->values();

        $event = $publishedEvents->first() ?? $this->event();
        $upcomingEvents = $publishedEvents->reject(fn (PublicEvent $event) => $event->isPast())->values();

        return view('pages.events', [
            'sections' => [
                'hero' => $this->section('Events', 'Kegiatan ISOC Jakarta', 'Temukan kegiatan terbaru dan daftar sebagai peserta untuk mengakses panel pembelajaran.'),
                'more_events' => $this->section('Agenda', 'Event Lainnya'),
                'upcoming' => $this->section('Timeline', 'Agenda Mendatang'),
            ],
            'items' => [],
            'featuredEvent' => $event,
            'events' => $publishedEvents->slice(1)->values(),
            'upcomingEvents' => $upcomingEvents,
            'settings' => $this->settings(),
        ]);
    }

    public function ourPartner(): View
    {
        return view('pages.ourpartner', [
            'sections' => [
                'hero' => $this->section('Mitra Kami', 'Kolaborasi untuk Internet yang Lebih Baik', 'Program berjalan melalui dukungan komunitas, sekolah, dan mitra strategis.'),
                'international' => $this->section('Global', 'Mitra Internasional', 'Terhubung dengan ekosistem Internet Society global.'),
                'national' => $this->section('Nasional', 'Mitra Nasional', 'Kolaborasi lokal untuk dampak yang lebih dekat.'),
                'cta' => $this->section('Berkolaborasi', 'Ingin Menjadi Mitra?', 'Mari membangun ruang digital yang aman dan inklusif bersama.', 'Hubungi Kami', 'mailto:info@isoc.id'),
            ],
            'items' => [],
            'internationalPartners' => collect([
                $this->partner('Internet Society', 'Global Community', 'https://www.internetsociety.org'),
            ]),
            'nationalPartners' => collect([
                $this->partner('APJII', 'Asosiasi Penyelenggara Jasa Internet Indonesia', 'https://apjii.or.id', asset('images/partners/apjii.png')),
                $this->partner('PANDI', 'Pengelola Nama Domain Internet Indonesia', 'https://pandi.id', asset('images/partners/pandi.png')),
            ]),
            'settings' => $this->settings(),
        ]);
    }

    /** Event utama untuk tombol daftar di Home/Program: utamakan event yang masih bisa didaftari. */
    public function event(): PublicEvent
    {
        $events = LearningEvent::query()
            ->withCount('participants')
            ->with('school')
            ->where('is_published', true)
            ->where('status', 'active')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (LearningEvent $event) => $this->publicEventFromModel($event));

        $event = $events->first(fn (PublicEvent $event) => $event->canRegister())
            ?? $events->first(fn (PublicEvent $event) => ! $event->isPast())
            ?? $events->last();

        if ($event) {
            return $event;
        }

        return new PublicEvent(
            slug: 'digital-safety-champions',
            title: 'Digital Safety Champions',
            date: Carbon::create(2026, 10, 24, 9, 0),
            time_info: '09.00 - 12.00 WIB',
            location: 'Online dan sekolah mitra',
            location_type: 'hybrid',
            category: 'Literasi Digital',
            description: 'Program pendaftaran peserta ISOC untuk literasi keamanan digital, pembelajaran modul, asesmen, dan e-sertifikat.',
            registration_open: true,
            max_participants: 500,
            confirmed_count: Participant::query()->count(),
            capacity_info: Participant::query()->count() . '/500 peserta'
        );
    }

    private function publicEventFromModel(LearningEvent $event): PublicEvent
    {
        $location = match ($event->event_type) {
            'webinar' => 'Webinar / Zoom',
            'hybrid' => ($event->school?->name ? $event->school->name . ' + Zoom' : 'Hybrid'),
            default => $event->school?->name,
        };

        return new PublicEvent(
            slug: $event->slug,
            title: $event->title,
            date: $event->starts_at,
            time_info: $event->starts_at && $event->ends_at
                ? $event->starts_at->format('H.i') . ' - ' . $event->ends_at->format('H.i') . ' WIB'
                : null,
            location: $location,
            location_type: $event->event_type,
            category: 'Literasi Digital',
            description: $event->description,
            registration_open: (bool) $event->registration_open,
            max_participants: $event->target_participants,
            confirmed_count: (int) ($event->participants_count ?? 0),
            capacity_info: ($event->participants_count ?? 0) . '/' . ($event->target_participants ?: 0) . ' peserta',
            audience_type: $event->audience_type ?? 'school',
            ends_at: $event->ends_at,
        );
    }

    private function section(
        string $title,
        ?string $subtitle = null,
        ?string $description = null,
        ?string $buttonText = null,
        ?string $buttonUrl = null,
        ?string $secondaryButtonText = null,
        ?string $secondaryButtonUrl = null,
        ?string $image = null,
    ): object {
        return (object) [
            'title' => $title,
            'subtitle' => $subtitle,
            'description' => $description,
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
            'secondary_button_text' => $secondaryButtonText,
            'secondary_button_url' => $secondaryButtonUrl,
            'image' => $image,
        ];
    }

    private function item(
        string $title,
        ?string $description = null,
        ?string $icon = null,
        ?string $iconColor = 'blue',
        array $extraData = [],
        ?string $url = null,
    ): object {
        return (object) [
            'title' => $title,
            'description' => $description,
            'icon' => $icon,
            'icon_color' => $iconColor,
            'extra_data' => $extraData,
            'image' => null,
            'url' => $url,
        ];
    }

    private function partner(string $name, ?string $subtitle, ?string $url, ?string $logoUrl = null): object
    {
        return (object) [
            'name' => $name,
            'subtitle' => $subtitle,
            'url' => $url,
            'logo' => null,
            'logo_url' => $logoUrl,
        ];
    }

    private function settings(): array
    {
        return [
            'footer_description' => 'Mendukung pengembangan internet yang berkelanjutan, inklusif, aman, dan mudah diakses.',
            'social_instagram' => 'https://www.instagram.com/isoc.jkt/',
            'social_linkedin' => 'https://www.linkedin.com/company/internet-society-chapter-jakarta-ina/',
        ];
    }
}
