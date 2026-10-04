<?php

namespace App\Rules;

use App\Services\MicrositeLinkChecker;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Link microsite harus bisa diakses dan bukan halaman "Tidak Ditemukan" s.id. */
class ReachableMicrositeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = app(MicrositeLinkChecker::class)->check(is_string($value) ? $value : null);

        if (! $result['ok']) {
            $fail($result['reason']);
        }
    }
}
