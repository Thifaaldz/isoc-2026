<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\TrainingSession;
use App\Models\Tutor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function secUser(UserRole $role, string $email, ?int $schoolId = null): User
{
    return User::query()->create(['name' => strtok($email, '@'), 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true, 'school_id' => $schoolId]);
}

function secEvent(School $school, ?User $creator = null, array $attributes = []): LearningEvent
{
    return LearningEvent::query()->create([
        'title' => 'Event ' . uniqid(), 'slug' => 'event-' . uniqid(), 'school_id' => $school->id, 'created_by' => $creator?->id,
        'status' => 'draft', 'workflow_status' => 'draft', 'publish_approval_status' => 'draft', 'is_published' => false, 'registration_open' => false,
        'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'target_participants' => 100, 'target_tutors' => 1, ...$attributes,
    ]);
}

test('admin daerah tidak bisa mem-publish atau mengubah status event lewat request yang dimanipulasi', function () {
    $school = School::query()->create(['name' => 'S']);
    $admin = secUser(UserRole::Admin, 'adm.sec@isoc.id', $school->id);
    $event = secEvent($school, $admin);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);
    Livewire::test(\App\Filament\Resources\LearningEventResource\Pages\EditLearningEvent::class, ['record' => $event->getRouteKey()])
        ->set('data.workflow_status', 'verified_term_2')->set('data.is_published', true)
        ->set('data.registration_open', true)->set('data.publish_approval_status', 'published')
        ->call('save');

    $event->refresh();
    expect($event->workflow_status)->toBe('draft')
        ->and($event->is_published)->toBeFalse()
        ->and($event->registration_open)->toBeFalse()
        ->and($event->publish_approval_status)->toBe('draft');
});

test('sertifikat tidak bisa diterbitkan bila peserta belum eligible', function () {
    $school = School::query()->create(['name' => 'S']);
    $event = secEvent($school);
    $participant = Participant::query()->create(['user_id' => secUser(UserRole::Peserta, 'p.sec@isoc.id', $school->id)->id, 'school_id' => $school->id]);
    $certificate = Certificate::query()->create(['learning_event_id' => $event->id, 'participant_id' => $participant->id, 'number' => 'SEC/1', 'status' => 'pending', 'eligibility_status' => 'pending']);

    $certificate->update(['status' => 'issued', 'issued_at' => now(), 'eligibility_status' => 'eligible']);

    expect($certificate->refresh()->status)->toBe('pending')->and($certificate->eligibility_status)->toBe('blocked');
});

test('template absensi hanya untuk pusat atau admin/tutor pengelola lokasi', function () {
    $schoolA = School::query()->create(['name' => 'A']);
    $schoolB = School::query()->create(['name' => 'B']);
    $session = TrainingSession::query()->create(['school_id' => $schoolA->id, 'title' => 'Sesi 1', 'date' => now()->toDateString()]);

    $this->actingAs(secUser(UserRole::Peserta, 'p.abs@isoc.id', $schoolA->id))->get(route('attendance.template', $session))->assertForbidden();
    $this->flushSession();
    $this->actingAs(secUser(UserRole::Admin, 'adm.lain@isoc.id', $schoolB->id))->get(route('attendance.template', $session))->assertForbidden();
    // Fasilitator di lokasi yang sama tetapi tanpa event miliknya di lokasi itu tidak boleh membuka absensi.
    $this->flushSession();
    $this->actingAs(secUser(UserRole::Admin, 'adm.a@isoc.id', $schoolA->id))->get(route('attendance.template', $session))->assertForbidden();
    $this->flushSession();
    $owner = secUser(UserRole::Admin, 'adm.owner@isoc.id', $schoolA->id);
    secEvent($schoolA, $owner);
    $this->actingAs($owner)->get(route('attendance.template', $session))->assertOk();
});

