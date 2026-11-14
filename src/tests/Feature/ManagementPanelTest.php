<?php

use App\Enums\UserRole;
use App\Filament\Manajemen\Pages\ManagementDashboard;
use App\Filament\Manajemen\Widgets\ManagementKpi;
use App\Filament\Manajemen\Widgets\ManagementLocationChart;
use App\Filament\Manajemen\Widgets\ManagementProgress;
use App\Filament\Pages\Auth\RoleLogin;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\User;
use App\Support\ProgramMetrics;
use Filament\Facades\Filament;
use Livewire\Livewire;

function mgmtUser(UserRole $role, string $email): User
{
    return User::query()->create(['name' => strtok($email, '@'), 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true]);
}

beforeEach(fn () => ProgramMetrics::flush());

test('akun manajemen diarahkan ke panel manajemen setelah login', function () {
    mgmtUser(UserRole::Manajemen, 'mgmt@isoc.id');
    Filament::setCurrentPanel(Filament::getPanel('auth'));

    Livewire::test(RoleLogin::class)
        ->set('data.email', 'mgmt@isoc.id')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect(url('/manajemen'));
});

test('akun manajemen hanya bisa membuka panel manajemen', function () {
    $this->actingAs(mgmtUser(UserRole::Manajemen, 'mgmt@isoc.id'));

    $this->get('/manajemen')->assertOk()->assertSee('Dashboard Manajerial');
    $this->get('/manajemen/progres-lokasi')->assertOk()->assertSee('Progres Lokasi');
    $this->get('/manajemen/peserta-nilai')->assertOk()->assertSee('Peserta & Nilai');
    $this->get('/manajemen/laporan-termin')->assertOk()->assertSee('Laporan & Termin');
    $this->get('/manajemen/detail-lokasi')->assertOk();
    $this->get('/manajemen/tutor-tot')->assertOk()->assertSee('Data tutor');
    $this->get('/manajemen/bukti-dukung')->assertOk()->assertSee('Data bukti dukung');
    $this->get('/manajemen/profile')->assertOk();
    foreach (['/superadmin', '/superadmin/users', '/superadmin/learning-events', '/admin', '/tutor', '/peserta'] as $url) {
        $this->get($url)->assertForbidden();
    }
});

test('panel manajemen tidak memuat resource yang bisa mengubah data', function () {
    expect(Filament::getPanel('manajemen')->getResources())->toBe([]);
});

test('role lain tidak bisa membuka panel manajemen', function () {
    foreach ([UserRole::SuperAdmin, UserRole::Admin, UserRole::Tutor, UserRole::Peserta] as $i => $role) {
        $this->actingAs(mgmtUser($role, "role{$i}@isoc.id"));
        foreach (['/manajemen', '/manajemen/progres-lokasi', '/manajemen/detail-lokasi', '/manajemen/peserta-nilai', '/manajemen/tutor-tot', '/manajemen/bukti-dukung', '/manajemen/laporan-termin'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }
});

test('dashboard manajemen menampilkan progres seluruh event', function () {
    $school = School::query()->create(['name' => 'Lokus Uji']);
    LearningEvent::query()->create([
        'title' => 'Digital Safety Champions - Uji', 'slug' => 'dsc-uji', 'school_id' => $school->id,
        'status' => 'active', 'workflow_status' => 'verified_term_1', 'publish_approval_status' => 'published', 'is_published' => true, 'registration_open' => true,
        'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'target_participants' => 100, 'target_tutors' => 3,
    ]);
    $this->actingAs(mgmtUser(UserRole::Manajemen, 'mgmt@isoc.id'));
    Filament::setCurrentPanel(Filament::getPanel('manajemen'));

    Livewire::test(ManagementKpi::class)->assertOk()->assertSee('Lokasi berjalan')->assertSee('1 / 20')->assertSee('Kualitas belajar')->assertSee('Penyelesaian');
    Livewire::test(ManagementProgress::class)->assertOk()->assertSee('Uji')->assertSee('Progres per lokasi')
        ->set('filter', 'done')->assertSee('Belum ada event pada filter ini.');
    Livewire::test(ManagementLocationChart::class)->assertOk();
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementTrendChart::class)->assertOk()->set('filter', 'all')->assertOk();
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementLocationProgressChart::class)->assertOk();
    expect(ProgramMetrics::get()->trend(30)['labels'])->toHaveCount(30);
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementStages::class)->assertOk()->assertSee('Tahapan seluruh lokasi');
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementFunnel::class)->assertOk()->assertSee('Corong peserta');
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementScoreChart::class)->assertOk();
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementReports::class)->assertOk()->assertSee('Uji')->assertSee('Belum ada');
    expect(Filament::getCurrentPanel()->getId())->toBe('manajemen');
});

test('admin RTIK pusat bisa membuat akun manajemen', function () {
    expect(UserRole::options())->toHaveKey('manajemen', 'Manajemen')
        ->and(UserRole::Manajemen->panelId())->toBe('manajemen');
    expect(ManagementDashboard::canAccess())->toBeFalse();
});

test('halaman data manajemen menampilkan data mentah tanpa aksi ubah/hapus', function () {
    $school = School::query()->create(['name' => 'Lokus Data']);
    $event = LearningEvent::query()->create([
        'title' => 'Digital Safety Champions - Data', 'slug' => 'dsc-data', 'school_id' => $school->id,
        'status' => 'active', 'workflow_status' => 'verified_term_1', 'publish_approval_status' => 'published', 'is_published' => true, 'registration_open' => true,
        'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'target_participants' => 100, 'target_tutors' => 3,
    ]);
    $pesertaUser = mgmtUser(UserRole::Peserta, 'siswa.data@isoc.id');
    $participant = \App\Models\Participant::query()->create(['user_id' => $pesertaUser->id, 'school_id' => $school->id, 'gender' => 'P', 'nik' => '3201999999999999']);
    $participant->learningEvents()->attach($event->id, ['status' => 'registered']);

    $this->actingAs(mgmtUser(UserRole::Manajemen, 'mgmt@isoc.id'));
    Filament::setCurrentPanel(Filament::getPanel('manajemen'));

    Livewire::test(\App\Filament\Manajemen\Pages\ParticipantScoresPage::class)
        ->assertCanSeeTableRecords([$participant])
        ->assertDontSee('3201999999999999');

    $detail = Livewire::test(\App\Filament\Manajemen\Pages\LocationDetailPage::class, ['eventId' => $event->id])
        ->assertSee('Syarat laporan final')
        ->assertCanSeeTableRecords([$participant]);
    foreach (['tutor', 'bukti', 'sertifikat'] as $tab) {
        $detail->call('setTab', $tab)->assertOk()->assertSet('tab', $tab);
    }

    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementAlerts::class)->assertOk()->assertSee('Perlu perhatian');
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementTutorSummary::class)->assertOk();
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementEvidenceSummary::class)->assertOk();
    Livewire::test(\App\Filament\Manajemen\Widgets\ManagementDemographics::class)->assertOk()->assertSee('Perempuan');

    // Tabel manajemen tidak punya aksi baris maupun bulk action.
    $table = Livewire::test(\App\Filament\Manajemen\Pages\EvidencePage::class)->instance()->getTable();
    expect($table->getActions())->toBe([])->and($table->getBulkActions())->toBe([]);
});
