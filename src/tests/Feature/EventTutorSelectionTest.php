<?php

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use App\Services\LearningEventProvisioner;

test('tutor terdaftar yang dipilih ditugaskan saat event diproses tanpa membuat akun baru', function () {
    $school = School::query()->create(['name' => 'S']);
    $otherSchool = School::query()->create(['name' => 'Asal Tutor']);
    $user = User::query()->create(['name' => 'Tutor Lama', 'email' => 'tutor.lama@isoc.id', 'password' => 'password', 'role' => UserRole::Tutor, 'is_active' => true]);
    $tutor = Tutor::query()->create(['user_id' => $user->id, 'school_id' => $otherSchool->id, 'institution' => 'RTIK', 'tot_completed' => true]);

    expect(LearningEventResource::registeredTutorOptions())->toHaveKey($tutor->id);

    $event = LearningEvent::query()->create([
        'title' => 'E', 'slug' => 'e-' . uniqid(), 'school_id' => $school->id, 'status' => 'draft', 'workflow_status' => 'draft',
        'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHour(),
        'selected_tutor_ids' => [(string) $tutor->id],
        'tutor_rows' => [['name' => 'Tutor Baru', 'email' => 'tutor.baru@isoc.id']],
    ]);

    $usersBefore = User::query()->count();
    app(LearningEventProvisioner::class)->provisionAccounts($event);
    app(LearningEventProvisioner::class)->assignSelectedTutors($event);

    $assigned = $event->tutors()->with('user')->get();
    expect($assigned->pluck('user.email')->sort()->values()->all())->toBe(['tutor.baru@isoc.id', 'tutor.lama@isoc.id'])
        ->and(User::query()->count())->toBe($usersBefore + 1)
        ->and($tutor->refresh()->school_id)->toBe($otherSchool->id);
});
