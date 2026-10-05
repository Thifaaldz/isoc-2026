<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cek link praktik microsite (mis. s.id/Daftar_Peserta) dengan mengaksesnya seperti curl.
 * Link dianggap valid bila bisa diakses (status 2xx setelah mengikuti redirect).
 * Halaman "Ups, link yang kamu akses Tidak Ditemukan" milik s.id dibalas 404 dengan penanda "__type":"not_found",
 * atau diarahkan ke artikel sdotid.app/.../oops-the-link-you-accessed-is-not-found-... — keduanya berarti link tidak terdaftar.
 */
class MicrositeLinkChecker
{
    private const NOT_FOUND_MARKERS = ['"__type":"not_found"', 'link yang kamu akses Tidak Ditemukan', 'oops-the-link-you-accessed-is-not-found'];

    public const NOT_REGISTERED = 'Link tersebut tidak terdaftar di microsite s.id.';

    public const SAVED_TITLE = 'Link microsite berhasil tersimpan';

    /** Pesan sukses yang sama di dashboard, halaman Praktik Microsite, dan Profile. */
    public static function savedMessage(string $url, ?string $eventTitle = null): string
    {
        return "Link {$url} terdaftar di microsite s.id dan sudah tercatat sebagai bukti praktik microsite"
            . ($eventTitle ? " untuk event {$eventTitle}" : '') . '.';
    }

    /** Tambahkan https:// bila peserta menulis link tanpa skema (s.id/Daftar_Peserta). */
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

    /** Bagian setelah s.id/ untuk kolom input yang awalan https://s.id/-nya dibuat sistem. */
    public static function sidPath(?string $url): string
    {
        return ltrim((string) preg_replace('#^(www\.)?s\.id/#i', '', self::withoutScheme($url)), '/');
    }

    /** @return array{ok: bool, url: string, reason: ?string} */
    public function check(?string $url): array
    {
        $url = self::normalize($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'url' => $url, 'reason' => self::NOT_REGISTERED];
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->withUserAgent('Mozilla/5.0 (sena microsite checker)')
                ->get($url);
        } catch (Throwable) {
            return ['ok' => false, 'url' => $url, 'reason' => 'Link tidak bisa diakses. Pastikan link sudah benar dan aktif.'];
        }

        // Semua URL yang dilewati (redirect) ikut dicek: s.id bisa mengalihkan link tak terdaftar ke artikel "oops" sdotid.app.
        $visited = implode(' ', [$url, ...$response->header('X-Guzzle-Redirect-History') ? $response->headers()['X-Guzzle-Redirect-History'] : []]);

        if ($response->status() === 404 || Str::contains($visited . ' ' . $response->body(), self::NOT_FOUND_MARKERS)) {
            return ['ok' => false, 'url' => $url, 'reason' => self::NOT_REGISTERED];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'url' => $url, 'reason' => "Link tidak bisa diakses (HTTP {$response->status()})."];
        }

        return ['ok' => true, 'url' => $url, 'reason' => null];
    }
}
