<?php

namespace App\Support;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Carbon;

class PublicEvent implements UrlRoutable
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?Carbon $date,
        public ?string $time_info,
        public ?string $location,
        public ?string $location_type,
        public ?string $category,
        public ?string $description,
        public ?string $image = null,
        public bool $registration_open = true,
        public ?int $max_participants = 500,
        public int $confirmed_count = 0,
        public bool $is_featured = true,
        public int $order = 1,
        public ?string $registration_url = null,
        public ?string $capacity_info = null,
        public string $audience_type = 'school',
        public ?Carbon $ends_at = null,
    ) {
        $this->capacity_info ??= $max_participants ? $max_participants . ' peserta' : null;
    }

    public function canRegister(): bool
    {
        return $this->registrationStatus() === 'open';
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null && $this->max_participants > 0 && $this->confirmed_count >= $this->max_participants;
    }

    /** Event sudah lewat setelah jam selesai (atau akhir hari tanggal event bila jam selesai kosong). */
    public function isPast(): bool
    {
        $endsAt = $this->ends_at ?? $this->date?->copy()->endOfDay();

        return $endsAt !== null && $endsAt->isPast();
    }

    /** open | past | closed | full */
    public function registrationStatus(): string
    {
        return match (true) {
            $this->isPast() => 'past',
            ! $this->registration_open => 'closed',
            $this->isFull() => 'full',
            default => 'open',
        };
    }

    public function registrationStatusLabel(): string
    {
        return match ($this->registrationStatus()) {
            'past' => 'Event Selesai',
            'closed' => 'Pendaftaran Ditutup',
            'full' => 'Kuota Penuh',
            default => 'Pendaftaran Dibuka',
        };
    }

    public function registrationStatusIcon(): string
    {
        return match ($this->registrationStatus()) {
            'past' => 'event_busy',
            'full' => 'group_off',
            'closed' => 'lock',
            default => 'how_to_reg',
        };
    }

    public function getRouteKey()
    {
        return $this->slug;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this;
    }

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        return null;
    }
}
