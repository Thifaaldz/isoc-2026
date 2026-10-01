<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class IndonesiaRegion
{
    private const BASE_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    public static function provinces(): array
    {
        return self::remember('provinces', 'provinces.json', self::fallbackProvinces());
    }

    public static function regencies(?string $provinceCode): array
    {
        if (! $provinceCode) {
            return [];
        }

        return self::remember("regencies.$provinceCode", "regencies/$provinceCode.json", self::fallbackRegencies($provinceCode));
    }

    public static function districts(?string $regencyCode): array
    {
        if (! $regencyCode) {
            return [];
        }

        return self::remember("districts.$regencyCode", "districts/$regencyCode.json", self::fallbackDistricts($regencyCode));
    }

    public static function villages(?string $districtCode): array
    {
        if (! $districtCode) {
            return [];
        }

        return self::remember("villages.$districtCode", "villages/$districtCode.json", self::fallbackVillages($districtCode));
    }

    private static function remember(string $key, string $path, array $fallback): array
    {
        return Cache::remember("indonesia_regions.$key", now()->addWeek(), function () use ($path, $fallback) {
            try {
                $response = Http::timeout(5)->retry(2, 150)->get(self::BASE_URL . '/' . $path);

                if (! $response->successful()) {
                    return $fallback;
                }

                $items = collect($response->json())
                    ->filter(fn ($item) => filled($item['id'] ?? null) && filled($item['name'] ?? null))
                    ->mapWithKeys(fn ($item) => [(string) $item['id'] => (string) $item['name']])
                    ->all();

                return $items ?: $fallback;
            } catch (\Throwable) {
                return $fallback;
            }
        });
    }

    private static function fallbackProvinces(): array
    {
        return [
            '31' => 'DKI JAKARTA',
            '32' => 'JAWA BARAT',
            '33' => 'JAWA TENGAH',
            '35' => 'JAWA TIMUR',
            '36' => 'BANTEN',
        ];
    }

    private static function fallbackRegencies(string $provinceCode): array
    {
        return match ($provinceCode) {
            '31' => [
                '3101' => 'KABUPATEN KEPULAUAN SERIBU',
                '3171' => 'KOTA JAKARTA SELATAN',
                '3172' => 'KOTA JAKARTA TIMUR',
                '3173' => 'KOTA JAKARTA PUSAT',
                '3174' => 'KOTA JAKARTA BARAT',
                '3175' => 'KOTA JAKARTA UTARA',
            ],
            '36' => [
                '3601' => 'KABUPATEN PANDEGLANG',
                '3602' => 'KABUPATEN LEBAK',
                '3603' => 'KABUPATEN TANGERANG',
                '3604' => 'KABUPATEN SERANG',
                '3671' => 'KOTA TANGERANG',
                '3672' => 'KOTA CILEGON',
                '3673' => 'KOTA SERANG',
                '3674' => 'KOTA TANGERANG SELATAN',
            ],
            default => [],
        };
    }

    private static function fallbackDistricts(string $regencyCode): array
    {
        return match ($regencyCode) {
            '3171' => [
                '3171010' => 'JAGAKARSA',
                '3171020' => 'PASAR MINGGU',
                '3171030' => 'CILANDAK',
                '3171040' => 'PESANGGRAHAN',
                '3171050' => 'KEBAYORAN LAMA',
                '3171060' => 'KEBAYORAN BARU',
                '3171070' => 'MAMPANG PRAPATAN',
                '3171080' => 'PANCORAN',
                '3171090' => 'TEBET',
                '3171100' => 'SETIABUDI',
            ],
            '3173' => [
                '3173010' => 'GAMBIR',
                '3173020' => 'SAWAH BESAR',
                '3173030' => 'KEMAYORAN',
                '3173040' => 'SENEN',
                '3173050' => 'CEMPAKA PUTIH',
                '3173060' => 'MENTENG',
                '3173070' => 'TANAH ABANG',
                '3173080' => 'JOHAR BARU',
            ],
            default => [],
        };
    }

    private static function fallbackVillages(string $districtCode): array
    {
        return match ($districtCode) {
            '3171090' => [
                '3171090001' => 'MENTENG DALAM',
                '3171090002' => 'TEBET BARAT',
                '3171090003' => 'TEBET TIMUR',
                '3171090004' => 'KEBON BARU',
                '3171090005' => 'BUKIT DURI',
                '3171090006' => 'MANGGARAI SELATAN',
                '3171090007' => 'MANGGARAI',
            ],
            '3173010' => [
                '3173010001' => 'GAMBIR',
                '3173010002' => 'KEBON KELAPA',
                '3173010003' => 'PETOJO UTARA',
                '3173010004' => 'DURI PULO',
                '3173010005' => 'CIDENG',
                '3173010006' => 'PETOJO SELATAN',
            ],
            default => [],
        };
    }
}
