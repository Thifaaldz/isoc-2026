<?php

use App\Enums\UserRole;
use App\Filament\Pages\TutorCertificate;
use App\Models\CertificateTemplate;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use App\Services\TutorCertificateService;
use Database\Seeders\TutorCertificateTemplateSeeder;
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

test('sertifikat tutor memakai template tutor, sedangkan peserta tetap memakai template peserta', function () {
    [$event] = tutorCertificateFixture();
    $participantTemplate = CertificateTemplate::query()->create(['name' => 'Desain Peserta', 'elements' => []]);
    $tutorTemplate = CertificateTemplate::query()->create(['name' => 'Desain Tutor', 'elements' => []]);
    $event->update(['certificate_template_id' => $participantTemplate->id]);

    // Belum memilih template tutor: tetap memakai template peserta seperti event lama.
    expect(app(TutorCertificateService::class)->template($event->fresh())->id)->toBe($participantTemplate->id);

    $event->update(['tutor_certificate_template_id' => $tutorTemplate->id]);
    $event = $event->fresh();

    expect(app(TutorCertificateService::class)->template($event)->id)->toBe($tutorTemplate->id)
        ->and($event->certificateTemplate->id)->toBe($participantTemplate->id);
});

test('seeder template narasumber tidak menimpa hasil edit maupun mengubah data lain', function () {
    [$event] = tutorCertificateFixture();
    $default = CertificateTemplate::query()->create(['name' => 'Desain Peserta', 'is_default' => true, 'elements' => []]);
    $event->update(['certificate_template_id' => $default->id]);

    $this->seed(TutorCertificateTemplateSeeder::class);

    $template = CertificateTemplate::query()->where('name', CertificateTemplate::TUTOR_TEMPLATE_NAME)->sole();
    expect($template->is_default)->toBeFalse()
        ->and(collect($template->elements)->firstWhere('label', 'Narasi kegiatan')['content'])->toContain('NARASUMBER');

    $template->update(['elements' => []]);
    $this->seed(TutorCertificateTemplateSeeder::class);

    $event = $event->fresh();
    expect(CertificateTemplate::query()->where('name', CertificateTemplate::TUTOR_TEMPLATE_NAME)->count())->toBe(1)
        ->and($template->fresh()->elements)->toBe([])
        ->and($default->fresh()->is_default)->toBeTrue()
        ->and($event->certificate_template_id)->toBe($default->id)
        ->and($event->tutor_certificate_template_id)->toBeNull();
});

test('sertifikat tutor ditolak untuk tutor yang belum ditugaskan atau masih menunggu validasi', function () {
    [$event, $user, $tutor] = tutorCertificateFixture();

    $this->actingAs($user)->get(route('tutor-certificates.preview', $event))->assertForbidden();

    $event->tutors()->attach($tutor->id, ['status' => 'pending']);
    $this->actingAs($user)->get(route('tutor-certificates.preview', $event))->assertForbidden();
});
