<?php

use App\Enums\UserRole;
use App\Filament\Pages\RegionalAccounts;
use App\Filament\Resources\ParticipantResource\Pages\ManageParticipant;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\MicrositePractice;
use App\Models\Participant;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function rmUser(UserRole $role, string $email, ?int $schoolId = null): User
{
    return User::query()->create(['name' => strtok($email, '@'), 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true, 'school_id' => $schoolId]);
}

function rmEvent(School $school, ?User $creator = null): LearningEvent
{
    return LearningEvent::query()->create([
        'title' => 'Event ' . uniqid(), 'slug' => 'event-' . uniqid(), 'school_id' => $school->id, 'created_by' => $creator?->id,
        'status' => 'active', 'workflow_status' => 'verified_term_1', 'publish_approval_status' => 'published', 'is_published' => true, 'registration_open' => true,
        'event_type' => 'offline', 'starts_at' => now()->startOfHour(), 'ends_at' => now()->startOfHour()->addHours(3),
        'target_participants' => 100, 'target_tutors' => 1,
    ]);
}

function rmParticipant(School $school, LearningEvent $event, string $email): Participant
{
    $participant = Participant::query()->create(['user_id' => rmUser(UserRole::Peserta, $email, $school->id)->id, 'school_id' => $school->id]);
    $participant->learningEvents()->attach($event->id, ['status' => 'registered', 'admin_approval_status' => 'approved', 'tutor_approval_status' => 'approved']);

    return $participant;
}

function rmTutor(School $school, LearningEvent $event, string $email): Tutor
{
    $tutor = Tutor::query()->create(['user_id' => rmUser(UserRole::Tutor, $email, $school->id)->id, 'school_id' => $school->id, 'tot_completed' => true]);
    $tutor->learningEvents()->attach($event->id, ['status' => 'assigned']);

    return $tutor;
}

