<?php

use App\Models\LearningMaterial;
use App\Models\LearningMeeting;
use App\Models\ModuleTemplate;
use App\Models\School;
use App\Models\SiteSetting;
use App\Support\EventOverview;
use App\Support\TermChecklistTemplate;
use App\Support\TorEventTemplate;
use App\Services\TutorMaterialMirror;

test('checklist termin standar TOR menjadi template awal dan event lama tetap memakainya', function () {
    expect(TermChecklistTemplate::options())->toHaveKey(TermChecklistTemplate::DEFAULT_KEY)
        ->and(TermChecklistTemplate::terms(null))->toBe(TorEventTemplate::TERM_CHECKLIST)
        ->and(TermChecklistTemplate::terms(TorEventTemplate::termChecklist(['banner'])))->toBe(TorEventTemplate::TERM_CHECKLIST)
        ->and(TermChecklistTemplate::templateKey(TorEventTemplate::termChecklist(['banner'])))->toBe(TermChecklistTemplate::DEFAULT_KEY);
});

test('template checklist termin baru tersimpan di site_settings dan tersalin ke budget_items event', function () {
    $key = TermChecklistTemplate::create([
        'name' => 'Termin Webinar',
        'term_1' => [['label' => 'Langganan Zoom'], ['key' => 'banner', 'label' => 'Banner virtual']],
        'term_2' => [['key' => 'daftar_hadir', 'label' => 'Daftar hadir peserta'], ['label' => 'Rekaman webinar']],
    ]);

    expect($key)->toBe('termin-webinar')
        ->and(SiteSetting::getValue(TermChecklistTemplate::SETTING_KEY))->toHaveKey('termin-webinar');

    $items = TorEventTemplate::termChecklist(['k_langganan_zoom'], TermChecklistTemplate::termsOf($key), $key);
    $school = School::query()->create(['name' => 'SMA Termin']);
    $event = \App\Models\LearningEvent::query()->create([
        'title' => 'Event Termin', 'slug' => 'event-termin', 'school_id' => $school->id, 'status' => 'active',
        'starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'budget_items' => $items,
    ]);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('superadmin'));

    expect(TermChecklistTemplate::templateKey($event->budget_items))->toBe('termin-webinar')
        ->and(TermChecklistTemplate::terms($event->budget_items)[1])->toBe(['k_langganan_zoom' => 'Langganan Zoom', 'banner' => 'Banner virtual'])
        ->and(TorEventTemplate::checkedKeys($event->budget_items, 1))->toBe(['k_langganan_zoom'])
        ->and(collect((new EventOverview($event))->termTwoChecklist())->pluck('key')->all())->toContain('daftar_hadir', 'k_rekaman_webinar', 'video_slogan');

    // Template diubah setelahnya: salinan event tidak ikut berubah.
    SiteSetting::putValue(TermChecklistTemplate::SETTING_KEY, ['termin-webinar' => ['name' => 'Termin Webinar', 'terms' => [1 => ['lain' => 'Lain'], 2 => []]]]);
    \Illuminate\Support\Once::flush();

    expect(TermChecklistTemplate::terms($event->fresh()->budget_items)[1])->toHaveKey('k_langganan_zoom');
});

