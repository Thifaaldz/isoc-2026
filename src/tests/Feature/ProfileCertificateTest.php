<?php

use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;

function profileCertificateFixture(): array
{
    $school = School::query()->create(['name' => 'SMA Profil', 'status' => 'active']);
    $facilitator = profileCertificateUser(UserRole::Admin, 'fasil', $school->id);
    $event = LearningEvent::query()->create([
        'title' => 'Event Profil Sertifikat', 'slug' => 'event-profil-sertifikat', 'school_id' => $school->id, 'created_by' => $facilitator->id,
        'status' => 'active', 'is_published' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3),
    ]);

    return [$school, $event, $facilitator];
}

function profileCertificateUser(UserRole $role, string $name, ?int $schoolId = null): User
{
    return User::query()->create(['name' => $name, 'email' => "{$name}.profil@isoc.id", 'password' => 'password', 'role' => $role, 'is_active' => true, 'school_id' => $schoolId]);
}

function profileParticipantCertificate(LearningEvent $event, Participant $participant, string $status): Certificate
{
    return Certificate::withoutEvents(fn () => Certificate::query()->create([
        'learning_event_id' => $event->id, 'participant_id' => $participant->id, 'number' => 'DSC/PROFIL/' . $participant->id,
        'status' => $status, 'eligibility_status' => 'eligible', 'issued_at' => now(),
    ]));
}

test('peserta hanya melihat sertifikat miliknya yang sudah terbit di profil', function () {
    [$school, $event] = profileCertificateFixture();
    $user = profileCertificateUser(UserRole::Peserta, 'peserta', $school->id);
    $participant = Participant::query()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $participant->learningEvents()->attach($event->id);

    $this->actingAs($user)->get('/peserta/profile')->assertOk()->assertSee('Sertifikat Saya')->assertSee('Belum ada sertifikat yang terbit');

    $certificate = Certificate::query()->where('participant_id', $participant->id)->firstOrFail();
    Certificate::withoutEvents(fn () => $certificate->update(['status' => 'revoked', 'number' => 'DSC/DICABUT']));
    $this->actingAs($user)->get('/peserta/profile')->assertDontSee('DSC/DICABUT');

    Certificate::withoutEvents(fn () => $certificate->update(['status' => 'issued', 'number' => 'DSC/TERBIT', 'issued_at' => now()]));
    $this->actingAs($user)->get('/peserta/profile')
        ->assertSee('DSC/TERBIT')
        ->assertSee(route('certificates.download-pdf', $certificate), false);
});

test('tutor mencetak sertifikat narasumber dari profil', function () {
    [$school, $event] = profileCertificateFixture();
    $user = profileCertificateUser(UserRole::Tutor, 'tutor', $school->id);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $school->id, 'institution' => 'RTIK Uji', 'tot_completed' => false]);

    // Belum lulus ToT: profil tetap bisa dibuka, menu lain tetap diarahkan ke pelatihan.
    $this->actingAs($user)->get('/tutor/profile')->assertOk()->assertSee('Belum ada event');
    $this->actingAs($user)->get('/tutor')->assertRedirect(url('/tutor/pelatihan-tutor'));

    $event->tutors()->attach($tutor->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $this->actingAs($user)->get('/tutor/profile')
        ->assertOk()
        ->assertSee('Event Profil Sertifikat')
        ->assertSee('DSC-NARASUMBER/')
        ->assertSee(route('tutor-certificates.download', $event), false);
});

test('menu sertifikat tidak ada di sidebar tutor, cetak lewat profil', function () {
    $classes = collect(\App\Providers\Filament\TutorPanelProvider::NAVIGATION)->flatten();

    expect($classes)->not->toContain(\App\Filament\Pages\TutorCertificate::class)
        ->and($classes)->toContain(\App\Filament\Pages\Profile::class);
});

test('fasilitator hanya melihat sertifikat event buatannya di profil', function () {
    [$school, $event, $facilitator] = profileCertificateFixture();
    $other = profileCertificateUser(UserRole::Admin, 'lain', $school->id);

    $this->actingAs($facilitator)->get('/admin/profile')
        ->assertOk()
        ->assertSee('DSC-NARASUMBER-F/')
        ->assertSee(route('facilitator-certificates.download', $event), false);

    auth()->logout();
    $this->flushSession();
    $this->actingAs($other)->get('/admin/profile')->assertOk()->assertDontSee('Event Profil Sertifikat');
});

test('admin pusat diarahkan ke menu e-Certificate dari profil', function () {
    $user = profileCertificateUser(UserRole::SuperAdmin, 'pusat');

    $this->actingAs($user)->get('/superadmin/profile')->assertOk()->assertSee('e-Certificate')->assertSee('/superadmin/certificates', false);
});
