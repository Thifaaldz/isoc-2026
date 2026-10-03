<?php

use App\Enums\UserRole;
use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\EvidenceResource;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Certificate;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\Participant;
use App\Models\School;
use App\Models\User;
use App\Services\CertificateEligibilityService;

function makeUser(UserRole $role, string $email): User
{
    return User::query()->create([
        'name' => ucfirst(strtok($email, '@')),
        'email' => $email,
        'password' => 'password',
        'role' => $role,
        'is_active' => true,
    ]);
}

function makeEvent(array $attributes = []): LearningEvent
{
    $school = School::query()->create(['name' => 'SMK Uji', 'status' => 'active']);

    return LearningEvent::query()->create([
        'title' => 'Event Uji',
        'slug' => 'event-uji',
        'school_id' => $school->id,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
        'status' => 'active',
        'is_published' => true,
        'registration_open' => true,
        'target_participants' => 100,
        ...$attributes,
    ]);
}

function enrol(LearningEvent $event, string $email = 'siswa@isoc.id'): Participant
{
    $user = makeUser(UserRole::Peserta, $email);
    $participant = Participant::query()->create(['user_id' => $user->id, 'school_id' => $event->school_id]);
    $participant->learningEvents()->attach($event->id, [
        'status' => 'registered',
        'admin_approval_status' => 'approved',
        'tutor_approval_status' => 'approved',
    ]);

    return $participant;
}

function completeLearning(LearningEvent $event, Participant $participant): void
{
    foreach (['pre', 'quiz', 'post'] as $type) {
        $assessment = Assessment::query()->create([
            'learning_event_id' => $event->id,
            'type' => $type,
            'title' => strtoupper($type),
            'is_open' => true,
            'questions' => [],
        ]);

        AssessmentAttempt::query()->create([
            'assessment_id' => $assessment->id,
            'participant_id' => $participant->id,
            'score' => 100,
            'submitted_at' => now(),
        ]);
    }

    MicrositePractice::query()->create([
        'participant_id' => $participant->id,
        'learning_event_id' => $event->id,
        'sid_url' => 'https://s.id/uji',
    ]);
}

function approveRequiredEvidence(LearningEvent $event): void
{
    foreach (array_keys(CertificateEligibilityService::REQUIRED_EVENT_EVIDENCE) as $type) {
        Evidence::query()->create([
            'learning_event_id' => $event->id,
            'school_id' => $event->school_id,
            'type' => $type,
            'status' => 'approved',
        ]);
    }
}

function makeCertificate(LearningEvent $event, Participant $participant, array $attributes = []): Certificate
{
    return Certificate::query()->create([
        'learning_event_id' => $event->id,
        'participant_id' => $participant->id,
        'number' => 'DSC/2026/0001/00001',
        'status' => 'pending',
        'eligibility_status' => 'pending',
        ...$attributes,
    ]);
}

test('tamu yang membuka link sertifikat diarahkan ke login, bukan error 500', function () {
    $event = makeEvent();
    $certificate = makeCertificate($event, enrol($event));

    $this->get(route('certificates.download-pdf', $certificate))->assertRedirect('/login');
    $this->get(route('reports.events.activity.preview', $event))->assertRedirect('/login');
});

test('sertifikat terkunci sampai bukti dukung wajib event disetujui', function () {
    $event = makeEvent();
    $participant = enrol($event);
    completeLearning($event, $participant);
    $certificate = makeCertificate($event, $participant);

    $certificate = app(CertificateEligibilityService::class)->updateCertificate($certificate);

    expect($certificate->eligibility_status)->toBe('blocked')
        ->and($certificate->eligibility_notes)->toContain('Bukti dukung kegiatan belum disetujui');

    approveRequiredEvidence($event);

    expect(app(CertificateEligibilityService::class)->updateCertificate($certificate)->eligibility_status)->toBe('eligible');
});

