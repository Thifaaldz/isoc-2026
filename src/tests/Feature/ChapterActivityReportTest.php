<?php

use App\Enums\UserRole;
use App\Filament\Manajemen\Pages\ChapterActivityPage;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function chapterEvent(School $school, string $slug, $startsAt): LearningEvent
{
    return LearningEvent::query()->create([
        'title' => "Digital Safety Champions - {$slug}", 'slug' => $slug, 'school_id' => $school->id,
        'status' => 'active', 'workflow_status' => 'verified_term_1', 'publish_approval_status' => 'published', 'is_published' => true, 'registration_open' => true,
        'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addHours(3), 'target_participants' => 100, 'target_tutors' => 3,
    ]);
}

beforeEach(function () {
    $this->actingAs(User::query()->create(['name' => 'mgmt', 'email' => 'mgmt.chapter@isoc.id', 'password' => 'password', 'role' => UserRole::Manajemen, 'is_active' => true]));
    Filament::setCurrentPanel(Filament::getPanel('manajemen'));
});

test('laporan chapter activity bisa dibuka manajemen dan admin pusat saja', function () {
    $this->get('/manajemen/chapter-activity')->assertOk()->assertSee('Laporan Chapter Activity');

    $this->actingAs(User::query()->create(['name' => 'admin', 'email' => 'admin.chapter@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true]));
    $this->get('/manajemen/chapter-activity')->assertForbidden();
});

test('laporan chapter activity menghitung kolom kuning dan memfilter periode', function () {
    $school = School::query()->create(['name' => 'SMK Uji', 'city' => 'Kota Bandung', 'province' => 'Jawa Barat', 'address' => 'Jl. Merdeka 1']);
    $inYear = chapterEvent($school, 'tahun-ini', now()->startOfYear()->addDays(10));
    $lastYear = chapterEvent($school, 'tahun-lalu', now()->subYear()->startOfYear()->addDays(10));

    $participant = Participant::query()->create(['user_id' => User::query()->create(['name' => 'siswa', 'email' => 'siswa.chapter@isoc.id', 'password' => 'password', 'role' => UserRole::Peserta, 'is_active' => true])->id, 'school_id' => $school->id]);
    $participant->learningEvents()->attach($inYear->id, ['status' => 'registered']);
    Attendance::query()->create(['learning_event_id' => $inYear->id, 'participant_id' => $participant->id, 'status' => 'hadir']);
    $pre = Assessment::query()->create(['type' => 'pre', 'title' => 'Pre', 'learning_event_id' => $inYear->id]);
    $post = Assessment::query()->create(['type' => 'post', 'title' => 'Post', 'learning_event_id' => $inYear->id]);
    AssessmentAttempt::query()->create(['assessment_id' => $pre->id, 'participant_id' => $participant->id, 'score' => 40]);
    AssessmentAttempt::query()->create(['assessment_id' => $post->id, 'participant_id' => $participant->id, 'score' => 90]);
    AssessmentAttempt::query()->create(['assessment_id' => $post->id, 'participant_id' => $participant->id, 'score' => 80]);

    $page = Livewire::test(ChapterActivityPage::class)
        ->assertCanSeeTableRecords([$inYear])
        ->assertCanNotSeeTableRecords([$lastYear])
        ->assertSee('Kota Bandung, Jawa Barat')
        ->assertSee('Naik 45,0 poin dari Pre-Test')
        ->assertTableActionHasUrl('laporan', route('reports.events.activity.preview', $inYear), $inYear);

    expect($page->instance()->summaryStats())->toMatchArray(['attended' => 1, 'registered' => 1, 'pre' => 40.0, 'post' => 85.0]);

    $row = ChapterActivityPage::query()->whereKey($inYear->id)->first();
    expect((int) $row->attended_count)->toBe(1)
        ->and(round((float) $row->avg_pre, 1))->toBe(40.0)
        ->and(round((float) $row->avg_post, 1))->toBe(85.0);

    $range = ChapterActivityPage::presetRange('last_year');
    $page->set('tableFilters.periode.preset', 'custom')
        ->set('tableFilters.periode.from', $range[0])
        ->set('tableFilters.periode.until', $range[1])
        ->assertCanSeeTableRecords([$lastYear])
        ->assertCanNotSeeTableRecords([$inYear]);

    $page->callAction('export')->assertFileDownloaded();
});

test('excel chapter activity memakai template asli: 14 kolom tetap ada, kolom tanpa data kosong', function () {
    $school = School::query()->create(['name' => 'SMK Uji', 'city' => 'Kota Bandung', 'province' => 'Jawa Barat', 'address' => 'Jl. Merdeka & Co <1>']);
    $event = chapterEvent($school, 'export', now()->startOfYear()->addDays(5));
    $row = ChapterActivityPage::templateRow(ChapterActivityPage::query()->whereKey($event->id)->first());

    $path = tempnam(sys_get_temp_dir(), 'chapter-test-');
    ChapterActivityPage::fillTemplate([$row], $path);

    $options = new OpenSpout\Reader\XLSX\Options();
    $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
    $reader = new OpenSpout\Reader\XLSX\Reader($options);
    $reader->open($path);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $r) {
            $rows[] = array_map(fn ($v) => $v instanceof DateTimeInterface ? $v->format('Y-m-d') : $v, $r->toArray());
        }
        break;
    }
    $reader->close();

    $zip = new ZipArchive();
    $zip->open($path);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    @unlink($path);
    expect($sheetXml)->not->toContain(' s="3"'); // tidak ada lagi header kuning

    expect(array_slice($rows[1], 0, 14))->toBe(['Action', 'Activity Name', 'Activity Date', 'Type', 'Number of Participants', 'Purpose', 'Is Mission Related', 'Focus Area', 'Location', 'URL', 'Pre Test Achievement', 'Post Test Achievement', 'Upload File', 'Address']);

    $data = array_slice($rows[2], 0, 14);
    expect($data[1])->toBe($event->title)
        ->and($data[2])->toBe($event->starts_at->format('Y-m-d'))
        ->and($data[3])->toBe('Offline')
        ->and($data[4])->toBe(0)
        ->and($data[8])->toBe('Kota Bandung, Jawa Barat')
        ->and($data[9])->toBe(route('reports.events.activity.preview', $event))
        ->and($data[13])->toBe('SMK Uji, Jl. Merdeka & Co <1>, Kota Bandung, Jawa Barat');
    foreach ([0, 5, 6, 7, 10, 11, 12] as $blank) {
        expect($data[$blank] ?? '')->toBe('');
    }
    expect($rows)->toHaveCount(1000);
});

test('lokasi dan alamat chapter activity dirapikan: huruf kapital & nama dobel', function () {
    expect(ChapterActivityPage::joinPlaces(['Lokus DSC Surabaya', 'Surabaya', 'Jawa Timur', 'KOTA SURABAYA', 'JAWA TIMUR']))
        ->toBe('Lokus DSC Surabaya, Kota Surabaya, Jawa Timur')
        ->and(ChapterActivityPage::joinPlaces(['Lokus DSC Jogjakarta', 'Jogjakarta, Di Yogyakarta', 'KOTA YOGYAKARTA', 'DI YOGYAKARTA']))
        ->toBe('Lokus DSC Jogjakarta, Jogjakarta, Kota Yogyakarta, DI Yogyakarta')
        ->and(ChapterActivityPage::joinPlaces(['KOTA ADM. JAKARTA PUSAT', 'DKI JAKARTA']))->toBe('Kota Adm. Jakarta Pusat, DKI Jakarta')
        ->and(ChapterActivityPage::joinPlaces(['SMK Uji', 'Jl. Merdeka 1', null, '', 'Kota Bandung', 'Jawa Barat']))->toBe('SMK Uji, Jl. Merdeka 1, Kota Bandung, Jawa Barat');
});