test('admin RTIK daerah reset semua password tutor & peserta di daerahnya saja', function () {
    $school = School::query()->create(['name' => 'S']);
    $admin = rmUser(UserRole::Admin, 'rm.admin@isoc.id', $school->id);
    $other = rmUser(UserRole::Admin, 'rm.lain@isoc.id', $school->id);
    $event = rmEvent($school, $admin);
    $otherEvent = rmEvent($school, $other);
    $peserta = rmParticipant($school, $event, 'rm.peserta@isoc.id')->user;
    $tutor = rmTutor($school, $event, 'rm.tutor@isoc.id')->user;
    $outside = rmParticipant($school, $otherEvent, 'rm.luar@isoc.id')->user;

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(RegionalAccounts::class)
        ->assertCanSeeTableRecords([$peserta, $tutor])
        ->assertCanNotSeeTableRecords([$outside, $admin])
        ->callTableAction('resetAll', data: ['role' => '', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
        ->assertHasNoTableActionErrors();

    expect(Hash::check('baru12345', $peserta->fresh()->password))->toBeTrue()
        ->and(Hash::check('baru12345', $tutor->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $outside->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $admin->fresh()->password))->toBeTrue();
});

test('admin RTIK daerah reset hanya peserta, dan ubah password satu peserta', function () {
    $school = School::query()->create(['name' => 'S']);
    $admin = rmUser(UserRole::Admin, 'rm2.admin@isoc.id', $school->id);
    $event = rmEvent($school, $admin);
    $a = rmParticipant($school, $event, 'rm2.a@isoc.id')->user;
    $b = rmParticipant($school, $event, 'rm2.b@isoc.id')->user;
    $tutor = rmTutor($school, $event, 'rm2.tutor@isoc.id')->user;

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(RegionalAccounts::class)
        ->callTableAction('resetAll', data: ['role' => 'peserta', 'password' => 'peserta123', 'password_confirmation' => 'peserta123'])
        ->callTableAction('changePassword', $a, data: ['password' => 'khusus123', 'password_confirmation' => 'khusus123'])
        ->callTableAction('changePassword', $b, data: ['password' => 'pendek', 'password_confirmation' => 'pendek'])
        ->assertHasTableActionErrors(['password']);

    expect(Hash::check('khusus123', $a->fresh()->password))->toBeTrue()
        ->and(Hash::check('peserta123', $b->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $tutor->fresh()->password))->toBeTrue();
});

test('halaman akun & password hanya untuk admin RTIK daerah', function () {
    $school = School::query()->create(['name' => 'S']);
    $event = rmEvent($school);
    $tutor = rmTutor($school, $event, 'rm3.tutor@isoc.id')->user;

    $this->actingAs($tutor);

    expect(RegionalAccounts::canAccess())->toBeFalse();
});

test('tutor mengubah nilai pre/post-test, link s.id, dan absensi peserta di manajemen peserta', function () {
    Http::fake(['s.id/*' => Http::response('<html>Microsite</html>', 200)]);

    $school = School::query()->create(['name' => 'S']);
    $event = rmEvent($school);
    $tutor = rmTutor($school, $event, 'rm4.tutor@isoc.id')->user;
    $participant = rmParticipant($school, $event, 'rm4.peserta@isoc.id');
    $pre = Assessment::query()->create(['type' => 'pre', 'title' => 'Pre', 'learning_event_id' => $event->id]);
    $post = Assessment::query()->create(['type' => 'post', 'title' => 'Post', 'learning_event_id' => $event->id]);
    AssessmentAttempt::query()->create(['assessment_id' => $post->id, 'participant_id' => $participant->id, 'score' => 90]);
    AssessmentAttempt::query()->create(['assessment_id' => $post->id, 'participant_id' => $participant->id, 'score' => 60]);

    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    $this->actingAs($tutor);

    Livewire::test(ManageParticipant::class, ['eventId' => $event->id])
        ->assertCanSeeTableRecords([$participant])
        ->callTableAction('editScores', $participant, data: ['pre_score' => 55, 'post_score' => 75])
        ->assertHasNoTableActionErrors()
        ->callTableAction('editMicrosite', $participant, data: ['sid_path' => 'https://s.id/Rina_Lestari'])
        ->assertHasNoTableActionErrors()
        ->callTableAction('editAttendance', $participant, data: ['status' => 'hadir', 'mode' => 'offline'])
        ->assertHasNoTableActionErrors();

    expect((float) AssessmentAttempt::query()->where('assessment_id', $pre->id)->max('score'))->toBe(55.0)
        ->and((float) AssessmentAttempt::query()->where('assessment_id', $post->id)->max('score'))->toBe(75.0)
        ->and(AssessmentAttempt::query()->where('assessment_id', $post->id)->count())->toBe(2)
        ->and(MicrositePractice::query()->where('participant_id', $participant->id)->value('sid_url'))->toBe('https://s.id/Rina_Lestari')
        ->and(Attendance::query()->where('learning_event_id', $event->id)->where('participant_id', $participant->id)->value('status'))->toBe('hadir');

    Livewire::test(ManageParticipant::class, ['eventId' => $event->id])
        ->callTableAction('editAttendance', $participant, data: ['status' => 'izin', 'mode' => 'offline']);

    expect(Attendance::query()->where('participant_id', $participant->id)->count())->toBe(1)
        ->and(Attendance::query()->where('participant_id', $participant->id)->value('status'))->toBe('izin');
});

test('link s.id yang tidak terdaftar ditolak dan tombol kelola butuh event terpilih', function () {
    Http::fake(['s.id/*' => Http::response('{"__type":"not_found"}', 404)]);

    $school = School::query()->create(['name' => 'S']);
    $event = rmEvent($school);
    $tutor = rmTutor($school, $event, 'rm5.tutor@isoc.id')->user;
    $participant = rmParticipant($school, $event, 'rm5.peserta@isoc.id');

    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    $this->actingAs($tutor);

    Livewire::test(ManageParticipant::class, ['eventId' => $event->id])
        ->callTableAction('editMicrosite', $participant, data: ['sid_path' => 'tidak-ada'])
        ->assertHasTableActionErrors(['sid_path']);

    expect(MicrositePractice::query()->count())->toBe(0);
});
