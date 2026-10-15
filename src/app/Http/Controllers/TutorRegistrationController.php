<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\School;
use App\Models\Tutor;
use App\Models\User;
use App\Services\TutorRegistrationService;
use App\Support\IdentityNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Registrasi tutor: pilih event, isi data, lalu menunggu validasi Admin RTIK Pusat. */
class TutorRegistrationController
{
    public function __construct(private readonly TutorRegistrationService $registrations)
    {
    }

    public function create(Request $request): View
    {
        $events = $this->registrations->availableEvents();
        $user = $request->user();

        return view('pages.tutor-register', [
            'events' => $events,
            'selectedEventId' => (int) old('learning_event_id', $events->firstWhere('slug', $request->query('event'))?->id ?? $events->firstWhere('id', (int) $request->query('event'))?->id),
            'viewerMode' => match (true) {
                ! $user => 'guest',
                TutorRegistrationService::isTutor($user) => 'tutor',
                default => 'other',
            },
            'closedReasons' => $events->mapWithKeys(fn (LearningEvent $event) => [$event->id => $this->registrations->closedReason($event, $user?->tutor)]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $event = $this->selectedEvent($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'institution' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'regex:' . IdentityNumber::NIK_REGEX],
            'npwp' => ['nullable', 'string', 'max:30'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'consent' => ['accepted'],
        ], [
            'nik.regex' => IdentityNumber::NIK_MESSAGE,
            'email.unique' => 'Email ini sudah terdaftar. Jika sudah punya akun tutor, login terlebih dahulu lalu daftar ke event dari halaman ini.',
        ], [
            'institution' => 'lembaga / instansi',
            'consent' => 'persetujuan',
        ]);

        $schoolId = $event->school_id ?: School::query()->where('status', 'active')->orderBy('id')->value('id');

        DB::transaction(function () use ($validated, $event, $schoolId): void {
            // Akun nonaktif sampai pendaftaran divalidasi Admin RTIK Pusat.
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Tutor,
                'phone' => $validated['phone'],
                'school_id' => $schoolId,
                'is_active' => false,
            ]);

            $tutor = Tutor::query()->create([
                'user_id' => $user->id,
                'school_id' => $schoolId,
                'institution' => $validated['institution'],
                'nik' => $validated['nik'] ?? null,
                'npwp' => $validated['npwp'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'tot_completed' => false,
            ]);

            $this->registrations->apply($tutor, $event);
        });

        return redirect()->route('tutors.register', ['event' => $event->slug])
            ->with('tutor_registered', 'Pendaftaran tutor untuk "' . $event->title . '" terkirim. Akun kamu aktif setelah divalidasi Admin RTIK Pusat; setelah itu login dengan email dan password yang baru dibuat.');
    }

    /** Tutor yang sudah punya akun mendaftar ke event lain. */
    public function join(Request $request): RedirectResponse
    {
        $tutor = $request->user()?->tutor;

        abort_unless(TutorRegistrationService::isTutor($request->user()), 403, 'Hanya akun tutor yang dapat mendaftar sebagai tutor event.');

        $event = $this->selectedEvent($request, $tutor);
        $request->validate(['consent' => ['accepted']], [], ['consent' => 'persetujuan']);

        $this->registrations->apply($tutor, $event);

        return redirect()->route('tutors.register', ['event' => $event->slug])
            ->with('tutor_registered', 'Pendaftaran tutor untuk "' . $event->title . '" terkirim dan menunggu validasi Admin RTIK Pusat.');
    }

    private function selectedEvent(Request $request, ?Tutor $tutor = null): LearningEvent
    {
        $request->validate(['learning_event_id' => ['required', 'integer']], [], ['learning_event_id' => 'event']);

        $event = $this->registrations->availableEvents()->firstWhere('id', (int) $request->input('learning_event_id'));

        if (! $event) {
            throw \Illuminate\Validation\ValidationException::withMessages(['learning_event_id' => 'Event yang dipilih tidak tersedia untuk pendaftaran tutor.']);
        }

        if ($reason = $this->registrations->closedReason($event, $tutor)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['learning_event_id' => $reason]);
        }

        return $event;
    }
}