test('peserta baru bisa mengunduh sertifikat setelah diterbitkan admin', function () {
    $event = makeEvent();
    $participant = enrol($event);
    completeLearning($event, $participant);
    approveRequiredEvidence($event);
    $certificate = makeCertificate($event, $participant);

    $this->actingAs($participant->user)
        ->get(route('certificates.download-pdf', $certificate))
        ->assertForbidden();

    expect($certificate->refresh()->status)->toBe('pending');

    $certificate->update(['status' => 'issued', 'issued_at' => now()]);

    $this->actingAs($participant->user)
        ->get(route('certificates.download-pdf', $certificate))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('preview sertifikat oleh admin pusat tidak menerbitkan sertifikat otomatis', function () {
    $event = makeEvent();
    $participant = enrol($event);
    completeLearning($event, $participant);
    approveRequiredEvidence($event);
    $certificate = makeCertificate($event, $participant);

    $this->actingAs(makeUser(UserRole::SuperAdmin, 'pusat@isoc.id'))
        ->get(route('certificates.preview-pdf', $certificate))
        ->assertOk();

    expect($certificate->refresh()->status)->toBe('pending');
});

test('halaman verifikasi menampilkan pesan untuk nomor yang tidak valid', function () {
    $this->get('/verify/DSC/2026/9999/99999')
        ->assertOk()
        ->assertSee('SERTIFIKAT TIDAK DITEMUKAN')
        ->assertSee('DSC/2026/9999/99999');
});

test('halaman verifikasi menampilkan nama event sertifikat yang valid', function () {
    $event = makeEvent(['title' => 'Seminar Literasi Digital']);
    $participant = enrol($event);
    completeLearning($event, $participant);
    approveRequiredEvidence($event);
    makeCertificate($event, $participant, ['status' => 'issued', 'issued_at' => now(), 'eligibility_status' => 'eligible']);

    $this->get('/verify/DSC/2026/0001/00001')
        ->assertOk()
        ->assertSee('SERTIFIKAT VALID')
        ->assertSee('Seminar Literasi Digital');
});

test('penghitung kuota pendaftaran memakai jumlah peserta event', function () {
    $event = makeEvent();
    enrol($event);
    Participant::query()->create(['user_id' => makeUser(UserRole::Peserta, 'lain@isoc.id')->id]);

    $this->get(route('event.register', $event->slug))
        ->assertOk()
        ->assertSee('1/100');
});

test('pendaftaran dengan email terdaftar memberi pesan untuk login', function () {
    $event = makeEvent();
    enrol($event, 'lama@isoc.id');

    $this->post(route('event.register.store', $event->slug), ['email' => 'lama@isoc.id'])
        ->assertSessionHasErrors(['email' => 'Email ini sudah terdaftar. Silakan login dengan akun tersebut, lalu buka kembali halaman event ini dan klik "Ikuti Event".']);
});

test('peserta yang sudah punya akun bisa mengikuti event baru', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $oldEvent = makeEvent();
    $participant = enrol($oldEvent);
    $newEvent = LearningEvent::query()->create([
        'title' => 'Event Baru',
        'slug' => 'event-baru',
        'school_id' => $oldEvent->school_id,
        'status' => 'active',
        'is_published' => true,
        'registration_open' => true,
    ]);

    $this->actingAs($participant->user)
        ->get(route('event.register', 'event-baru'))
        ->assertOk()
        ->assertSee('Ikuti Event');

    $this->actingAs($participant->user)
        ->post(route('event.join', 'event-baru'), [
            'consent' => '1',
        ])
        ->assertRedirect('/peserta?event=' . $newEvent->id);

    expect($participant->refresh()->isApprovedForEvent($newEvent))->toBeFalse();
});

test('bukti dukung dilengkapi dari dashboard membuka akses event', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $event = makeEvent();
    $participant = enrol($event);
    $participant->update(['joined_wag' => false]);
    $participant->learningEvents()->updateExistingPivot($event->id, ['admin_approval_status' => 'pending', 'tutor_approval_status' => 'pending']);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('peserta'));
    $this->actingAs($participant->user);

    $widget = \Livewire\Livewire::test(\App\Filament\Widgets\ParticipantDashboardOverview::class)
        ->assertSee('Bukti Dukung Peserta')
        ->set('instagramEvidence', \Illuminate\Http\UploadedFile::fake()->image('ig.png'))
        ->call('submitInstagramEvidence')
        ->assertHasNoErrors();

    expect($participant->refresh()->isApprovedForEvent($event))->toBeFalse();

    $widget->call('toggleJoinedWag');

    expect($participant->refresh()->isApprovedForEvent($event))->toBeTrue();
});

