<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\LearningEvent;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LearningEventProvisioner
{
    public function provision(LearningEvent $event): void
    {
        $event->refresh();

        $this->provisionAccounts($event);
        $this->provisionPaymentTerms($event);
    }

    public function provisionAccounts(LearningEvent $event): void
    {
        $event->refresh();

        $this->provisionParticipants($event);
        $this->provisionTutors($event);
    }

    public function provisionPaymentTerms(LearningEvent $event): void
    {
        $event->refresh();

        $this->ensurePaymentTerms($event);
    }

    private function provisionParticipants(LearningEvent $event): void
    {
        foreach ($event->participant_rows ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $user = $this->userFor($name, $row['email'] ?? null, UserRole::Peserta, $event->school_id, $row['phone'] ?? null);
            $isGeneral = ($event->audience_type ?? 'school') === 'general';
            // Peserta umum tidak punya NISN; baris lama yang menaruh NIK di kolom nis tetap terbaca sebagai NIK.
            $nis = $isGeneral ? null : ($row['nis'] ?? $row['nisn'] ?? null);
            $nik = $row['nik'] ?? ($isGeneral ? ($row['nis'] ?? null) : null);

            $participant = Participant::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $event->school_id,
                    'participant_category' => ($event->audience_type ?? 'school') === 'general' ? 'umum' : 'pelajar',
                    'nis' => $nis,
                    'nik' => $nik,
                    'grade' => $row['grade'] ?? null,
                    'organization' => $row['organization'] ?? null,
                    'position' => $row['position'] ?? null,
                    'gender' => $row['gender'] ?? null,
                    'birth_date' => $row['birth_date'] ?? null,
                    'consent_at' => now(),
                ],
            );

            $participant->update([
                'school_id' => $event->school_id,
                'participant_category' => $participant->participant_category ?: (($event->audience_type ?? 'school') === 'general' ? 'umum' : 'pelajar'),
                'nis' => $nis ?? $participant->nis,
                'nik' => $nik ?? $participant->nik,
                'grade' => $row['grade'] ?? $participant->grade,
                'organization' => $row['organization'] ?? $participant->organization,
                'position' => $row['position'] ?? $participant->position,
                'gender' => $row['gender'] ?? $participant->gender,
                'birth_date' => $row['birth_date'] ?? $participant->birth_date,
            ]);

            $approvalStatus = $event->audience_type === 'general' ? 'pending' : 'approved';

            $event->participants()->syncWithoutDetaching([
                $participant->id => [
                    'status' => 'registered',
                    'registered_at' => now(),
                    'admin_approval_status' => $approvalStatus,
                    'tutor_approval_status' => $approvalStatus,
                ],
            ]);
        }
    }

    /** Tugaskan tutor terdaftar yang dipilih di wizard; tutor yang sudah ditugaskan tidak diubah. */
    public function assignSelectedTutors(LearningEvent $event): void
    {
        $assignedIds = $event->tutors()->pluck('tutors.id')->all();

        $newIds = Tutor::query()
            ->whereKey(array_filter((array) ($event->selected_tutor_ids ?? [])))
            ->whereKeyNot($assignedIds)
            ->pluck('id');

        foreach ($newIds as $tutorId) {
            $event->tutors()->attach($tutorId, ['status' => 'assigned', 'assigned_at' => now()]);
        }
    }

    private function provisionTutors(LearningEvent $event): void
    {
        $this->assignSelectedTutors($event);

        foreach ($event->tutor_rows ?? [] as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '' || ! $event->school_id) {
                continue;
            }

            $user = $this->userFor($name, $row['email'] ?? null, UserRole::Tutor, $event->school_id, $row['phone'] ?? null);

            $tutor = Tutor::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $event->school_id,
                    'institution' => $row['institution'] ?? null,
                    'nik' => $row['nik'] ?? null,
                    'npwp' => $row['npwp'] ?? null,
                    'bank_name' => $row['bank_name'] ?? null,
                    'bank_account_number' => $row['bank_account_number'] ?? null,
                    'tot_completed' => false,
                ],
            );

            $tutor->update([
                'school_id' => $event->school_id,
                'institution' => $row['institution'] ?? $tutor->institution,
                'nik' => $row['nik'] ?? $tutor->nik,
                'npwp' => $row['npwp'] ?? $tutor->npwp,
                'bank_name' => $row['bank_name'] ?? $tutor->bank_name,
                'bank_account_number' => $row['bank_account_number'] ?? $tutor->bank_account_number,
            ]);

            $event->tutors()->syncWithoutDetaching([
                $tutor->id => ['status' => 'assigned', 'assigned_at' => now()],
            ]);
        }
    }

    private function ensurePaymentTerms(LearningEvent $event): void
    {
        if (! $event->school_id) {
            return;
        }

        $items = collect($event->budget_items ?? []);
        $totalBudget = $items->sum(fn (array $item) => (float) ($item['amount'] ?? 0));
        // Item RAB TOR punya termin masing-masing; RAB lama tanpa termin dibagi dua rata.
        $hasTerms = $items->contains(fn (array $item) => filled($item['term'] ?? null));
        $termAmountFor = fn (int $term) => $hasTerms
            ? round($items->where('term', $term)->sum(fn (array $item) => (float) ($item['amount'] ?? 0)), 2)
            : ($totalBudget > 0 ? round($totalBudget / 2, 2) : 0);

        Payment::query()
            ->where('learning_event_id', $event->id)
            ->where('term', '>', 2)
            ->delete();

        foreach ([1, 2] as $term) {
            $payment = Payment::query()->firstOrNew(
                ['learning_event_id' => $event->id, 'term' => $term],
            );

            $payment->school_id = $event->school_id;
            $payment->amount = $termAmountFor($term);
            $payment->status = $payment->status ?: 'pending';
            $payment->checklist = $payment->checklist ?: $this->defaultChecklist($term);
            $payment->save();
        }
    }

    private function userFor(string $name, ?string $email, UserRole $role, ?int $schoolId, ?string $phone): User
    {
        if (blank($email)) {
            $existing = User::query()
                ->where('name', $name)
                ->where('role', $role->value)
                ->where('school_id', $schoolId)
                ->first();

            if ($existing) {
                $existing->update([
                    'phone' => $phone ?: $existing->phone,
                    'is_active' => true,
                ]);

                return $existing;
            }
        }

        $email = filled($email) ? strtolower(trim($email)) : $this->uniqueEmailFor($name);

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'role' => $role,
                'phone' => $phone,
                'school_id' => $schoolId,
                'is_active' => true,
            ],
        );

        $user->update([
            'name' => $user->name ?: $name,
            'phone' => $phone ?: $user->phone,
            'school_id' => $user->school_id ?: $schoolId,
            'is_active' => true,
        ]);

        return $user;
    }

    private function uniqueEmailFor(string $name): string
    {
        $base = Str::slug($name, '.');
        $base = $base !== '' ? $base : 'user';
        $email = "{$base}@isoc.id";
        $counter = 2;

        while (User::query()->where('email', $email)->exists()) {
            $email = "{$base}{$counter}@isoc.id";
            $counter++;
        }

        return $email;
    }

    private function defaultChecklist(int $term): array
    {
        return match ($term) {
            1 => Arr::map([
                'Data sekolah lengkap',
                'Data peserta valid untuk dibuat akun',
                'Data tutor/fasilitator valid untuk dibuat akun',
                'Jadwal pelatihan lengkap',
                'Checklist Termin-1 lengkap: banner, snack, dana kebersihan sekolah',
                'Akun peserta dan tutor aktif setelah approval Termin-1',
                'Berita acara persiapan',
            ], fn (string $label) => ['label' => $label, 'done' => false]),
            2 => Arr::map([
                'Dokumentasi acara lengkap',
                'Hasil pre-test dan post-test',
                'Daftar hadir registrasi / absensi',
                'Video slogan ISOC',
                'Bukti praktik microsite s.id',
                'Checklist Termin-2 lengkap: foto a-g, daftar hadir, nilai pre/post-test, microsite & ranking peserta, administrasi RTIK Pusat',
                'Laporan kegiatan per lokasi disubmit',
                'Laporan kegiatan disetujui Admin RTIK Pusat',
            ], fn (string $label) => ['label' => $label, 'done' => false]),
            default => [],
        };
    }
}
