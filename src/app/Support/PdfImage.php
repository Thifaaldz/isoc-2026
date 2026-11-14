<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickPixel;
use Throwable;

/**
 * Thumbnail kecil (data URI) untuk disematkan ke PDF dompdf.
 *
 * Foto asli bukti dukung bisa berukuran beberapa MB; bila di-embed apa adanya sebagai
 * base64, HTML laporan membengkak ratusan MB dan parser dompdf kehabisan memori.
 *
 * Hasilnya:
 * - mengikuti orientasi EXIF (foto HP yang diambil tegak tidak tampil miring);
 * - sudah dibingkai pas ke kotak $boxWidth x $boxHeight dengan latar putih, karena dompdf
 *   tidak mendukung object-fit dan akan menarik (stretch) foto ke ukuran kotak;
 * - dibuat dengan Imagick bila tersedia (decode JPEG langsung dalam ukuran kecil sehingga
 *   hemat memori; memori Imagick tidak dihitung ke memory_limit PHP), dengan GD sebagai cadangan
 *   yang menolak foto terlalu besar alih-alih membuat proses PHP kehabisan memori.
 */
class PdfImage
{
    /** Naikkan bila cara membuat thumbnail berubah, agar cache lama dibuat ulang. */
    private const VERSION = 2;

    /** Folder cache di disk privat (tidak bisa diakses lewat URL publik). */
    private const CACHE_DIR = 'pdf-thumbs';

    /** Default 420x300 px = rasio kotak .evidence-thumb (28 x 20 mm) di laporan kegiatan, ~380 dpi. */
    public static function thumbnail(?string $path, int $boxWidth = 420, int $boxHeight = 300, int $quality = 75): ?string
    {
        $disk = Storage::disk('public');

        if (! $path || ! preg_match('/\.(jpe?g|png|webp|gif)$/i', $path) || ! $disk->exists($path)) {
            return null;
        }

        // Thumbnail disimpan agar laporan berikutnya tidak perlu memproses ulang foto.
        $cache = Storage::disk('local');
        $cachePath = self::CACHE_DIR . '/' . sha1(implode('|', [self::VERSION, $path, $disk->lastModified($path), $boxWidth, $boxHeight, $quality])) . '.jpg';

        if (! $cache->exists($cachePath)) {
            $jpeg = self::render($disk->path($path), $boxWidth, $boxHeight, $quality);

            if ($jpeg === null) {
                return null;
            }

            $cache->put($cachePath, $jpeg);
        }

        return 'data:image/jpeg;base64,' . base64_encode($cache->get($cachePath));
    }

    private static function render(string $file, int $boxWidth, int $boxHeight, int $quality): ?string
    {
        if (extension_loaded('imagick')) {
            $jpeg = self::renderWithImagick($file, $boxWidth, $boxHeight, $quality);

            if ($jpeg !== null) {
                return $jpeg;
            }
        }

        return self::renderWithGd($file, $boxWidth, $boxHeight, $quality);
    }

    private static function renderWithImagick(string $file, int $boxWidth, int $boxHeight, int $quality): ?string
    {
        $image = $canvas = null;

        try {
            $image = new Imagick();
            // Batasi memori Imagick; sisanya dialihkan ke disk, bukan membuat proses gagal.
            $image->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 256 * 1024 * 1024);
            // Foto besar: libjpeg men-decode langsung ke ~2x ukuran target (jauh lebih hemat untuk foto 12-108 MP).
            // Hanya untuk foto yang memang lebih besar, karena libjpeg-turbo juga bisa memperbesar foto kecil.
            $size = @getimagesize($file);
            if ($size && max($size[0], $size[1]) > max($boxWidth, $boxHeight) * 2) {
                $image->setOption('jpeg:size', ($boxWidth * 2) . 'x' . ($boxWidth * 2));
            }
            $image->readImage($file . '[0]'); // frame pertama saja untuk GIF animasi
            $image->autoOrient();
            // ImageMagick tidak memperbarui geometri page setelah transpose/transverse (EXIF 5 & 7),
            // sehingga flatten di bawah akan memotong/menggeser foto. Reset page ke ukuran gambar.
            $image->setImagePage(0, 0, 0, 0);
            $image->setImageBackgroundColor(new ImagickPixel('white'));
            $image = $image->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN); // transparansi PNG/WebP -> putih
            $image->thumbnailImage($boxWidth, $boxHeight, true);

            $canvas = new Imagick();
            $canvas->newImage($boxWidth, $boxHeight, new ImagickPixel('white'), 'jpeg');
            $canvas->compositeImage(
                $image,
                Imagick::COMPOSITE_OVER,
                intdiv($boxWidth - $image->getImageWidth(), 2),
                intdiv($boxHeight - $image->getImageHeight(), 2),
            );
            $canvas->stripImage();
            $canvas->setImageCompressionQuality($quality);

            return $canvas->getImageBlob() ?: null;
        } catch (Throwable) {
            return null;
        } finally {
            $image?->clear();
            $canvas?->clear();
        }
    }

    private static function renderWithGd(string $file, int $boxWidth, int $boxHeight, int $quality): ?string
    {
        try {
            $info = @getimagesize($file);

            if (! $info || $info[0] < 1 || $info[1] < 1) {
                return null;
            }

            [$width, $height] = $info;

            // GD memuat seluruh piksel ke memori PHP (~5 byte/piksel); kehabisan memori adalah fatal
            // error yang tidak bisa ditangkap dan membuat seluruh laporan gagal, jadi foto dilewati.
            if (! self::gdFits($width, $height, (int) filesize($file))) {
                return null;
            }

            $source = @imagecreatefromstring((string) file_get_contents($file));

            if (! $source) {
                return null;
            }

            $source = self::applyExifOrientation($source, $file, $info[2] ?? null);
            $width = imagesx($source);
            $height = imagesy($source);

            $scale = min(1, $boxWidth / $width, $boxHeight / $height);
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $target = imagecreatetruecolor($boxWidth, $boxHeight);
            // Latar putih: bingkai kotak sekaligus pengganti transparansi (JPEG tidak punya alpha).
            imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
            imagecopyresampled(
                $target, $source,
                intdiv($boxWidth - $newWidth, 2), intdiv($boxHeight - $newHeight, 2), 0, 0,
                $newWidth, $newHeight, $width, $height,
            );
            imagedestroy($source);

            ob_start();
            imagejpeg($target, null, $quality);
            imagedestroy($target);

            return ob_get_clean() ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function gdFits(int $width, int $height, int $fileSize): bool
    {
        $limit = self::bytes((string) ini_get('memory_limit'));

        if ($limit <= 0) {
            return true; // memory_limit = -1 (tanpa batas)
        }

        // Bitmap sumber + isi file + cadangan untuk proses laporan yang sedang berjalan.
        $needed = $width * $height * 5 + $fileSize * 2;

        return $needed < ($limit - memory_get_usage(true)) * 0.6;
    }

    private static function bytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /** Putar/balik bitmap GD sesuai tag EXIF Orientation (hanya JPEG). */
    private static function applyExifOrientation(\GdImage $image, string $file, ?int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        // imagerotate memutar berlawanan arah jarum jam untuk sudut positif.
        // 5 = transpose (flip + putar 90° CCW), 6 = putar 90° CW, 7 = transverse (flip + putar 90° CW), 8 = putar 90° CCW.
        $angle = match ($orientation) {
            3, 4 => 180,
            6, 7 => -90,
            5, 8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);

        return $rotated ?: $image;
    }
}