test('fasilitator dan tutor hanya melihat event yang mereka buat atau dampingi', function () {
    $school = School::query()->create(['name' => 'Lokasi Sama']);
    $adminA = secUser(UserRole::Admin, 'fasil.a@isoc.id', $school->id);
    $adminB = secUser(UserRole::Admin, 'fasil.b@isoc.id', $school->id);
    $eventA = secEvent($school, $adminA, ['title' => 'Event Fasilitator A']);
    $eventB = secEvent($school, $adminB, ['title' => 'Event Fasilitator B']);
    $tutorUser = secUser(UserRole::Tutor, 'tutor.a@isoc.id', $school->id);
    $tutor = \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $school->id, 'tot_completed' => true]);
    $eventA->tutors()->attach($tutor->id, ['status' => 'assigned']);
    $participant = Participant::query()->create(['user_id' => secUser(UserRole::Peserta, 'p.b@isoc.id', $school->id)->id, 'school_id' => $school->id]);
    $certificateB = Certificate::query()->create(['learning_event_id' => $eventB->id, 'participant_id' => $participant->id, 'number' => 'SEC/B', 'status' => 'issued', 'eligibility_status' => 'eligible']);

    // Daftar Kelola Seminar fasilitator A: hanya event A.
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($adminA);
    expect(\App\Filament\Resources\LearningEventResource::getEloquentQuery()->pluck('title')->all())->toBe(['Event Fasilitator A']);
    $this->get(\App\Filament\Resources\LearningEventResource::getUrl('view', ['record' => $eventB], panel: 'admin'))->assertNotFound();
    $this->get(route('reports.events.activity.preview', $eventB))->assertForbidden();
    $this->get(route('certificates.event-download', $eventB))->assertForbidden();
    $this->get(route('certificates.preview-pdf', $certificateB))->assertForbidden();

    // Tutor: hanya event yang ditugaskan kepadanya.
    $this->flushSession();
    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    $this->actingAs($tutorUser);
    expect(\App\Filament\Resources\LearningEventResource::getEloquentQuery()->pluck('title')->all())->toBe(['Event Fasilitator A']);
    $this->get(route('reports.events.activity.preview', $eventB))->assertForbidden();
});

test('admin daerah dan tutor hanya bisa membuka PDF sertifikat event yang mereka kelola', function () {
    $school = School::query()->create(['name' => 'S']);
    $owner = secUser(UserRole::Admin, 'owner@isoc.id', $school->id);
    $event = secEvent($school, $owner);
    $participant = Participant::query()->create(['user_id' => secUser(UserRole::Peserta, 'p.pdf@isoc.id', $school->id)->id, 'school_id' => $school->id]);
    $certificate = Certificate::query()->create(['learning_event_id' => $event->id, 'participant_id' => $participant->id, 'number' => 'SEC/2', 'status' => 'pending', 'eligibility_status' => 'pending']);
    $otherSchool = School::query()->create(['name' => 'Lain']);

    $this->actingAs(secUser(UserRole::Admin, 'other@isoc.id', $otherSchool->id))->get(route('certificates.preview-pdf', $certificate))->assertForbidden();
    $this->flushSession();
    $this->actingAs(secUser(UserRole::Tutor, 'tutor.lain@isoc.id', $school->id))->get(route('certificates.preview-pdf', $certificate))->assertForbidden();
    $this->flushSession();
    $this->actingAs(secUser(UserRole::Admin, 'other2@isoc.id', $otherSchool->id))->get(route('reports.events.activity.preview', $event))->assertForbidden();
});

test('upload SVG ditolak pada form peserta dan pendaftaran publik', function () {
    Storage::fake('public');
    $school = School::query()->create(['name' => 'S']);
    $user = secUser(UserRole::Peserta, 'p.svg@isoc.id', $school->id);
    Participant::query()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $event = secEvent($school, null, ['status' => 'active', 'is_published' => true, 'registration_open' => true]);
    $svg = fn () => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    Filament::setCurrentPanel(Filament::getPanel('peserta'));
    $this->actingAs($user);
    $user->participant->learningEvents()->attach($event->id, ['admin_approval_status' => 'pending', 'tutor_approval_status' => 'pending']);

    Livewire::test(\App\Filament\Widgets\ParticipantDashboardOverview::class)
        ->set('instagramEvidence', $svg())
        ->call('submitInstagramEvidence')
        ->assertHasErrors('instagramEvidence');
    expect(\App\Models\Evidence::query()->where('uploaded_by', $user->id)->exists())->toBeFalse();
});

test('admin dan tutor tidak bisa mengganti sekolah akunnya sendiri dari profil', function () {
    $own = School::query()->create(['name' => 'Sendiri']);
    $other = School::query()->create(['name' => 'Lain']);
    $admin = secUser(UserRole::Admin, 'adm.profil@isoc.id', $own->id);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);
    Livewire::test(\App\Filament\Pages\Profile::class)
        ->set('profileData.school_id', $other->id)
        ->call('updateProfile');

    expect($admin->refresh()->school_id)->toBe($own->id);
});

test('endpoint publik dibatasi rate limit', function () {
    $school = School::query()->create(['name' => 'S']);
    secEvent($school, null, ['status' => 'active', 'is_published' => true, 'registration_open' => true, 'slug' => 'event-throttle']);

    foreach (range(1, 10) as $i) {
        $this->post(route('event.register.store', 'event-throttle'), []);
    }

    $this->post(route('event.register.store', 'event-throttle'), [])->assertStatus(429);
});