test('materi ToT tutor disalin sekali dari materi peserta lalu bisa diedit tanpa tertimpa', function () {
    $source = ModuleTemplate::query()->create(['name' => 'Materi Peserta', 'audience' => 'peserta', 'meeting_count' => 1, 'is_active' => true]);
    // Di MariaDB kolom learning_event_id sudah nullable; samakan khusus di database SQLite memori test ini.
    \Illuminate\Support\Facades\Schema::table('learning_meetings', fn ($table) => $table->unsignedBigInteger('learning_event_id')->nullable()->change());
    $meetingId = \Illuminate\Support\Facades\DB::table('learning_meetings')->insertGetId(['module_template_id' => $source->id, 'learning_event_id' => null, 'order' => 1, 'title' => 'Modul 1', 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);
    LearningMaterial::query()->create(['learning_meeting_id' => $meetingId, 'title' => 'Slide Modul 1', 'type' => 'link', 'external_url' => 'https://example.test/ppt', 'order' => 1, 'is_published' => true]);

    $mirror = app(TutorMaterialMirror::class)->ensureFor($source);
    $tutorMeeting = $mirror->learningMeetings()->whereNull('learning_event_id')->first();

    expect($tutorMeeting->title)->toBe('Modul 1')
        ->and($tutorMeeting->materials()->value('title'))->toBe('Slide Modul 1');

    // Tutor material diedit, lalu materi peserta ikut diubah: materi tutor tidak tertimpa.
    $tutorMeeting->update(['title' => 'Modul 1 (versi tutor)']);
    $tutorMeeting->materials()->first()->update(['title' => 'PPT Fasilitator Modul 1']);
    LearningMeeting::query()->find($meetingId)->update(['title' => 'Modul 1 baru']);
    app(TutorMaterialMirror::class)->ensureFor($source->fresh());

    $tutorMeeting = $mirror->learningMeetings()->whereNull('learning_event_id')->first();
    expect($tutorMeeting->title)->toBe('Modul 1 (versi tutor)')
        ->and($tutorMeeting->materials()->value('title'))->toBe('PPT Fasilitator Modul 1');

    // Salin ulang manual mengambil isi terbaru materi peserta.
    app(TutorMaterialMirror::class)->resync($mirror->fresh());

    expect($mirror->learningMeetings()->whereNull('learning_event_id')->value('title'))->toBe('Modul 1 baru');
});

test('jumlah pertemuan materi tutor bisa ditambah dan dikurangi sendiri tanpa ikut materi peserta', function () {
    \Illuminate\Support\Facades\Schema::table('learning_meetings', fn ($table) => $table->unsignedBigInteger('learning_event_id')->nullable()->change());
    $source = ModuleTemplate::query()->create(['name' => 'Materi Peserta', 'audience' => 'peserta', 'meeting_count' => 3, 'is_active' => true]);
    $syncer = app(\App\Services\ModuleTemplateMeetingSyncer::class);
    $syncer->sync($source);
    $mirror = app(TutorMaterialMirror::class)->ensureFor($source);
    $titles = fn () => $mirror->learningMeetings()->whereNull('learning_event_id')->orderBy('order')->pluck('title')->all();

    expect($mirror->fresh()->meeting_count)->toBe(3);

    // Tambah lewat Jumlah Pertemuan: materi peserta tetap 3.
    $mirror->update(['meeting_count' => 5]);
    $syncer->sync($mirror->fresh());
    expect($titles())->toHaveCount(5)->and($source->learningMeetings()->count())->toBe(3);

    // Hapus satu pertemuan di tengah dari menu Pertemuan & Materi: jumlah ikut berkurang dan tidak dibuat ulang saat disimpan.
    $mirror->learningMeetings()->where('order', 2)->first()->delete();
    expect($mirror->fresh()->meeting_count)->toBe(4);
    $syncer->sync($mirror->fresh());
    expect($titles())->toBe(['Pertemuan 1', 'Pertemuan 3', 'Pertemuan 4', 'Pertemuan 5']);

    // Tambah pertemuan langsung: jumlah ikut bertambah dan tidak terhapus saat materi disimpan.
    LearningMeeting::query()->create(['module_template_id' => $mirror->id, 'learning_event_id' => null, 'order' => 6, 'title' => 'Praktik Fasilitator', 'is_published' => true]);
    $syncer->sync($mirror->fresh());
    expect($mirror->fresh()->meeting_count)->toBe(5)->and(last($titles()))->toBe('Praktik Fasilitator');

    // Kurangi lewat Jumlah Pertemuan: pertemuan terakhir yang dihapus.
    $mirror->update(['meeting_count' => 2]);
    $syncer->sync($mirror->fresh());
    expect($titles())->toBe(['Pertemuan 1', 'Pertemuan 3'])->and($mirror->fresh()->meeting_count)->toBe(2);
});

test('wizard event menawarkan template checklist termin dan bisa membuat template baru', function () {
    $superAdmin = \App\Models\User::query()->create(['name' => 'Pusat', 'email' => 'termin.pusat@isoc.id', 'password' => 'password', 'role' => \App\Enums\UserRole::SuperAdmin, 'is_active' => true]);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('superadmin'));
    $this->actingAs($superAdmin);

    \Livewire\Livewire::test(\App\Filament\Resources\LearningEventResource\Pages\CreateLearningEvent::class)
        ->assertFormSet(['term_template' => TermChecklistTemplate::DEFAULT_KEY])
        ->assertSee('Pengadaan 2 buah banner kegiatan')
        ->callFormComponentAction('term_template', 'createOption', [
            'name' => 'Termin Hybrid',
            'term_1' => [['label' => 'Sewa perangkat streaming']],
        ])
        ->assertHasNoFormComponentActionErrors()
        ->assertFormSet(['term_template' => 'termin-hybrid'])
        ->assertSee('Sewa perangkat streaming');
});
