<?php

use App\Enums\UserRole;
use App\Filament\Resources\EvidenceResource\Pages\ManageEvidence;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\User;
use App\Support\TorEventTemplate;
use Filament\Facades\Filament;
use Livewire\Livewire;

function qaEvent(array $attributes = []): LearningEvent
{
    $school = School::query()->create(['name' => 'SMA QA ' . uniqid()]);

    return LearningEvent::query()->create([
        'title' => 'Event QA', 'slug' => 'event-qa-' . uniqid(), 'school_id' => $school->id,
        'status' => 'active', 'is_published' => true, 'registration_open' => true,
        'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(3), 'target_participants' => 100,
        ...$attributes,
    ]);
}

function qaPayload(string $email): array
{
    return ['participant_category' => 'pelajar', 'name' => 'Peserta QA', 'email' => $email, 'phone' => '0812', 'nik' => '0000000000000000', 'nis' => '0000000000', 'grade' => 'SMA 11', 'organization' => 'SMA QA',
        'gender' => 'P', 'password' => 'password123', 'password_confirmation' => 'password123', 'consent' => '1'];
}

test('pendaftaran lewat link ID event (QR) tetap terdaftar ke event dan lokasinya', function () {
    $event = qaEvent();

    $this->get(route('event.register', (string) $event->id))->assertOk()->assertSee($event->title);
    $this->post(route('event.register.store', (string) $event->id), qaPayload('qa.id@isoc.id'))->assertRedirect();

    $participant = User::query()->where('email', 'qa.id@isoc.id')->firstOrFail()->participant;
    expect($participant->school_id)->toBe($event->school_id)
        ->and($participant->learningEvents()->whereKey($event->id)->exists())->toBeTrue();
});

test('link event yang tidak dikenal menampilkan 404, bukan mendaftar ke lokasi lain', function () {
    qaEvent();

    $this->get(route('event.register', 'event-tidak-ada'))->assertNotFound();
    $this->post(route('event.register.store', 'event-tidak-ada'), qaPayload('qa.salah@isoc.id'))->assertNotFound();

    expect(User::query()->where('email', 'qa.salah@isoc.id')->exists())->toBeFalse();
});

test('judul event otomatis tidak memakai huruf kapital semua dari API wilayah', function () {
    expect(TorEventTemplate::title('KOTA BEKASI'))->toBe('Digital Safety Champions - Kota Bekasi')
        ->and(TorEventTemplate::title('Pangkalpinang'))->toBe('Digital Safety Champions - Pangkalpinang');
});

test('rundown TOR tidak menggandakan awalan modul', function () {
    $activities = collect(TorEventTemplate::rundown('09:00', ['Modul 1: Kenali Online Scam', 'Modul 2: Lindungi Device']))->pluck('activity');

    expect($activities)->toContain('Materi 1: Kenali Online Scam', 'Materi 2: Lindungi Device')
        ->and($activities->implode('|'))->not->toContain('Materi 1: Modul');
});

test('admin RTIK pusat dapat menyetujui bukti dukung sekaligus', function () {
    $event = qaEvent();
    $superAdmin = User::query()->create(['name' => 'Pusat', 'email' => 'qa.pusat@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    $evidence = collect(['absensi_basah', 'video_slogan', 'praktik_microsite'])
        ->map(fn (string $type) => Evidence::query()->create(['learning_event_id' => $event->id, 'school_id' => $event->school_id, 'type' => $type, 'status' => 'pending']));

    Filament::setCurrentPanel(Filament::getPanel('superadmin'));
    $this->actingAs($superAdmin);

    Livewire::test(ManageEvidence::class)->callTableBulkAction('approveSelected', $evidence);

    expect(Evidence::query()->where('status', 'approved')->count())->toBe(3);
});

test('label role memakai istilah ISOC', function () {
    expect(UserRole::SuperAdmin->label())->toBe('Admin RTIK Pusat')
        ->and(UserRole::Admin->label())->toBe('Fasilitator (Admin RTIK Daerah)');
});

test('KPI dashboard fasilitator tanpa sekolah utama hanya menghitung event miliknya', function () {
    $own = qaEvent();
    qaEvent();
    qaEvent();
    $admin = User::query()->create(['name' => 'Fasilitator QA', 'email' => 'qa.fasil@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true]);
    $own->update(['created_by' => $admin->id]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(\App\Filament\Widgets\KpiOverview::class)
        ->assertSeeInOrder(['Lokasi', '1', 'Event', '1']);
});
