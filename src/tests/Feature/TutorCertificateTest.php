<?php

use App\Enums\UserRole;
use App\Filament\Pages\TutorCertificate;
use App\Filament\Resources\CertificateResource;
use App\Http\Controllers\TutorSignatureVerificationController;
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

    $this->actingAs($user)->get('/tutor/sertifikat-tutor')->assertOk()->assertSee($event->title)->assertSee('DSC-NARASUMBER/');

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

    // Template narasumber belum ada: memakai template peserta.
    expect(app(TutorCertificateService::class)->template($event->fresh())->id)->toBe($participantTemplate->id);

    // Event belum memilih template tutor: otomatis memakai template narasumber.
    $this->seed(TutorCertificateTemplateSeeder::class);
    expect(app(TutorCertificateService::class)->template($event->fresh())->name)->toBe(CertificateTemplate::TUTOR_TEMPLATE_NAME);

    $event->update(['tutor_certificate_template_id' => $tutorTemplate->id]);
    $event = $event->fresh();

    expect(app(TutorCertificateService::class)->template($event)->id)->toBe($tutorTemplate->id)
        ->and($event->certificateTemplate->id)->toBe($participantTemplate->id);
});

test('sertifikat tutor hanya satu halaman', function () {
    [$event, , $tutor] = tutorCertificateFixture();

    $pdf = app(TutorCertificateService::class)->pdf($event, $tutor->load(['user', 'school']));
    $pdf->output();

    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
});

test('sertifikat semua tutor terpisah dari peserta, satu halaman per tutor, dan hanya untuk pengelola event', function () {
    [$event, $tutorUser, $tutor] = tutorCertificateFixture();
    $secondUser = User::query()->create(['name' => 'Tutor Kedua', 'email' => 'tutor.kedua@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true]);
    $second = Tutor::query()->create(['user_id' => $secondUser->id, 'school_id' => $tutor->school_id, 'institution' => 'RTIK Uji']);
    $superAdmin = User::query()->create(['name' => 'Pusat', 'email' => 'pusat.sertifikat@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    $owner = User::query()->create(['name' => 'Fasilitator', 'email' => 'fasilitator.sertifikat@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true]);
    $otherAdmin = User::query()->create(['name' => 'Fasilitator Lain', 'email' => 'fasilitator.lain@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true]);
    $event->update(['created_by' => $owner->id]);

    $this->actingAs($superAdmin)->get(route('tutor-certificates.event-download', $event))->assertNotFound();

    $event->tutors()->attach([$tutor->id, $second->id], ['status' => 'assigned', 'assigned_at' => now()]);

    $pdf = app(TutorCertificateService::class)->eventPdf($event);
    $pdf->output();
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(2);

    $this->actingAs($superAdmin)->get(route('tutor-certificates.event-download', $event))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($owner)->get(route('tutor-certificates.event-download', $event))->assertOk();
    $this->actingAs($otherAdmin)->get(route('tutor-certificates.event-download', $event))->assertForbidden();
    $this->actingAs($tutorUser)->get(route('tutor-certificates.event-download', $event))->assertForbidden();
});

test('menu e-Certificate punya pilihan peserta dan tutor dengan nomor tutor otomatis', function () {
    [$event, $tutorUser, $tutor] = tutorCertificateFixture();
    $event->tutors()->attach($tutor->id, ['status' => 'assigned', 'assigned_at' => now()]);
    $superAdmin = User::query()->create(['name' => 'Pusat', 'email' => 'pusat.menu@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    $number = app(TutorCertificateService::class)->number($event, $tutor);

    Filament::setCurrentPanel(Filament::getPanel('superadmin'));

    $this->actingAs($superAdmin)->get(CertificateResource::getUrl())
        ->assertOk()->assertSee('Sertifikat Peserta')->assertSee('Sertifikat Tutor');

    $this->actingAs($superAdmin)->get(CertificateResource::getUrl('tutors') . '?event=' . $event->id)
        ->assertOk()
        ->assertSee('Sertifikat Peserta')
        ->assertSee('Tutor Bersertifikat')
        ->assertSee($number)
        ->assertSee(route('tutor-certificates.manager-download', [$event, $tutor]), false);

    $this->actingAs($superAdmin)->get(route('tutor-certificates.manager-download', [$event, $tutor]))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($tutorUser)->get(route('tutor-certificates.manager-download', [$event, $tutor]))->assertForbidden();

    // Tutor yang tidak ditugaskan di event ini tidak punya sertifikat.
    $event->tutors()->detach($tutor->id);
    $this->actingAs($superAdmin)->get(route('tutor-certificates.manager-download', [$event, $tutor]))->assertNotFound();
});

test('fasilitator punya sertifikat narasumber untuk event yang dibuatnya', function () {
    [$event, $tutorUser] = tutorCertificateFixture();
    $facilitator = User::query()->create(['name' => 'Fasilitator Daerah', 'email' => 'fasil.narasumber@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true, 'school_id' => $event->school_id]);
    $other = User::query()->create(['name' => 'Fasilitator Lain', 'email' => 'fasil.lain@isoc.id', 'password' => 'password', 'role' => UserRole::Admin, 'is_active' => true]);
    $superAdmin = User::query()->create(['name' => 'Pusat', 'email' => 'pusat.fasil@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    $service = app(TutorCertificateService::class);

    // Event buatan admin pusat tidak punya fasilitator.
    $event->update(['created_by' => $superAdmin->id]);
    $this->actingAs($superAdmin)->get(route('facilitator-certificates.download', $event))->assertNotFound();

    $event->update(['created_by' => $facilitator->id]);
    $event = $event->fresh();
    $number = $service->facilitatorNumber($event, $facilitator);
    expect($number)->toStartWith('DSC-NARASUMBER-F/');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($facilitator)->get(CertificateResource::getUrl('facilitators') . '?event=' . $event->id)
        ->assertOk()->assertSee('Sertifikat Fasilitator')->assertSee('Fasilitator Daerah')->assertSee($number);

    $this->actingAs($facilitator)->get(route('facilitator-certificates.download', $event))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($superAdmin)->get(route('facilitator-certificates.preview', $event))->assertOk();
    $this->actingAs($other)->get(route('facilitator-certificates.download', $event))->assertForbidden();
    $this->actingAs($tutorUser)->get(route('facilitator-certificates.download', $event))->assertForbidden();

    $pdf = $service->facilitatorPdf($event, $facilitator);
    $pdf->output();
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);

    $url = TutorSignatureVerificationController::facilitatorUrl($event, $facilitator);
    auth()->logout();
    $this->get($url)->assertOk()->assertSee('TANDA TANGAN ELEKTRONIK SAH')->assertSee('Fasilitator Daerah')->assertDontSee('Status ToT');
    $this->get($url . 'x')->assertOk()->assertSee('TANDA TANGAN TIDAK VALID')->assertDontSee('Fasilitator Daerah');
});

test('QR verifikasi tutor tetap sah setelah halaman verifikasi diubah', function () {
    [$event, , $tutor] = tutorCertificateFixture();
    $event->tutors()->attach($tutor->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $this->get(TutorSignatureVerificationController::url($event, $tutor))
        ->assertOk()->assertSee('TANDA TANGAN ELEKTRONIK SAH')->assertSee('Tutor Bersertifikat')->assertSee('Status ToT');
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
