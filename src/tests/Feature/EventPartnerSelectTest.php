<?php

use App\Enums\UserRole;
use App\Filament\Resources\LearningEventResource\Pages\CreateLearningEvent;
use App\Models\LearningEvent;
use App\Models\Partner;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Livewire\Livewire;

test('pilihan mitra di form event tidak duplikat walau mitra dipakai banyak event', function () {
    $school = School::query()->create(['name' => 'S']);
    $partners = collect(['APJII', 'ISOC', 'Komdigi'])->map(fn ($name) => Partner::query()->create(['name' => $name, 'status' => 'active']));

    foreach (range(1, 3) as $i) {
        LearningEvent::query()->create([
            'title' => "E{$i}", 'slug' => "e-{$i}", 'school_id' => $school->id, 'status' => 'draft', 'workflow_status' => 'draft',
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHour(),
        ])->partners()->sync($partners->pluck('id')->all());
    }

    $admin = User::query()->create(['name' => 'su', 'email' => 'su.partner@isoc.id', 'password' => 'password', 'role' => UserRole::SuperAdmin, 'is_active' => true]);
    config(['app.event_creation_enabled' => true]);
    Filament::setCurrentPanel(Filament::getPanel('superadmin'));
    $this->actingAs($admin);

    \DB::listen(fn ($q) => str_contains($q->sql, "partners") && fwrite(STDERR, $q->sql . PHP_EOL));
    $select = collect(Livewire::test(CreateLearningEvent::class)->instance()->form->getFlatComponents(withHidden: true))
        ->first(fn ($component) => $component instanceof Select && $component->getName() === 'partners');

    expect($select->getOptions())->toBe($partners->sortBy('name')->pluck('name', 'id')->all());
});