test('opsi jawaban diacak tanpa mengubah indeks penilaian', function () {
    $event = makeEvent();
    $participant = enrol($event);
    $assessment = Assessment::query()->create(['learning_event_id' => $event->id, 'type' => 'pre', 'title' => 'Pre', 'is_open' => true]);
    $question = ['options' => [['text' => 'A', 'is_correct' => true], ['text' => 'B'], ['text' => 'C'], ['text' => 'D']]];

    $this->actingAs($participant->user);
    $options = (new \App\Filament\Pages\ParticipantLearning())->displayOptions($assessment, 0, $question);

    expect($options->keys()->sort()->values()->all())->toBe([0, 1, 2, 3])
        ->and($options->get(0)['text'])->toBe('A')
        ->and((new \App\Filament\Pages\ParticipantLearning())->displayOptions($assessment, 0, $question)->keys()->all())
        ->toBe($options->keys()->all());
});

test('hanya admin pusat yang dapat memverifikasi bukti dukung', function () {
    $this->actingAs(makeUser(UserRole::Admin, 'daerah@isoc.id'));
    expect(EvidenceResource::canVerify())->toBeFalse();

    $this->actingAs(makeUser(UserRole::SuperAdmin, 'pusat@isoc.id'));
    expect(EvidenceResource::canVerify())->toBeTrue();
});

test('label menu sertifikat menyesuaikan role', function () {
    $this->actingAs(makeUser(UserRole::Peserta, 'p@isoc.id'));
    expect(CertificateResource::getNavigationLabel())->toBe('Sertifikat Saya');

    $this->actingAs(makeUser(UserRole::SuperAdmin, 's@isoc.id'));
    expect(CertificateResource::getNavigationLabel())->toBe('e-Certificate');
});

test('wizard event hanya menawarkan materi event tipe peserta', function () {
    \App\Models\ModuleTemplate::query()->create(['name' => 'Materi Peserta', 'audience' => 'peserta', 'meeting_count' => 1, 'is_active' => true]);
    \App\Models\ModuleTemplate::query()->create(['name' => 'Materi ToT', 'audience' => 'tutor', 'meeting_count' => 1, 'is_active' => true]);

    expect(\App\Models\ModuleTemplate::query()->forParticipants()->pluck('name')->all())->toBe(['Materi Peserta'])
        ->and(\App\Models\ModuleTemplate::query()->forTutors()->pluck('name')->all())->toBe(['Materi ToT']);
});

test('halaman ToT tutor menampilkan materi event tipe tutor', function () {
    $template = \App\Models\ModuleTemplate::query()->create(['name' => 'Materi ToT Uji', 'audience' => 'tutor', 'meeting_count' => 1, 'is_active' => true]);
    // Di SQLite kolom learning_event_id belum nullable seperti di MariaDB, jadi isi dengan event dummy.
    $meeting = \App\Models\LearningMeeting::query()->create(['learning_event_id' => makeEvent()->id, 'module_template_id' => $template->id, 'order' => 1, 'title' => 'Sesi Fasilitasi Kelas', 'is_published' => true]);
    \App\Models\LearningMaterial::query()->create(['learning_meeting_id' => $meeting->id, 'title' => 'Slide Fasilitasi', 'type' => 'link', 'external_url' => 'https://example.test/slide', 'order' => 1, 'is_published' => true]);
    \App\Models\ModuleTemplate::query()->create(['name' => 'Materi Peserta Uji', 'audience' => 'peserta', 'meeting_count' => 1, 'is_active' => true]);

    $school = School::query()->first();
    $tutorUser = makeUser(UserRole::Tutor, 'tutor.uji@isoc.id');
    \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $school->id]);

    $this->actingAs($tutorUser)
        ->get('/tutor/pelatihan-tutor')
        ->assertOk()
        ->assertSee('Materi ToT: Materi ToT Uji')
        ->assertSee('Sesi Fasilitasi Kelas')
        ->assertSee('Slide Fasilitasi')
        ->assertDontSee('Materi Peserta Uji');
});

