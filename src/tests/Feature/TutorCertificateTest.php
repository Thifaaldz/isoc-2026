<?php

use App\Enums\UserRole;
use App\Filament\Pages\TutorCertificate;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function tutorCertificateFixture(): array
{
    $school = School::query()->create(['name' => 'SMA Sertifikat Tutor', 'status' => 'active']);
    $event = LearningEvent::query()->create([
        'title' => 'Event Sertifikat Tutor', 'slug' => 'event-sertifikat-tutor', 'school_id' => $school->id,
        'status' => 'active', 'is_published' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->subDay()->addHours(3),
    ]);
    $user = User::query()->create(['name' => 'Tutor Bersertifikat', 'email' => 'tutor.sertifikat@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true]);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $school->id, 'institution' => 'RTIK Uji', 'tot_completed' => false]);

    return [$event, $user, $tutor];
}

test('tutor yang ditugaskan bisa membuka form dan mengunduh sertifikat tanpa syarat ToT', function () {
    [$event, $user, $tutor] = tutorCertificateFixture();
    $event->tutors()->attach($tutor->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $this->actingAs($user)->get('/tutor/sertifikat-tutor')->assertOk()->assertSee($event->title)->assertSee('DSC-TUTOR/');

    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    Livewire::actingAs($user)->test(TutorCertificate::class)->assertSet('eventId', $event->id);

    $this->actingAs($user)->get(route('tutor-certificates.download', $event))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('sertifikat tutor ditolak untuk tutor yang belum ditugaskan atau masih menunggu validasi', function () {
    [$event, $user, $tutor] = tutorCertificateFixture();

    $this->actingAs($user)->get(route('tutor-certificates.preview', $event))->assertForbidden();

    $event->tutors()->attach($tutor->id, ['status' => 'pending']);
    $this->actingAs($user)->get(route('tutor-certificates.preview', $event))->assertForbidden();
});
