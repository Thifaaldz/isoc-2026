<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\EvidenceResource;
use App\Filament\Resources\LearningEventResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Support\EventOverview;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

/** Dashboard Admin RTIK Pusat: daftar hal yang perlu ditindaklanjuti dan posisi tahapan seluruh lokasi. */
class ControlCenter extends Widget
{
    protected static string $view = 'filament.widgets.control-center';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -9;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    /** Antrian tindakan Pusat beserta tautan langsung ke halaman yang sudah terfilter. */
    public static function actionItems(): array
    {
        $eventsUrl = fn (string $tab) => LearningEventResource::getUrl('index', ['activeTab' => $tab]);

        return [
            [
                'label' => 'Laporan final menunggu approval',
                'hint' => 'Periksa laporan lalu approve untuk mencairkan Termin-2.',
                'count' => LearningEvent::query()->where('final_report_status', 'submitted')->count(),
                'icon' => 'heroicon-o-document-check', 'color' => 'warning',
                'url' => $eventsUrl('laporan'), 'cta' => 'Review laporan',
            ],
            [
                'label' => 'Bukti dukung menunggu verifikasi',
                'hint' => 'Foto, video, absensi, dan bukti microsite dari daerah.',
                'count' => Evidence::query()->where('status', 'pending')->where('type', '!=', 'follow_ig')->count(),
                'icon' => 'heroicon-o-photo', 'color' => 'info',
                'url' => EvidenceResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'pending']]]), 'cta' => 'Verifikasi bukti',
            ],
            [
                'label' => 'Pengajuan event / publish',
                'hint' => 'Event baru atau revisi dari Admin RTIK Daerah.',
                'count' => LearningEvent::query()->where(fn ($query) => $query->where('workflow_status', 'submitted')->orWhere('publish_approval_status', 'pending'))->count(),
                'icon' => 'heroicon-o-inbox-arrow-down', 'color' => 'danger',
                'url' => $eventsUrl('persetujuan'), 'cta' => 'Tinjau pengajuan',
            ],
            [
                'label' => 'Termin-2 siap dicairkan',
                'hint' => 'Laporan sudah disetujui, Termin-2 eligible untuk diproses.',
                'count' => DB::table('payments')->where('term', 2)->where('status', 'eligible')->count(),
                'icon' => 'heroicon-o-banknotes', 'color' => 'success',
                'url' => PaymentResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'eligible']]]), 'cta' => 'Buka termin',
            ],
        ];
    }

    /** Jumlah lokasi per tahap saat ini (tahap pertama yang belum selesai). */
    public static function pipeline(): array
    {
        $events = LearningEvent::query()->with(['school', 'payments', 'tutors', 'meetings', 'orderedPartners'])->get();
        $titles = ['Disetujui Pusat', 'Termin-1 & pendaftaran', 'Pelaksanaan', 'Laporan final', 'Termin-2', 'Selesai'];
        $counts = array_fill(0, count($titles), 0);

        foreach ($events as $event) {
            $steps = (new EventOverview($event))->steps();
            $current = collect($steps)->search(fn (array $step) => $step['state'] !== 'done');
            $counts[$current === false ? count($titles) - 1 : $current]++;
        }

        return ['total' => $events->count(), 'steps' => collect($titles)->map(fn ($title, $i) => ['title' => $title, 'count' => $counts[$i]])->all()];
    }

    protected function getViewData(): array
    {
        return ['actions' => static::actionItems(), 'pipeline' => static::pipeline()];
    }
}
