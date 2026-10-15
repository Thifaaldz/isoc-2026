<?php

use App\Enums\UserRole;
use App\Filament\Pages\TutorApproval;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\Tutor;
use App\Models\TutorRegistration;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function tutorRegEvent(array $attributes = []): LearningEvent
{
    $school = School::query()->create(['name' => 'SMA Tutor ' . uniqid(), 'status' => 'active']);

    return LearningEvent::query()->create([
        'title' => 'Event Tutor ' . uniqid(), 'slug' => 'event-tutor-' . uniqid(), 'school_id' => $school->id,
        'status' => 'active', 'is_published' => true, 'registration_open' => true, 'target_tutors' => 3,
        'starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHours(3),
        ...$attributes,
    ]);
}

function tutorRegPayload(LearningEvent $event, string $email): array
{
    return ['learning_event_id' => $event->id, 'name' => 'Calon Tutor', 'email' => $email, 'phone' => '0812', 'institution' => 'RTIK Uji',
        'nik' => '1234567890123456', 'password' => 'password123', 'password_confirmation' => 'password123', 'consent' => '1'];
}

test('calon tutor memilih event, akunnya nonaktif dan belum dihitung sebagai tutor event sampai divalidasi', function () {
    $event = tutorRegEvent();

    $this->get(route('tutors.register', ['event' => $event->slug]))->assertOk()->assertSee($event->title);
    $this->post(route('tutors.register.store'), tutorRegPayload($event, 'calon.tutor@isoc.id'))
        ->assertRedirect(route('tutors.register', ['event' => $event->slug]))
        ->assertSessionHas('tutor_registered');

    $user = User::query()->where('email', 'calon.tutor@isoc.id')->firstOrFail();
    $registration = TutorRegistration::query()->firstOrFail();

    expect($user->role)->toBe(UserRole::Tutor)
        ->and($user->is_active)->toBeFalse()
        ->and($user->tutor->school_id)->toBe($event->school_id)
        ->and($registration->status)->toBe(TutorRegistration::PENDING)
        ->and($event->tutors()->count())->toBe(0)
        ->and($user->tutor->learningEvents()->count())->toBe(0);
});

