<?php

use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\AttendanceTemplateController;
use App\Http\Controllers\CertificatePdfController;
use App\Http\Controllers\CertificateTemplatePreviewController;
use App\Http\Controllers\EventActivityReportController;
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
Route::redirect('/login', '/auth/login');
Route::redirect('/student/login', '/login');
Route::redirect('/portal/login', '/login')->name('participant.login');
Route::redirect('/superadmin/login', '/login');
Route::redirect('/admin/login', '/login');
Route::redirect('/tutor/login', '/login');
Route::redirect('/peserta/login', '/login');
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
Route::middleware('auth')->get('/certificate-templates/{template}/preview-pdf', [CertificateTemplatePreviewController::class, 'show'])
    ->name('certificate-templates.preview-pdf');
Route::middleware('auth')->get('/reports/events/{event}/activity-report/preview', [EventActivityReportController::class, 'preview'])
    ->name('reports.events.activity.preview');
Route::middleware('auth')->get('/reports/events/{event}/activity-report/download', [EventActivityReportController::class, 'download'])
    ->name('reports.events.activity.download');
Route::get('/verify/{certificateNumber}', [CertificateVerificationController::class, 'show'])
    ->where('certificateNumber', '.*')
    ->name('certificate.verify');

// Pintasan: /dashboard mengarahkan pengguna ke panel sesuai rolenya.
Route::get('/dashboard', function () {
    $user = auth()->user();

    return $user ? redirect('/' . $user->role->panelId()) : redirect('/auth/login');
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

Route::get('/templates/import/rab.xlsx', [ImportTemplateController::class, 'rab'])
    ->middleware('auth')
    ->name('import-templates.rab');
