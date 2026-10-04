<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cek link praktik microsite (mis. s.id/ISOC_Champion) dengan mengaksesnya seperti curl.
 * Link dianggap valid bila bisa diakses (status 2xx setelah mengikuti redirect).
 * Halaman "Ups, link yang kamu akses Tidak Ditemukan" milik s.id dibalas 404 dengan penanda "__type":"not_found".
 */
class MicrositeLinkChecker
{
    private const NOT_FOUND_MARKERS = ['"__type":"not_found"', 'link yang kamu akses Tidak Ditemukan'];

    /** Tambahkan https:// bila peserta menulis link tanpa skema (s.id/ISOC_Champion). */
    public static function normalize(?string $url): string
    {
        $url = trim((string) $url);

        return $url === '' || preg_match('#^https?://#i', $url) ? $url : 'https://' . ltrim($url, '/');
    }

    /** Link tanpa http(s):// untuk kolom input yang awalan https://-nya dibuat sistem. */
    public static function withoutScheme(?string $url): string
    {
        return ltrim((string) preg_replace('#^\s*https?://#i', '', trim((string) $url)), '/');
    }

    /** @return array{ok: bool, url: string, reason: ?string} */
    public function check(?string $url): array
    {
        $url = self::normalize($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'url' => $url, 'reason' => 'Format link tidak valid. Contoh: s.id/ISOC_Champion'];
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->withUserAgent('Mozilla/5.0 (sena microsite checker)')
                ->get($url);
        } catch (Throwable) {
            return ['ok' => false, 'url' => $url, 'reason' => 'Link tidak bisa diakses. Pastikan link sudah benar dan aktif.'];
        }

        if ($response->status() === 404 || Str::contains($response->body(), self::NOT_FOUND_MARKERS)) {
            return ['ok' => false, 'url' => $url, 'reason' => 'Link tidak ditemukan (Ups, link yang kamu akses Tidak Ditemukan). Periksa kembali link s.id Anda.'];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'url' => $url, 'reason' => "Link tidak bisa diakses (HTTP {$response->status()})."];
        }

        return ['ok' => true, 'url' => $url, 'reason' => null];
    }
}
