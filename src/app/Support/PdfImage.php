<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Thumbnail kecil (data URI) untuk disematkan ke PDF dompdf.
 *
 * Foto asli bukti dukung bisa berukuran beberapa MB; bila di-embed apa adanya sebagai
 * base64, HTML laporan membengkak ratusan MB dan parser dompdf kehabisan memori.
 */
class PdfImage
{
    public static function thumbnail(?string $path, int $maxSize = 360, int $quality = 70): ?string
    {
        $disk = Storage::disk('public');

        if (! $path || ! preg_match('/\.(jpe?g|png|webp|gif)$/i', $path) || ! $disk->exists($path)) {
            return null;
        }

        // Thumbnail disimpan di disk agar laporan berikutnya tidak perlu memproses ulang foto.
        $cachePath = 'pdf-thumbs/' . sha1($path . '|' . $disk->lastModified($path) . "|{$maxSize}|{$quality}") . '.jpg';

        if (! $disk->exists($cachePath)) {
            $jpeg = self::resize($disk->path($path), $maxSize, $quality);

            if ($jpeg === null) {
                return null;
            }

            $disk->put($cachePath, $jpeg);
        }

        return 'data:image/jpeg;base64,' . base64_encode($disk->get($cachePath));
    }

    private static function resize(string $file, int $maxSize, int $quality): ?string
    {
        try {
            $info = @getimagesize($file);

            if (! $info || $info[0] < 1 || $info[1] < 1) {
                return null;
            }

            $source = @imagecreatefromstring((string) file_get_contents($file));

            if (! $source) {
                return null;
            }

            [$width, $height] = $info;
            $scale = min(1, $maxSize / max($width, $height));
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $target = imagecreatetruecolor($newWidth, $newHeight);
            // Latar putih untuk PNG/WebP transparan karena JPEG tidak punya alpha.
            imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);

            ob_start();
            imagejpeg($target, null, $quality);
            imagedestroy($target);

            return ob_get_clean() ?: null;
        } catch (Throwable) {
            return null;
        }
    }
}
