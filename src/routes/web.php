<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\AttendanceTemplateController;
use App\Http\Controllers\CertificatePdfController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\ParticipantRegistrationController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'id'], true)) {
        session()->put('locale', $locale);
    }

    return back();
})->name('lang.switch');

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/programs', [PublicPageController::class, 'programs'])->name('programs');
Route::get('/events', [PublicPageController::class, 'events'])->name('events');
Route::get('/resources', fn () => view('pages.resources', ['settings' => []]))->name('resources');
Route::get('/our-partner', [PublicPageController::class, 'ourPartner'])->name('our-partner');

Route::get('/events/{event}/register', [ParticipantRegistrationController::class, 'showEventRegistration'])
    ->middleware('guest')
    ->name('event.register');
Route::post('/events/{event}/register', [ParticipantRegistrationController::class, 'store'])
    ->middleware('guest')
    ->name('event.register.store');

Route::redirect('/webinars', '/events');
Route::redirect('/student/login', '/peserta/login');
Route::redirect('/portal/login', '/peserta/login')->name('participant.login');
Route::redirect('/student/register', '/events/digital-safety-champions/register');

Route::get('/program', [ParticipantRegistrationController::class, 'showProgram'])
    ->name('program.show');

Route::get('/participants/register', [ParticipantRegistrationController::class, 'create'])
    ->middleware('guest')
    ->name('participants.register');

Route::post('/register-participant', [ParticipantRegistrationController::class, 'store'])
    ->middleware('guest')
    ->name('participants.register.store');

Route::get('/certificates/verify/{certificateNumber}', [CertificateVerificationController::class, 'show'])
    ->where('certificateNumber', '.*')
    ->name('certificates.verify');
Route::middleware('auth')->get('/certificates/{certificate}/preview-pdf', [CertificatePdfController::class, 'preview'])
    ->name('certificates.preview-pdf');
Route::middleware('auth')->get('/certificates/{certificate}/download-pdf', [CertificatePdfController::class, 'download'])
    ->name('certificates.download-pdf');
Route::get('/verify/{certificateNumber}', [CertificateVerificationController::class, 'show'])
    ->where('certificateNumber', '.*')
    ->name('certificate.verify');

// Pintasan: /dashboard mengarahkan pengguna ke panel sesuai rolenya.
Route::get('/dashboard', function () {
    $user = auth()->user();

    return $user ? redirect('/' . $user->role->panelId()) : redirect('/');
});

Route::get('/attendance-template/{session}', [AttendanceTemplateController::class, 'show'])
    ->middleware('auth')
    ->name('attendance.template');

Route::get('/templates/import/participants.xlsx', [ImportTemplateController::class, 'participants'])
    ->middleware('auth')
    ->name('import-templates.participants');

Route::get('/templates/import/tutors.xlsx', [ImportTemplateController::class, 'tutors'])
    ->middleware('auth')
    ->name('import-templates.tutors');
