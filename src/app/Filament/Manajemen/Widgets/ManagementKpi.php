<?php

namespace App\Filament\Manajemen\Widgets;

use App\Enums\UserRole;
use App\Filament\Manajemen\Pages\EvidencePage;
use App\Filament\Manajemen\Pages\LocationProgressPage;
use App\Filament\Manajemen\Pages\ParticipantScoresPage;
use App\Filament\Manajemen\Pages\ReportsTermsPage;
use App\Filament\Manajemen\Pages\TutorsPage;
use App\Support\ProgramMetrics;
use Filament\Widgets\Widget;

/** Banner progres program + KPI TOR dikelompokkan (Jangkauan, Kualitas Belajar, Penyelesaian). Setiap kartu membuka halaman datanya. */
class ManagementKpi extends Widget
{
    protected static string $view = 'filament.manajemen.kpi';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Manajemen;
    }

    protected function getViewData(): array
    {
        $m = ProgramMetrics::get();
        $k = $m->kpi();
        $f = collect($m->funnel())->pluck('value', 'label');
        $events = $k['events'];
        $proofComplete = $m->rows->filter(fn ($r) => $r['proof_total'] > 0 && $r['proof_done'] >= $r['proof_total'])->count();
        $gain = $k['avg_pre'] > 0 ? round($k['avg_post'] - $k['avg_pre'], 1) : null;
        $num = fn ($v, int $d = 0) => number_format((float) $v, $d, ',', '.');
        // [label, nilai tampil, nilai, target, keterangan, ikon, url]
        $card = fn (string $label, string $shown, float $value, ?float $target, string $hint, string $icon, string $url) => compact('label', 'shown', 'value', 'target', 'hint', 'icon', 'url');

        return [
            'progress' => $m->overallProgress(),
            'events' => $events,
            'stageNow' => collect($m->stageCounts())->sortByDesc('count')->first(),
            'updated' => now()->translatedFormat('d M Y, H:i'),
            'hero' => [
                ['Lokasi', $k['locations'] . '/' . ProgramMetrics::TARGET_LOCATIONS],
                ['Peserta', $num($k['participants'])],
                ['Sertifikat terbit', $num($f['Sertifikat terbit'] ?? 0)],
                ['Laporan disetujui', $k['reports_approved'] . '/' . $events],
            ],
            'groups' => [
                'Jangkauan' => [
                    $card('Lokasi berjalan', $k['locations'] . ' / ' . ProgramMetrics::TARGET_LOCATIONS, $k['locations'], ProgramMetrics::TARGET_LOCATIONS, 'Target ' . ProgramMetrics::TARGET_LOCATIONS . ' lokasi', 'heroicon-o-map-pin', LocationProgressPage::getUrl()),
                    $card('Peserta terdaftar', $num($k['participants']), $k['participants'], ProgramMetrics::TARGET_PARTICIPANTS, 'Target ' . $num(ProgramMetrics::TARGET_PARTICIPANTS) . ' siswa · ' . $num($f['Terdaftar'] ?? 0) . ' pendaftaran event', 'heroicon-o-user-group', ParticipantScoresPage::getUrl()),
                    $card('Tutor lulus ToT', $k['tutors_tot'] . ' / ' . $k['tutors'], $k['tutors_tot'], max(1, $k['tutors']), 'Dari tutor yang ditugaskan', 'heroicon-o-academic-cap', TutorsPage::getUrl()),
                    $card('Anggota WAG aktif', $num($k['wag']), $k['wag'], ProgramMetrics::TARGET_WAG, 'Target ≥ ' . $num(ProgramMetrics::TARGET_WAG) . ' anggota', 'heroicon-o-chat-bubble-left-right', ParticipantScoresPage::getUrl()),
                ],
                'Kualitas belajar' => [
                    $card('Rata-rata Post-Test', $num($k['avg_post'], 1), $k['avg_post'], 85, 'Target ≥ 85 · Pre-Test ' . $num($k['avg_pre'], 1), 'heroicon-o-clipboard-document-check', ParticipantScoresPage::getUrl()),
                    $card('Kenaikan nilai', $gain === null ? '-' : (($gain >= 0 ? '+' : '') . $num($gain, 1)), (float) ($gain ?? 0), null, 'Rata-rata Post-Test dikurangi Pre-Test', 'heroicon-o-arrow-trending-up', ParticipantScoresPage::getUrl()),
                    $card('Identifikasi Ancaman (%)', $num($k['threat'], 1), $k['threat'], 82, 'Target ≥ 82', 'heroicon-o-shield-check', ParticipantScoresPage::getUrl()),
                    $card('Self-Efficacy (Post)', $num($k['efficacy'], 1), $k['efficacy'], 70, 'Baseline 55 → 70 → 85', 'heroicon-o-sparkles', ParticipantScoresPage::getUrl()),
                ],
                'Penyelesaian' => [
                    $card('Sertifikat terbit', $num($f['Sertifikat terbit'] ?? 0), (float) ($f['Sertifikat terbit'] ?? 0), max(1, (float) ($f['Terdaftar'] ?? 0)), 'Dari ' . $num($f['Terdaftar'] ?? 0) . ' pendaftaran peserta', 'heroicon-o-trophy', ReportsTermsPage::getUrl()),
                    $card('Syarat laporan lengkap', $proofComplete . ' / ' . $events, $proofComplete, max(1, $events), '13 syarat terpenuhi', 'heroicon-o-photo', EvidencePage::getUrl()),
                    $card('Laporan final disetujui', $k['reports_approved'] . ' / ' . $events, $k['reports_approved'], max(1, $events), 'Disetujui Admin RTIK Pusat', 'heroicon-o-document-check', ReportsTermsPage::getUrl()),
                    $card('Termin-2 cair', $k['term2'] . ' / ' . $events, $k['term2'], max(1, $events), 'Setelah laporan disetujui', 'heroicon-o-banknotes', ReportsTermsPage::getUrl()),
                ],
            ],
        ];
    }
}