test('admin RTIK pusat menyetujui dan menolak registrasi tutor dari halaman Approval Tutor', function () {
    $event = tutorRegEvent();
    $this->post(route('tutors.register.store'), tutorRegPayload($event, 'setuju@isoc.id'));
    $this->post(route('tutors.register.store'), tutorRegPayload($event, 'tolak@isoc.id'));
    [$approve, $reject] = TutorRegistration::query()->orderBy('id')->get()->all();

    $superAdmin = User::query()->create(['name' => 'Pusat', 'email' => 'pusat.tutor@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    Filament::setCurrentPanel(Filament::getPanel('superadmin'));
    $this->actingAs($superAdmin);

    Livewire::test(TutorApproval::class)
        ->assertCanSeeTableRecords([$approve, $reject])
        ->callTableAction('approve', $approve)
        ->callTableAction('reject', $reject);

    expect($approve->fresh()->status)->toBe(TutorRegistration::ASSIGNED)
        ->and(User::query()->where('email', 'setuju@isoc.id')->value('is_active'))->toBeTrue()
        ->and($reject->fresh()->status)->toBe(TutorRegistration::REJECTED)
        ->and(User::query()->where('email', 'tolak@isoc.id')->value('is_active'))->toBeFalse()
        ->and($event->tutors()->pluck('tutors.id')->all())->toBe([$approve->tutor_id]);
});

test('tutor yang sudah punya akun mendaftar ke event lain dan kuota penuh ditolak', function () {
    $event = tutorRegEvent();
    $full = tutorRegEvent(['target_tutors' => 1]);
    $user = User::query()->create(['name' => 'Tutor Lama', 'email' => 'tutor.lama@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true]);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $event->school_id]);
    $other = Tutor::query()->create(['user_id' => User::query()->create(['name' => 'Lain', 'email' => 'lain@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true])->id, 'school_id' => $full->school_id]);
    $full->tutors()->attach($other->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $this->actingAs($user)->post(route('tutors.register.join'), ['learning_event_id' => $event->id, 'consent' => '1'])->assertSessionHas('tutor_registered');
    $this->actingAs($user)->post(route('tutors.register.join'), ['learning_event_id' => $full->id, 'consent' => '1'])->assertSessionHasErrors('learning_event_id');

    expect(TutorRegistration::query()->where('tutor_id', $tutor->id)->pluck('status', 'learning_event_id')->all())->toBe([$event->id => TutorRegistration::PENDING])
        ->and($user->fresh()->is_active)->toBeTrue();
});

test('tutor pendaftar yang dipilih di wizard event ditugaskan tanpa error duplikat', function () {
    $event = tutorRegEvent();
    $this->post(route('tutors.register.store'), tutorRegPayload($event, 'wizard.tutor@isoc.id'));
    $tutorId = TutorRegistration::query()->value('tutor_id');

    $event->update(['selected_tutor_ids' => [$tutorId]]);
    app(\App\Services\LearningEventProvisioner::class)->assignSelectedTutors($event);

    expect($event->tutors()->pluck('tutors.id')->all())->toBe([$tutorId])
        ->and(TutorRegistration::query()->count())->toBe(1);
});

test('tutor mendaftar event dari panel lalu ToT memakai materi dan tes event yang dipilih', function () {
    $template = \App\Models\ModuleTemplate::query()->create(['name' => 'Materi Peserta', 'audience' => 'peserta', 'is_active' => true]);
    $mirror = \App\Models\ModuleTemplate::query()->create(['name' => 'Materi Peserta - ToT Tutor', 'audience' => 'tutor', 'source_template_id' => $template->id, 'is_active' => true]);
    $question = [['question' => '1+1?', 'options' => [['text' => '2', 'is_correct' => true], ['text' => '3', 'is_correct' => false]]]];
    $pre = \App\Models\Assessment::query()->create(['type' => 'pre', 'title' => 'Pre ToT', 'module_template_id' => $mirror->id, 'is_open' => true, 'questions' => $question]);
    $post = \App\Models\Assessment::query()->create(['type' => 'post', 'title' => 'Post ToT', 'module_template_id' => $mirror->id, 'is_open' => true, 'questions' => $question]);
    $event = tutorRegEvent(['module_template_id' => $template->id]);
    $other = tutorRegEvent();

    $user = User::query()->create(['name' => 'Tutor Panel', 'email' => 'tutor.panel@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true]);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $event->school_id]);
    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    $this->actingAs($user);

    $this->get('/tutor/daftar-event')->assertOk()->assertSee($event->title);
    Livewire::test(\App\Filament\Pages\TutorEventCatalog::class)
        ->callAction('join', ['consent' => true], ['event' => $event->id])
        ->assertRedirect(\App\Filament\Pages\TutorTraining::getUrl(['event' => $event->id]));

    expect(app(\App\Services\TutorRegistrationService::class)->registration($event, $tutor)->status)->toBe(TutorRegistration::PENDING);

    Livewire::withQueryParams(['event' => $event->id])->test(\App\Filament\Pages\TutorTraining::class)
        ->assertSet('eventId', $event->id)
        ->assertSee($mirror->name)
        ->call('startTest', $pre->id)->set('testAnswers', [0 => 0])->call('submitTest')
        ->call('startTest', $post->id)->set('testAnswers', [0 => 0])->call('submitTest');

    expect(\App\Models\TotAssessment::query()->where('tutor_id', $tutor->id)->pluck('score', 'learning_event_id')->all())->toEqual([$event->id => 100])
        ->and($tutor->fresh()->tot_completed)->toBeTrue()
        ->and($other->id)->not->toBe($event->id);
});
