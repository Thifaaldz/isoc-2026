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
    ) {
        $this->capacity_info ??= $max_participants ? $max_participants . ' peserta' : null;
    }

    public function canRegister(): bool
    {
        return $this->registration_open && !$this->isFull();
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null && $this->confirmed_count >= $this->max_participants;
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
