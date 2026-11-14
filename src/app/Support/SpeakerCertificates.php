<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sertifikat narasumber yang ditambahkan manual oleh Admin RTIK Pusat untuk tutor / fasilitator.
 * Disimpan sebagai JSON di site_settings (tanpa tabel baru). Entri untuk user / event yang sudah
 * dihapus otomatis diabaikan karena selalu dicocokkan ke data yang masih ada.
 *
 * Setiap entri: {id, learning_event_id, user_id, role, created_by, created_at}.
 * Narasumber yang belum punya akun di sistem disimpan dengan user_id null serta name, institution,
 * dan seq (nomor urut untuk nomor sertifikat).
 */
class SpeakerCertificates
{
    public const KEY = 'speaker_certificates';

    public const ROLE_TUTOR = 'tutor';

    public const ROLE_FACILITATOR = 'facilitator';

    /** @return Collection<int, array> */
    public static function all(): Collection
    {
        return collect(SiteSetting::getValue(self::KEY, []))->values();
    }

    /** Entri untuk narasumber yang belum terdaftar sebagai akun di sistem. */
    public static function isExternal(array $item): bool
    {
        return empty($item['user_id']) && filled($item['name'] ?? null);
    }

    /** @return Collection<int, array> sertifikat manual untuk akun terdaftar. */
    public static function where(string $role, ?int $eventId = null, ?int $userId = null): Collection
    {
        return static::all()
            ->reject(fn (array $item) => static::isExternal($item))
            ->where('role', $role)
            ->when($eventId !== null, fn (Collection $items) => $items->where('learning_event_id', $eventId))
            ->when($userId !== null, fn (Collection $items) => $items->where('user_id', $userId))
            ->values();
    }

    /** @return array<int, int> */
    public static function userIds(string $role, int $eventId): array
    {
        return static::where($role, eventId: $eventId)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return array<int, int> */
    public static function eventIds(string $role, int $userId): array
    {
        return static::where($role, userId: $userId)->pluck('learning_event_id')->map(fn ($id) => (int) $id)->all();
    }

    /** Menambah entri; false bila user sudah punya sertifikat tambahan di event ini. */
    public static function add(int $eventId, int $userId, string $role): bool
    {
        return DB::transaction(function () use ($eventId, $userId, $role): bool {
            $items = static::lockedItems();

            if ($items->contains(fn (array $item) => $item['role'] === $role && (int) $item['learning_event_id'] === $eventId && (int) $item['user_id'] === $userId)) {
                return false;
            }

            SiteSetting::putValue(self::KEY, $items->push([
                'id' => (string) Str::uuid(),
                'learning_event_id' => $eventId,
                'user_id' => $userId,
                'role' => $role,
                'created_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
            ])->values()->all());

            return true;
        });
    }

    /** @return Collection<int, array> narasumber belum terdaftar di event, urut nama. */
    public static function external(string $role, int $eventId): Collection
    {
        return static::all()
            ->filter(fn (array $item) => static::isExternal($item) && $item['role'] === $role && (int) $item['learning_event_id'] === $eventId)
            ->sortBy(fn (array $item) => mb_strtolower($item['name']))
            ->values();
    }

    public static function find(string $id): ?array
    {
        return static::all()->first(fn (array $item) => ($item['id'] ?? null) === $id);
    }

    /** Menambah narasumber yang belum terdaftar; false bila nama yang sama sudah ada di event ini. */
    public static function addExternal(int $eventId, string $role, string $name, ?string $institution): bool
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        $institution = filled($institution) ? trim($institution) : null;

        return DB::transaction(function () use ($eventId, $role, $name, $institution): bool {
            $items = static::lockedItems();

            if ($items->contains(fn (array $item) => static::isExternal($item) && $item['role'] === $role
                && (int) $item['learning_event_id'] === $eventId && mb_strtolower($item['name']) === mb_strtolower($name))) {
                return false;
            }

            SiteSetting::putValue(self::KEY, $items->push([
                'id' => (string) Str::uuid(),
                'learning_event_id' => $eventId,
                'user_id' => null,
                'name' => $name,
                'institution' => $institution,
                'role' => $role,
                // Nomor urut global agar nomor sertifikat tetap unik walau entri lain dihapus.
                'seq' => (int) $items->max(fn (array $item) => (int) ($item['seq'] ?? 0)) + 1,
                'created_by' => auth()->id(),
                'created_at' => now()->toIso8601String(),
            ])->values()->all());

            return true;
        });
    }

    public static function remove(string $id): void
    {
        DB::transaction(function () use ($id): void {
            SiteSetting::putValue(self::KEY, static::lockedItems()->reject(fn (array $item) => $item['id'] === $id)->values()->all());
        });
    }

    private static function lockedItems(): Collection
    {
        return collect(SiteSetting::query()->where('key', self::KEY)->lockForUpdate()->value('value') ?? []);
    }
}
