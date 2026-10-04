<?php

namespace App\Support;

use Filament\Forms\Components\TextInput;

/** Aturan nomor identitas: NIK tepat 16 angka, NISN 10-11 angka (keduanya opsional, divalidasi bila diisi). */
class IdentityNumber
{
    public const NIK_REGEX = '/^\d{16}$/';

    public const NISN_REGEX = '/^\d{10,11}$/';

    public const NIK_MESSAGE = 'NIK harus 16 digit angka.';

    public const NISN_MESSAGE = 'NISN harus 10-11 digit angka.';

    public static function nik(TextInput $input): TextInput
    {
        return $input->regex(self::NIK_REGEX)->maxLength(16)
            ->validationMessages(['regex' => self::NIK_MESSAGE])
            ->extraInputAttributes(['inputmode' => 'numeric']);
    }

    /** @param  bool|\Closure  $isNisn  false untuk NIM mahasiswa (tidak dibatasi 10-11 digit). */
    public static function nisn(TextInput $input, bool | \Closure $isNisn = true): TextInput
    {
        return $input
            ->rules(fn (TextInput $component) => $component->evaluate($isNisn) ? ['regex:' . self::NISN_REGEX] : [])
            ->maxLength(fn (TextInput $component) => $component->evaluate($isNisn) ? 11 : 50)
            ->validationMessages(['regex' => self::NISN_MESSAGE])
            ->extraInputAttributes(['inputmode' => 'numeric']);
    }
}