test('admin daerah dapat melihat lokasi dari event yang dibuatnya dan lokasi baru di sesi ini', function () {
    $ownSchool = School::query()->create(['name' => 'Sekolah Admin']);
    $eventSchool = School::query()->create(['name' => 'Sekolah Event']);
    $otherSchool = School::query()->create(['name' => 'Sekolah Lain']);
    $newSchool = School::query()->create(['name' => 'Sekolah Baru']);
    $admin = makeUser(UserRole::Admin, 'daerah@isoc.id');
    $admin->update(['school_id' => $ownSchool->id]);
    LearningEvent::query()->create(['title' => 'Event Admin', 'slug' => 'event-admin', 'school_id' => $eventSchool->id, 'created_by' => $admin->id]);

    $this->actingAs($admin);
    \App\Filament\Resources\LearningEventResource::rememberCreatedSchool($newSchool->id);

    expect(\App\Filament\Resources\LearningEventResource::managedSchoolIds())
        ->toContain($ownSchool->id, $eventSchool->id, $newSchool->id)
        ->not->toContain($otherSchool->id);
});

test('seeder mitra membuat enam mitra aktif dengan logo yang tersedia', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $this->seed(\Database\Seeders\PartnerSeeder::class);

    $partners = \App\Models\Partner::query()->where('status', 'active')->get();

    expect($partners->pluck('name')->all())->toContain(
        'ISOC Indonesia Jakarta Chapter', 'Kementerian Komunikasi dan Digital', 'Relawan TIK Indonesia',
        'APJII', '.id Academy', 'Universitas Esa Unggul',
    );
    $partners->each(fn ($partner) => \Illuminate\Support\Facades\Storage::disk('public')->assertExists($partner->logo_path));

    $this->seed(\Database\Seeders\PartnerSeeder::class);
    expect(\App\Models\Partner::query()->count())->toBe(6);
});

test('ToT tutor tidak memundurkan status event yang sudah melewati tahap ToT', function () {
    $event = makeEvent(['workflow_status' => 'final_report_submitted']);
    $tutorUser = makeUser(UserRole::Tutor, 'tutor.status@isoc.id');
    $tutor = \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $event->school_id]);
    $event->tutors()->attach($tutor->id, ['status' => 'assigned']);

    \App\Models\TotAssessment::query()->create(['learning_event_id' => $event->id, 'tutor_id' => $tutor->id, 'score' => 100]);

    expect($event->refresh()->workflow_status)->toBe('final_report_submitted');
});

test('ToT tutor memajukan event dari verified_term_1 dan event tetap bisa di-publish', function () {
    $event = makeEvent(['workflow_status' => 'verified_term_1', 'publish_approval_status' => 'pending']);
    $tutorUser = makeUser(UserRole::Tutor, 'tutor.publish@isoc.id');
    $tutor = \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $event->school_id]);
    $event->tutors()->attach($tutor->id, ['status' => 'assigned']);

    \App\Models\TotAssessment::query()->create(['learning_event_id' => $event->id, 'tutor_id' => $tutor->id, 'score' => 100]);

    expect($event->refresh()->workflow_status)->toBe('tot_completed')
        ->and(\App\Filament\Resources\LearningEventResource::publishableWorkflowStatuses())->toContain('tot_completed');
});

test('peserta dapat melihat dan mengikuti event lain dari menu Cari Event', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $oldEvent = makeEvent();
    $participant = enrol($oldEvent);
    $newEvent = LearningEvent::query()->create(['title' => 'Event Panel Baru', 'slug' => 'event-panel-baru', 'school_id' => $oldEvent->school_id, 'status' => 'active', 'is_published' => true, 'registration_open' => true]);
    LearningEvent::query()->create(['title' => 'Event Draft Tersembunyi', 'slug' => 'event-draft', 'school_id' => $oldEvent->school_id, 'status' => 'draft', 'is_published' => false]);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('peserta'));
    $this->actingAs($participant->user);

    $this->get('/peserta/cari-event')->assertOk()->assertSee('Event Panel Baru')->assertSee('Sudah terdaftar')->assertDontSee('Event Draft Tersembunyi');

    \Livewire\Livewire::test(\App\Filament\Pages\EventCatalog::class)
        ->callAction('join', [
            'consent' => true,
        ], ['event' => $newEvent->id])
        ->assertHasNoActionErrors()
        ->assertRedirect('/peserta?event=' . $newEvent->id);

    expect($participant->learningEvents()->whereKey($newEvent->id)->exists())->toBeTrue()
        ->and($participant->refresh()->isApprovedForEvent($newEvent))->toBeFalse();
});

