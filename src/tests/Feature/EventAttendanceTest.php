<?php

use App\Enums\UserRole;
use App\Filament\Pages\ParticipantAttendance;
use App\Filament\Pages\EventAttendanceCode;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

function attUser(UserRole $role, string $email, ?int $schoolId = null): User
{
    return User::query()->create(['name' => strtok($email, '@'), 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true, 'school_id' => $schoolId]);
}

function attEvent(School $school, array $attributes = []): LearningEvent
{
    return LearningEvent::query()->create([
        'title' => 'Event ' . uniqid(), 'slug' => 'event-' . uniqid(), 'school_id' => $school->id,
        'status' => 'active', 'workflow_status' => 'verified_term_1', 'publish_approval_status' => 'published', 'is_published' => true, 'registration_open' => true,
        'event_type' => 'offline', 'starts_at' => now()->startOfHour(), 'ends_at' => now()->startOfHour()->addHours(3),
        'target_participants' => 100, 'target_tutors' => 1, 'attendance_code' => 'ABC234', ...$attributes,
    ]);
}

function attParticipant(School $school, LearningEvent $event, string $email): User
{
    $user = attUser(UserRole::Peserta, $email, $school->id);
    $participant = Participant::query()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $participant->learningEvents()->attach($event->id, [
        'status' => 'registered', 'registered_at' => now(),
        'admin_approval_status' => 'approved', 'tutor_approval_status' => 'approved',
    ]);

    return $user;
}

test('peserta absen dengan kode event yang benar dan tidak tercatat dua kali', function () {
    $school = School::query()->create(['name' => 'S']);
    $event = attEvent($school);
    $user = attParticipant($school, $event, 'att.peserta@isoc.id');

    Filament::setCurrentPanel(Filament::getPanel('peserta'));
    $this->actingAs($user);

    Livewire::test(ParticipantAttendance::class)
        ->assertSet('eventId', $event->id)
        ->assertSet('mode', 'offline')
        ->set('code', 'abc234')
        ->call('submit')
        ->assertHasNoErrors();

    Livewire::test(ParticipantAttendance::class)->set('code', 'ABC234')->call('submit')->assertHasNoErrors();

    $rows = Attendance::query()->where('learning_event_id', $event->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->mode)->toBe('offline')
        ->and($rows->first()->method)->toBe('kode')
        ->and($rows->first()->status)->toBe('hadir');
});

test('kode salah, event belum hari H, dan mode yang tidak sesuai tipe event ditolak', function () {
    $school = School::query()->create(['name' => 'S']);
    $today = attEvent($school);
    $future = attEvent($school, ['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(3)]);
    $user = attParticipant($school, $today, 'att.tolak@isoc.id');
    $user->participant->learningEvents()->attach($future->id, ['status' => 'registered', 'admin_approval_status' => 'approved', 'tutor_approval_status' => 'approved']);

    Filament::setCurrentPanel(Filament::getPanel('peserta'));
    $this->actingAs($user);

    Livewire::test(ParticipantAttendance::class)->set('eventId', $today->id)->set('code', 'SALAH1')->call('submit')->assertHasErrors(['code']);
    Livewire::test(ParticipantAttendance::class)->set('eventId', $today->id)->set('mode', 'online')->set('code', 'ABC234')->call('submit')->assertHasErrors(['mode']);
    Livewire::test(ParticipantAttendance::class)->set('eventId', $future->id)->set('code', 'ABC234')->call('submit')->assertHasErrors(['eventId']);

    expect(Attendance::query()->count())->toBe(0);
});

test('event hybrid bisa absen online', function () {
    $school = School::query()->create(['name' => 'S']);
    $event = attEvent($school, ['event_type' => 'hybrid']);
    $user = attParticipant($school, $event, 'att.hybrid@isoc.id');

    Filament::setCurrentPanel(Filament::getPanel('peserta'));
    $this->actingAs($user);

    Livewire::test(ParticipantAttendance::class)->set('mode', 'online')->set('code', 'ABC234')->call('submit')->assertHasNoErrors();

    expect(Attendance::query()->first()->mode)->toBe('online');
});

test('tutor hanya bisa generate kode untuk event yang ditugaskan', function () {
    $school = School::query()->create(['name' => 'S']);
    $assigned = attEvent($school, ['attendance_code' => null]);
    $other = attEvent($school, ['attendance_code' => 'LAMA22']);
    $user = attUser(UserRole::Tutor, 'att.tutor@isoc.id', $school->id);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $school->id, 'tot_completed' => true]);
    $tutor->learningEvents()->attach($assigned->id, ['status' => 'assigned', 'assigned_at' => now()]);

    Filament::setCurrentPanel(Filament::getPanel('tutor'));
    $this->actingAs($user);

    Livewire::test(EventAttendanceCode::class)
        ->call('generate', $assigned->id)
        ->call('generate', $other->id);

    expect($assigned->refresh()->attendance_code)->toMatch('/^[A-Z2-9]{6}$/')
        ->and($other->refresh()->attendance_code)->toBe('LAMA22');
});

test('fasilitator (admin RTIK daerah) hanya mengelola kode absensi event yang dibuatnya', function () {
    $school = School::query()->create(['name' => 'S']);
    $admin = attUser(UserRole::Admin, 'att.fasilitator@isoc.id', $school->id);
    $own = attEvent($school, ['attendance_code' => null, 'created_by' => $admin->id]);
    $other = attEvent($school, ['attendance_code' => 'LAMA22']);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(EventAttendanceCode::class)
        ->assertSee($own->title)
        ->assertDontSee($other->title)
        ->call('generate', $own->id)
        ->call('generate', $other->id);

    expect($own->refresh()->attendance_code)->toMatch('/^[A-Z2-9]{6}$/')
        ->and($other->refresh()->attendance_code)->toBe('LAMA22');
});

test('absensi basah dan rekap absensi online bisa dicetak oleh tutor event dan ditolak untuk peserta', function () {
    $school = School::query()->create(['name' => 'SMA Cetak']);
    $event = attEvent($school, ['event_type' => 'hybrid', 'title' => 'Event Cetak Absensi']);
    $nadia = attParticipant($school, $event, 'cetak.online@isoc.id');
    $budi = attParticipant($school, $event, 'cetak.offline@isoc.id');
    $belum = attParticipant($school, $event, 'cetak.belum@isoc.id');
    app(\App\Services\EventAttendanceService::class)->checkIn($nadia->participant, $event, 'ABC234', 'online');
    app(\App\Services\EventAttendanceService::class)->checkIn($budi->participant, $event, 'ABC234', 'offline');

    $tutorUser = attUser(UserRole::Tutor, 'cetak.tutor@isoc.id', $school->id);
    $tutor = Tutor::query()->create(['user_id' => $tutorUser->id, 'school_id' => $school->id, 'tot_completed' => true]);
    $tutor->learningEvents()->attach($event->id, ['status' => 'assigned', 'assigned_at' => now()]);

    $this->actingAs($tutorUser)->get(route('events.attendance.wet', $event))
        ->assertOk()->assertSee('Absensi Basah')->assertSee($nadia->name)->assertSee('Tanda Tangan');

    $this->actingAs($tutorUser)->get(route('events.attendance.digital', [$event, 'mode' => 'online']))
        ->assertOk()->assertSee($nadia->name)->assertDontSee($budi->name);

    $this->actingAs($tutorUser)->get(route('events.attendance.digital', $event))
        ->assertOk()->assertSee($budi->name)->assertSee('Peserta belum absen')->assertSee($belum->name);

    $this->actingAs($nadia)->get(route('events.attendance.digital', $event))->assertForbidden();
});
