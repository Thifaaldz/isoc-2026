<?php

use App\Enums\UserRole;
use App\Filament\Manajemen\Pages\StoryboardPage;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Attendance;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\School;
use App\Models\User;
use App\Support\ProgramMetrics;
use App\Support\ProgramStoryboard;
use Filament\Facades\Filament;
use Livewire\Livewire;

function sbUser(UserRole $role, string $email): User
{
    return User::query()->create(['name' => strtok($email, '@'), 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true, 'phone' => '0812']);
}

/** Bank 3 soal: 0 = phishing, 1 = hoaks, 2 = lainnya. Jawaban benar selalu opsi 0. */
function sbQuestions(): array
{
    $q = fn (string $text) => ['question' => $text, 'options' => [['text' => 'benar', 'is_correct' => true], ['text' => 'salah', 'is_correct' => false]]];

    return [$q('Ada yang meminta kode OTP via SMS. Apa yang harus dilakukan?'), $q('(Modul 3 – Hoax) Ciri berita palsu/hoaks adalah...'), $q('(Modul 3 – Perundungan Siber & Hoax) Ciri perundungan online adalah…')];
}

beforeEach(function () {
    ProgramMetrics::flush();
    ProgramStoryboard::flush();
});

test('tema soal dinilai dari isi soal, bukan judul modul', function () {
    expect(ProgramStoryboard::theme('Ada yang meminta kode OTP via SMS?'))->toBe(ProgramStoryboard::TEMA_PHISHING)
        ->and(ProgramStoryboard::theme('(Modul 3 – Mengenali Informasi Palsu) Ciri khas berita hoaks adalah…'))->toBe(ProgramStoryboard::TEMA_HOAKS)
        ->and(ProgramStoryboard::theme('(Modul 3 – Perundungan Siber & Hoax) Ciri perundungan online adalah…'))->toBe(ProgramStoryboard::TEMA_LAIN)
        ->and(ProgramStoryboard::shortName('Digital Safety Champions - banjarmasin'))->toBe('Banjarmasin');
});

