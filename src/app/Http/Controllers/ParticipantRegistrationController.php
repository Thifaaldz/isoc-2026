<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Evidence;
use App\Models\LearningEvent;
use App\Models\Module;
use App\Models\Participant;
use App\Models\School;
use App\Models\User;
use App\Models\WagGroup;
use App\Support\PublicEvent;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ParticipantRegistrationController
{
    private const GRADE_OPTIONS = [
        'SD 1' => 'SD Kelas 1',
        'SD 2' => 'SD Kelas 2',
        'SD 3' => 'SD Kelas 3',
        'SD 4' => 'SD Kelas 4',
        'SD 5' => 'SD Kelas 5',
        'SD 6' => 'SD Kelas 6',
        'SMP 7' => 'SMP Kelas 7',
        'SMP 8' => 'SMP Kelas 8',
        'SMP 9' => 'SMP Kelas 9',
        'SMA 10' => 'SMA/SMK Kelas 10',
        'SMA 11' => 'SMA/SMK Kelas 11',
        'SMA 12' => 'SMA/SMK Kelas 12',
    ];

    public function create(): View
    {
        return $this->showEventRegistration();
    }

    public function showEventRegistration(?string $event = null): View
    {
        $learningEvent = LearningEvent::query()
            ->withCount('participants')
            ->with('school.wagGroups')
            ->where('is_published', true)
            ->where('status', 'active')
            ->when($event, fn ($query) => $query->where('slug', $event))
            ->orderBy('starts_at')
            ->first();

        $registrationEvent = $learningEvent
            ? $this->publicEventFromModel($learningEvent)
            : new PublicEvent(
                slug: 'digital-safety-champions',
                title: 'Digital Safety Champions',
                date: Carbon::create(2026, 10, 24, 9, 0),
                time_info: '09.00 - 12.00 WIB',
                location: 'Online dan sekolah mitra',
                location_type: 'hybrid',
                category: 'Literasi Digital',
                description: 'Program pendaftaran peserta ISOC untuk literasi keamanan digital, pembelajaran modul, asesmen, dan e-sertifikat.',
                registration_open: true,
                max_participants: 500,
                confirmed_count: Participant::query()->count(),
                capacity_info: Participant::query()->count() . '/500 peserta'
            );

        $wagGroup = $learningEvent?->school?->wagGroups
            ?->filter(fn (WagGroup $group): bool => $group->status === 'active' && filled($group->invite_link))
            ->sortByDesc('updated_at')
            ->first()
            ?: WagGroup::query()
                ->where('status', 'active')
                ->whereNotNull('invite_link')
                ->latest()
                ->first();

        return view('pages.event-register', [
            'event' => $registrationEvent,
            'wagGroup' => $wagGroup,
            'registrationCount' => Participant::query()->count(),
            'gradeOptions' => self::GRADE_OPTIONS,
            'schools' => School::query()
                ->where('status', 'active')
                ->orderBy('city')
                ->orderBy('name')
                ->get(),
            'modules' => Module::query()
                ->where('is_published', true)
                ->orderBy('number')
                ->get(),
            'sessions' => collect(),
            'settings' => [
                'footer_description' => 'Mendukung pengembangan internet yang berkelanjutan, inklusif, aman, dan mudah diakses.',
            ],
        ]);
    }

    public function showProgram(): View
    {
        return view('program.show', [
            'schools' => School::query()
                ->where('status', 'active')
                ->orderBy('training_date')
                ->orderBy('name')
                ->get(),
            'modules' => Module::query()
                ->where('is_published', true)
                ->orderBy('number')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $eventSlug = $request->route('event') ?: 'digital-safety-champions';
        $learningEvent = Schema::hasTable('learning_events')
            ? LearningEvent::query()
                ->where('slug', $eventSlug)
                ->where('is_published', true)
                ->where('status', 'active')
                ->first()
            : null;
        $participantCategory = $request->input('participant_category', (($learningEvent?->audience_type ?? 'school') === 'general' ? 'umum' : 'pelajar'));
        $isStudent = $participantCategory === 'pelajar';
        $identityLabel = $this->identityLabel($participantCategory);
        $identityRules = match ($participantCategory) {
            'pelajar' => ['required', 'digits:10'],
            'umum', 'karyawan' => ['required', 'digits:16'],
            'mahasiswa' => ['required', 'string', 'min:3', 'max:50'],
            default => ['required', 'string', 'max:50'],
        };
        $programLocationId = $learningEvent?->school_id
            ?: $request->input('school_id')
            ?: School::query()->where('status', 'active')->orderBy('id')->value('id');
        $identityUniqueRule = Rule::unique('participants', 'nis')
            ->where(fn ($query) => $query
                ->where('school_id', $programLocationId)
                ->where('participant_category', $participantCategory));

        $validated = $request->validate([
            'participant_category' => ['required', Rule::in(['pelajar', 'mahasiswa', 'umum', 'karyawan'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')->where('status', 'active')],
            'nis' => [
                ...$identityRules,
                $identityUniqueRule,
            ],
            'grade' => [$isStudent ? 'required' : 'nullable', Rule::in(array_keys(self::GRADE_OPTIONS))],
            'organization' => [$isStudent ? 'required' : 'nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'instagram_evidence' => ['required', 'image'],
            'joined_wag' => ['accepted'],
            'consent' => ['accepted'],
        ], [], [
            'nis' => $identityLabel,
            'participant_category' => 'kategori peserta',
        ]);

        $user = DB::transaction(function () use ($request, $validated, $learningEvent, $programLocationId): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Peserta,
                'phone' => $validated['phone'],
                'school_id' => $programLocationId,
                'is_active' => true,
            ]);

            $participant = Participant::query()->create([
                'user_id' => $user->id,
                'school_id' => $programLocationId,
                'participant_category' => $validated['participant_category'],
                'nis' => $validated['nis'] ?? null,
                'grade' => $validated['grade'] ?? null,
                'organization' => $validated['organization'] ?? null,
                'position' => $validated['position'] ?? null,
                'gender' => $validated['gender'],
                'birth_date' => $validated['birth_date'] ?? null,
                'consent_at' => now(),
                'followed_instagram' => true,
                'joined_wag' => true,
                'registered_ecert' => false,
            ]);

            Evidence::query()->create([
                'learning_event_id' => $learningEvent?->id,
                'school_id' => $programLocationId,
                'type' => 'follow_ig',
                'file_path' => $request->file('instagram_evidence')->store('evidences', 'public'),
                'status' => 'approved',
                'uploaded_by' => $user->id,
                'verified_at' => now(),
                'review_notes' => 'Auto-approved dari registrasi: bukti follow IG dan checklist WAG lengkap.',
            ]);

            if ($learningEvent) {
                $participant->learningEvents()->syncWithoutDetaching([
                    $learningEvent->id => [
                        'status' => 'registered',
                        'registered_at' => now(),
                        'admin_approval_status' => 'approved',
                        'admin_approved_at' => now(),
                        'tutor_approval_status' => 'approved',
                        'tutor_approved_at' => now(),
                        'approval_notes' => 'Auto-approved: bukti follow IG dan checklist join WAG sudah lengkap.',
                    ],
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/peserta');
    }

    private function publicEventFromModel(LearningEvent $event): PublicEvent
    {
        $location = match ($event->event_type) {
            'webinar' => 'Webinar / Zoom',
            'hybrid' => ($event->school?->name ? $event->school->name . ' + Zoom' : 'Hybrid'),
            default => $event->school?->name,
        };

        return new PublicEvent(
            slug: $event->slug,
            title: $event->title,
            date: $event->starts_at,
            time_info: $event->starts_at && $event->ends_at
                ? $event->starts_at->format('H.i') . ' - ' . $event->ends_at->format('H.i') . ' WIB'
                : null,
            location: $location,
            location_type: $event->event_type,
            category: 'Literasi Digital',
            description: $event->description,
            registration_open: (bool) $event->registration_open,
            max_participants: $event->target_participants,
            confirmed_count: (int) ($event->participants_count ?? 0),
            capacity_info: ($event->participants_count ?? 0) . '/' . ($event->target_participants ?: 0) . ' peserta',
            audience_type: $event->audience_type ?? 'school',
        );
    }

    private function identityLabel(string $category): string
    {
        return match ($category) {
            'pelajar' => 'NISN',
            'mahasiswa' => 'NIM',
            'karyawan', 'umum' => 'NIK',
            default => 'Nomor Identitas',
        };
    }
}
