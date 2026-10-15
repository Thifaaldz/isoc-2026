<?php

use App\Filament\Resources\LearningEventResource;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\SiteSetting;
use App\Support\EvidenceTemplate;
use Illuminate\Support\Once;

function evidenceTemplateEvent(?array $checklist = null): LearningEvent
{
    $school = School::query()->create(['name' => 'SMA Bukti ' . uniqid()]);

    return LearningEvent::query()->create([
        'title' => 'Event Bukti', 'slug' => 'event-bukti-' . uniqid(), 'school_id' => $school->id,
        'status' => 'active', 'starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHours(3),
        'evidence_checklist' => $checklist,
    ]);
}

test('event lama tanpa template tetap memakai foto wajib a-g dan video slogan', function () {
    $event = evidenceTemplateEvent();

    expect(array_keys(Evidence::requiredPhotos($event->id)))->toBe(array_keys(Evidence::REQUIRED_PHOTOS))
        ->and(array_keys(Evidence::requiredFiles($event->id)))->toBe(['video_slogan'])
        ->and(LearningEventResource::missingFinalReportRequirements($event))->toContain('video slogan', 'foto a. Tutor bersama guru / manajemen sekolah (0/1)');
});

test('template bukti dukung baru tersimpan di site_settings dan disalin ke event', function () {
    $key = EvidenceTemplate::create([
        'name' => 'Bukti Webinar',
        'items' => [
            ['title' => 'Screenshot peserta zoom', 'photo' => true, 'min' => 2, 'instruction' => 'Galeri peserta.'],
            ['type' => 'video_slogan', 'title' => 'Video slogan', 'photo' => false, 'min' => 1],
            ['title' => 'Rekaman webinar', 'photo' => false, 'min' => 1],
        ],
    ]);

    expect($key)->toBe('bukti-webinar')
        ->and(SiteSetting::getValue(EvidenceTemplate::SETTING_KEY))->toHaveKey('bukti-webinar')
        ->and(EvidenceTemplate::options())->toHaveKeys([EvidenceTemplate::DEFAULT_KEY, 'bukti-webinar']);

    $event = evidenceTemplateEvent(EvidenceTemplate::snapshot($key));
    $photos = Evidence::requiredPhotos($event->id);

    expect(array_keys($photos))->toBe(['bd_screenshot_peserta_zoom'])
        ->and($photos['bd_screenshot_peserta_zoom']['step'])->toBe('a')
        ->and(array_keys(Evidence::requiredFiles($event->id)))->toBe(['video_slogan', 'bd_rekaman_webinar'])
        ->and(Evidence::typeLabel('bd_rekaman_webinar'))->toBe('Rekaman webinar')
        ->and(Evidence::uploadTypes($event->id))->toHaveKey('bd_rekaman_webinar');

    Evidence::query()->create(['learning_event_id' => $event->id, 'type' => 'bd_screenshot_peserta_zoom', 'link' => 'https://drive.google.com/x', 'status' => 'approved']);
    Evidence::query()->create(['learning_event_id' => $event->id, 'type' => 'video_slogan', 'link' => 'https://youtu.be/x', 'status' => 'approved']);
    Once::flush();

    expect(LearningEventResource::missingFinalReportRequirements($event))
        ->toContain('rekaman webinar')
        ->not->toContain('video slogan', 'foto a. Tutor bersama guru / manajemen sekolah (0/1)')
        ->and(collect(LearningEventResource::missingFinalReportRequirements($event))->filter(fn ($item) => str_starts_with($item, 'foto ')))->toBeEmpty();
});

test('mengubah template tidak mengubah salinan bukti dukung event yang sudah dibuat', function () {
    $key = EvidenceTemplate::create(['name' => 'Bukti Khusus', 'items' => [['title' => 'Foto spanduk', 'photo' => true, 'min' => 1]]]);
    $event = evidenceTemplateEvent(EvidenceTemplate::snapshot($key));

    SiteSetting::putValue(EvidenceTemplate::SETTING_KEY, [$key => ['name' => 'Bukti Khusus', 'items' => [['type' => 'bd_lain', 'title' => 'Lain', 'photo' => false, 'min' => 1]]]]);
    Once::flush();

    expect(array_keys(Evidence::requiredPhotos($event->id)))->toBe(['bd_foto_spanduk'])
        ->and(LearningEventResource::evidenceChecklistState($key, $event->fresh()))->toBe($event->fresh()->evidence_checklist);
});

test('wizard event menampilkan pilihan template bukti dukung dan bisa membuat template baru', function () {
    $superAdmin = \App\Models\User::query()->create(['name' => 'Pusat', 'email' => 'bukti.pusat@isoc.id', 'password' => 'password', 'role' => \App\Enums\UserRole::SuperAdmin, 'is_active' => true]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('superadmin'));
    $this->actingAs($superAdmin);

    \Livewire\Livewire::test(\App\Filament\Resources\LearningEventResource\Pages\CreateLearningEvent::class)
        ->assertFormFieldExists('evidence_template')
        ->assertFormSet(['evidence_template' => EvidenceTemplate::DEFAULT_KEY])
        ->assertSee('Foto g. Tutor bersama 10 peserta teraktif')
        ->callFormComponentAction('evidence_template', 'createOption', [
            'name' => 'Bukti Hybrid',
            'items' => [['title' => 'Foto peserta online', 'photo' => true, 'min' => 1, 'instruction' => '']],
        ])
        ->assertHasNoFormComponentActionErrors()
        ->assertFormSet(['evidence_template' => 'bukti-hybrid'])
        // Form template diisi awal dengan bukti dukung standar, item baru ditambahkan di akhir.
        ->assertSee('Foto h. Foto peserta online');

    expect(collect(SiteSetting::getValue(EvidenceTemplate::SETTING_KEY)['bukti-hybrid']['items'])->last()['type'])->toBe('bd_foto_peserta_online');
});