test('storyboard menghitung kenaikan berpasangan, soal skenario, self-efficacy dan tindak lanjut', function () {
    $school = School::query()->create(['name' => 'SMK Uji', 'city' => 'Kota Uji']);
    $event = LearningEvent::query()->create([
        'title' => 'Digital Safety Champions - Kota Uji', 'slug' => 'kota-uji', 'school_id' => $school->id, 'status' => 'active',
        'workflow_status' => 'verified_term_2', 'is_published' => true, 'starts_at' => now()->subDays(3), 'ends_at' => now()->subDays(3)->addHours(3),
        'target_participants' => 4, 'target_tutors' => 1, 'final_report_status' => 'approved',
    ]);
    $pre = Assessment::query()->create(['type' => 'pre', 'title' => 'Pre', 'learning_event_id' => $event->id, 'questions' => sbQuestions()]);
    $post = Assessment::query()->create(['type' => 'post', 'title' => 'Post', 'learning_event_id' => $event->id, 'questions' => sbQuestions()]);

    // Peserta A: pre 40 (phishing salah) → post 100. Peserta B: pre 80 → post 60 (hoaks salah).
    $participants = collect(['a', 'b'])->map(function ($k) use ($school, $event) {
        $p = Participant::query()->create(['user_id' => sbUser(UserRole::Peserta, "sb.{$k}@isoc.id")->id, 'school_id' => $school->id, 'grade' => 'SMK 10']);
        $p->learningEvents()->attach($event->id, ['status' => 'registered']);

        return $p;
    });
    $attempt = fn ($a, $p, $score, array $answers) => AssessmentAttempt::query()->create(['assessment_id' => $a->id, 'participant_id' => $p->id, 'score' => $score, 'answers' => $answers, 'total_questions' => 3]);
    $attempt($pre, $participants[0], 40, ['1', '0', '0']);
    $attempt($post, $participants[0], 100, ['0', '0', '0']);
    $attempt($pre, $participants[1], 80, ['0', '0', '1']);
    $attempt($post, $participants[1], 60, ['0', '1', '0']);

    $s = ProgramStoryboard::get();
    $ind = $s->indicators();

    expect($ind['pasangan'])->toBe(2)
        ->and($ind['kenaikan'])->toBe(20.0)          // (+60 −20) / 2
        ->and([$ind['naik'], $ind['tetap'], $ind['turun']])->toBe([1, 0, 1])
        ->and($ind['skenario_pre'])->toBe(50.0)      // B benar semua soal skenario, A salah phishing
        ->and($ind['skenario_post'])->toBe(50.0)     // A benar semua, B salah hoaks
        ->and($ind['self_efficacy_dari_skor'])->toBeTrue();

    $phishing = collect($s->themes())->firstWhere('tema', ProgramStoryboard::TEMA_PHISHING);
    expect($phishing['pre'])->toBe(50.0)->and($phishing['post'])->toBe(100.0);

    $row = $s->locations()->firstWhere('lokasi', 'Kota Uji');
    expect($row['pct_target'])->toBe(50.0)->and($row['kenaikan'])->toBe(20.0);

    expect($s->completeness()['peserta_items'][2])->toBe(['Nomor HP', 100.0])
        ->and($s->attention()->pluck('isu')->all())->toContain('Kuota peserta', 'Kehadiran', 'E-sertifikat', 'Self-efficacy');

    // Daftar detail per lokus + filter status.
    Attendance::query()->create(['learning_event_id' => $event->id, 'participant_id' => $participants[0]->id, 'status' => 'hadir']);
    ProgramStoryboard::flush();
    $s = ProgramStoryboard::get();
    expect($s->participantsQuery($event->id)->count())->toBe(2)
        ->and($s->participantsQuery($event->id, null, 'hadir')->pluck('name')->all())->toBe(['sb.a'])
        ->and($s->participantsQuery($event->id, null, 'tidak-hadir')->pluck('name')->all())->toBe(['sb.b'])
        ->and($s->participantsQuery(null, 'sb.b@isoc')->count())->toBe(1)
        ->and($s->participantSummary($event->id)['hadir'])->toBe(1);
    $first = $s->participantsQuery($event->id, null, 'hadir')->first();
    expect((float) $first->nilai_pre)->toBe(40.0)->and((float) $first->nilai_post)->toBe(100.0);

    // Unduhan CSV hanya berisi baris sesuai filter aktif.
    $this->actingAs(sbUser(UserRole::Manajemen, 'mgmt.dl@isoc.id'));
    Filament::setCurrentPanel(Filament::getPanel('manajemen'));
    Livewire::test(StoryboardPage::class)
        ->set('pLokasi', (string) $event->id)
        ->set('pStatus', 'hadir')
        ->assertSee('Unduh CSV (1 baris)')
        ->call('downloadPeserta')
        ->assertFileDownloaded('peserta-dsc_kota-uji_hadir.csv');

    ob_start();
    $page = new StoryboardPage();
    $page->pLokasi = (string) $event->id;
    $page->pStatus = 'hadir';
    $page->downloadPeserta()->sendContent();
    $csv = ob_get_clean();
    expect($csv)->toContain('sb.a@isoc.id')->not->toContain('sb.b@isoc.id')->and(substr_count(trim($csv), "\n"))->toBe(1);
});

test('storyboard bisa dibuka manajemen dan admin pusat, tidak oleh admin daerah', function () {
    $this->actingAs(sbUser(UserRole::Manajemen, 'mgmt.sb@isoc.id'));
    Filament::setCurrentPanel(Filament::getPanel('manajemen'));

    $this->get('/manajemen/storyboard')->assertOk()->assertSee('Storyboard Program')->assertSee('Ringkasan eksekutif')
        ->assertSee('Capaian kriteria pelaporan')->assertSee('Kesimpulan dan tindak lanjut')->assertDontSee('@if');
    $this->get('/manajemen')->assertSee('Buka Storyboard');
    Livewire::test(StoryboardPage::class)->assertOk();

    $this->flushSession();
    $this->actingAs(sbUser(UserRole::SuperAdmin, 'pusat.sb@isoc.id'));
    $this->get('/manajemen/storyboard')->assertOk();

    $this->flushSession();
    $this->actingAs(sbUser(UserRole::Admin, 'admin.sb@isoc.id'));
    $this->get('/manajemen/storyboard')->assertForbidden();
});