test('peserta tidak bisa mengikuti event yang pendaftarannya ditutup dari panel', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $oldEvent = makeEvent();
    $participant = enrol($oldEvent);
    $closed = LearningEvent::query()->create(['title' => 'Event Tutup', 'slug' => 'event-tutup', 'school_id' => $oldEvent->school_id, 'status' => 'active', 'is_published' => true, 'registration_open' => false]);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('peserta'));
    $this->actingAs($participant->user);

    \Livewire\Livewire::test(\App\Filament\Pages\EventCatalog::class)
        ->callAction('join', ['consent' => true], ['event' => $closed->id]);

    expect($participant->learningEvents()->whereKey($closed->id)->exists())->toBeFalse();
});

test('peserta tidak bisa mengikuti event yang kuotanya penuh', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $oldEvent = makeEvent();
    $participant = enrol($oldEvent);
    $full = LearningEvent::query()->create(['title' => 'Event Penuh', 'slug' => 'event-penuh', 'school_id' => $oldEvent->school_id, 'status' => 'active', 'is_published' => true, 'registration_open' => true, 'target_participants' => 1]);
    enrol($full, 'pengisi.kuota@isoc.id');

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('peserta'));
    $this->actingAs($participant->user);
    $this->get('/peserta/cari-event')->assertSee('Kuota penuh');

    \Livewire\Livewire::test(\App\Filament\Pages\EventCatalog::class)
        ->callAction('join', ['consent' => true], ['event' => $full->id]);
    expect($participant->learningEvents()->whereKey($full->id)->exists())->toBeFalse();

    $this->post(route('event.join', 'event-penuh'), ['consent' => '1'])
        ->assertSessionHasErrors(['event' => 'Kuota peserta event ini sudah penuh.']);
});

function tutorTemplateWithTests(): \App\Models\ModuleTemplate
{
    $template = \App\Models\ModuleTemplate::query()->create(['name' => 'ToT Uji', 'audience' => 'tutor', 'meeting_count' => 1, 'is_active' => true]);
    $question = [['question' => 'Soal', 'options' => [['text' => 'Salah', 'is_correct' => false], ['text' => 'Benar', 'is_correct' => true]]]];
    foreach (['pre', 'post'] as $type) {
        Assessment::query()->create(['module_template_id' => $template->id, 'type' => $type, 'title' => strtoupper($type) . ' ToT', 'is_open' => true, 'questions' => $question]);
    }

    return $template;
}

test('tutor mengerjakan pre-test dan post-test ToT dari materi tipe tutor dan lulus dengan nilai 100', function () {
    tutorTemplateWithTests();
    $event = makeEvent(['workflow_status' => 'verified_term_1']);
    $tutorUser = makeUser(UserRole::Tutor, 'tutor.tot@isoc.id');
    $tutor = \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $event->school_id]);
    $event->tutors()->attach($tutor->id, ['status' => 'assigned']);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('tutor'));
    $this->actingAs($tutorUser);
    $page = \Livewire\Livewire::test(\App\Filament\Pages\TutorTraining::class);
    $pre = $page->instance()->preTest; $post = $page->instance()->postTest;

    // Post-test terkunci sebelum pre-test.
    $page->call('startTest', $post->id)->assertSet('activeTestId', null);

    $page->call('startTest', $pre->id)->set('testAnswers', [0 => 0])->call('submitTest');
    expect(AssessmentAttempt::query()->where('tutor_id', $tutor->id)->where('assessment_id', $pre->id)->value('score'))->toEqual(0);

    // Post-test salah → belum lulus, boleh diulang; benar → lulus.
    $page->call('startTest', $post->id)->set('testAnswers', [0 => 0])->call('submitTest');
    expect($tutor->refresh()->tot_completed)->toBeFalse();
    $page->call('startTest', $post->id)->set('testAnswers', [0 => 1])->call('submitTest');

    expect($tutor->refresh()->tot_completed)->toBeTrue()
        ->and(\App\Models\TotAssessment::query()->where('tutor_id', $tutor->id)->value('is_perfect'))->toBeTrue();
});

test('halaman preview materi dapat diakses pusat, admin daerah, dan tutor tetapi tidak oleh peserta', function () {
    $event = makeEvent();
    $template = \App\Models\ModuleTemplate::query()->create(['name' => 'Materi Preview', 'audience' => 'peserta', 'meeting_count' => 1, 'is_active' => true]);
    $meeting = \App\Models\LearningMeeting::query()->create(['learning_event_id' => $event->id, 'order' => 1, 'title' => 'Modul Preview', 'is_published' => true]);
    \App\Models\LearningMaterial::query()->create(['learning_meeting_id' => $meeting->id, 'order' => 1, 'title' => 'Video Preview', 'type' => 'video', 'file_path' => 'learning-materials/video.mp4', 'is_published' => true]);

    $this->actingAs(makeUser(UserRole::SuperAdmin, 'pusat.preview@isoc.id'))
        ->get('/superadmin/preview-materi?materi=event-' . $event->id)
        ->assertOk()->assertSee('Modul Preview')->assertSee('Video Preview')->assertSee('learning-materials/video.mp4');

    $admin = makeUser(UserRole::Admin, 'daerah.preview@isoc.id');
    $event->update(['created_by' => $admin->id]);
    // Ganti user dalam satu test: reset sesi agar AuthenticateSession Filament tidak me-logout.
    $this->flushSession();
    $this->actingAs($admin)->get('/admin/preview-materi?materi=event-' . $event->id)->assertOk()->assertSee('Video Preview');
    $this->actingAs($admin)->get('/admin/preview-materi?materi=template-' . $template->id)->assertOk()->assertSee('Materi Preview');

    $tutorUser = makeUser(UserRole::Tutor, 'tutor.preview@isoc.id');
    $tutor = \App\Models\Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $event->school_id]);
    $event->tutors()->attach($tutor->id, ['status' => 'assigned']);
    $this->flushSession();
    $this->actingAs($tutorUser)->get('/tutor/preview-materi?materi=event-' . $event->id)->assertOk()->assertSee('Video Preview');

    expect(\App\Filament\Pages\MaterialPreview::canAccess())->toBeTrue();
    $this->actingAs(enrol($event, 'peserta.preview@isoc.id')->user);
    expect(\App\Filament\Pages\MaterialPreview::canAccess())->toBeFalse();
});

test('viewer materi menyiapkan pdf, video file, dan video youtube', function () {
    $pdf = new \App\Models\LearningMaterial(['type' => 'pdf', 'file_path' => 'learning-materials/slide.pdf']);
    $video = new \App\Models\LearningMaterial(['type' => 'video', 'file_path' => 'learning-materials/video.mp4']);
    $youtube = new \App\Models\LearningMaterial(['type' => 'video', 'external_url' => 'https://youtu.be/pLxS9dVhGGU']);

    expect(\App\Support\MaterialViewer::for($pdf)['embed_url'])->toEndWith('/storage/learning-materials/slide.pdf')
        ->and(\App\Support\MaterialViewer::for($video)['url'])->toEndWith('/storage/learning-materials/video.mp4')
        ->and(\App\Support\MaterialViewer::for($youtube)['embed_url'])->toBe('https://www.youtube.com/embed/pLxS9dVhGGU');
});

test('materi PPT dipreview dari versi PDF bila tersedia', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::disk('public')->put('learning-materials/modul-ajar/Modul A.pptx', 'pptx');
    $ppt = new \App\Models\LearningMaterial(['type' => 'ppt', 'file_path' => 'learning-materials/modul-ajar/Modul A.pptx']);

    expect(\App\Support\MaterialViewer::for($ppt)['embed_url'])->toContain('view.officeapps.live.com');

    \Illuminate\Support\Facades\Storage::disk('public')->put('learning-materials/modul-ajar/Modul A.pdf', 'pdf');

    expect(\App\Support\MaterialViewer::for($ppt)['embed_url'])->toEndWith('learning-materials/modul-ajar/Modul A.pdf')
        ->and(\App\Support\MaterialViewer::for($ppt)['url'])->toEndWith('Modul A.pptx');
});

test('sertifikat yang sudah terbit tetap bisa diunduh walau aturan eligibility berubah', function () {
    $event = makeEvent();
    $participant = enrol($event);
    $certificate = makeCertificate($event, $participant);
    // Simulasikan sertifikat lama yang diterbitkan sebelum aturan bukti dukung berlaku.
    \Illuminate\Support\Facades\DB::table('certificates')->where('id', $certificate->id)->update(['status' => 'issued', 'issued_at' => now(), 'eligibility_status' => 'eligible']);

    $this->actingAs($participant->user)->get(route('certificates.download-pdf', $certificate))->assertOk();

    \Illuminate\Support\Facades\DB::table('certificates')->where('id', $certificate->id)->update(['status' => 'revoked']);
    $this->get(route('certificates.download-pdf', $certificate))->assertForbidden();
});
