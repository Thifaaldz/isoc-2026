<?php

use App\Support\PdfImage;
use Illuminate\Support\Facades\Storage;

/**
 * Foto uji 600x400 (landscape) dengan blok merah di pojok kiri atas, plus tag EXIF Orientation
 * seperti yang ditulis kamera HP. Setelah diproses, blok merah harus berada di pojok yang benar.
 */
function exifJpeg(int $orientation): string
{
    $img = imagecreatetruecolor(600, 400);
    imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
    imagefilledrectangle($img, 0, 0, 199, 132, imagecolorallocate($img, 255, 0, 0));
    ob_start();
    imagejpeg($img, null, 95);
    $jpeg = ob_get_clean();
    imagedestroy($img);

    // Segmen APP1 EXIF minimal: IFD0 dengan satu tag Orientation (0x0112).
    $tiff = "MM\x00\x2A" . pack('N', 8) . pack('n', 1) . pack('nnNnn', 0x0112, 3, 1, $orientation, 0) . pack('N', 0);
    $app1 = "\xFF\xE1" . pack('n', 2 + 6 + strlen($tiff)) . "Exif\x00\x00" . $tiff;

    return substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
}

function thumbImage(string $dataUri): GdImage
{
    expect($dataUri)->toStartWith('data:image/jpeg;base64,');

    return imagecreatefromstring(base64_decode(substr($dataUri, strpos($dataUri, ',') + 1)));
}

function isRed(GdImage $img, int $x, int $y): bool
{
    $c = imagecolorsforindex($img, imagecolorat($img, $x, $y));

    return $c['red'] > 180 && $c['green'] < 90 && $c['blue'] < 90;
}

/** Pojok yang berwarna merah di dalam area foto (di luar bingkai putih). */
function redCorner(GdImage $img, bool $portrait): string
{
    // Kotak 420x300: foto landscape 3:2 -> 420x280 (y 10..290); portrait 2:3 -> 200x300 (x 110..310).
    [$x0, $y0, $x1, $y1] = $portrait ? [110, 0, 309, 299] : [0, 10, 419, 289];
    $corners = [
        'TL' => [$x0 + 15, $y0 + 15], 'TR' => [$x1 - 15, $y0 + 15],
        'BL' => [$x0 + 15, $y1 - 15], 'BR' => [$x1 - 15, $y1 - 15],
    ];
    $red = array_keys(array_filter($corners, fn ($p) => isRed($img, ...$p)));

    return implode(',', $red);
}

/** Pojok tujuan blok merah (awalnya kiri atas) untuk tiap nilai EXIF Orientation. */
dataset('orientations', [
    'normal (1)' => [1, 'TL', false],
    'flip horizontal (2)' => [2, 'TR', false],
    'putar 180 (3)' => [3, 'BR', false],
    'flip vertikal (4)' => [4, 'BL', false],
    'transpose (5)' => [5, 'TL', true],
    'putar 90 searah jarum jam (6) - foto HP tegak' => [6, 'TR', true],
    'transverse (7)' => [7, 'BR', true],
    'putar 90 berlawanan jarum jam (8)' => [8, 'BL', true],
]);

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

test('Imagick: foto mengikuti orientasi EXIF dan dibingkai pas 420x300 tanpa distorsi', function (int $orientation, string $corner, bool $portrait) {
    if (! extension_loaded('imagick')) {
        $this->markTestSkipped('Ekstensi imagick tidak tersedia.');
    }
    Storage::disk('public')->put("evidence/o{$orientation}.jpg", exifJpeg($orientation));

    $img = thumbImage(PdfImage::thumbnail("evidence/o{$orientation}.jpg"));

    expect([imagesx($img), imagesy($img)])->toBe([420, 300])
        ->and(redCorner($img, $portrait))->toBe($corner);
    // Bingkai putih di sisi pendek: foto tidak ditarik memenuhi kotak.
    expect(isRed($img, $portrait ? 50 : 15, $portrait ? 15 : 3))->toBeFalse();
})->with('orientations');

test('GD (cadangan): foto mengikuti orientasi EXIF dan dibingkai pas 420x300', function (int $orientation, string $corner, bool $portrait) {
    Storage::disk('public')->put("evidence/o{$orientation}.jpg", exifJpeg($orientation));
    $render = new ReflectionMethod(PdfImage::class, 'renderWithGd');

    $jpeg = $render->invoke(null, Storage::disk('public')->path("evidence/o{$orientation}.jpg"), 420, 300, 75);
    $img = thumbImage('data:image/jpeg;base64,' . base64_encode($jpeg));

    expect([imagesx($img), imagesy($img)])->toBe([420, 300])
        ->and(redCorner($img, $portrait))->toBe($corner);
})->with('orientations');

test('PNG transparan menjadi latar putih dan cache disimpan di disk privat', function () {
    $img = imagecreatetruecolor(300, 300);
    imagesavealpha($img, true);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    ob_start();
    imagepng($img);
    Storage::disk('public')->put('evidence/transparan.png', ob_get_clean());

    $thumb = thumbImage(PdfImage::thumbnail('evidence/transparan.png'));
    $center = imagecolorsforindex($thumb, imagecolorat($thumb, 210, 150));

    expect($center['red'])->toBeGreaterThan(240)->and($center['green'])->toBeGreaterThan(240)->and($center['blue'])->toBeGreaterThan(240)
        ->and(Storage::disk('local')->files('pdf-thumbs'))->toHaveCount(1)
        ->and(Storage::disk('public')->exists('pdf-thumbs'))->toBeFalse();
});

test('berkas bukan gambar, hilang, atau rusak tidak membuat laporan gagal', function () {
    Storage::disk('public')->put('evidence/rusak.jpg', 'bukan gambar');
    Storage::disk('public')->put('evidence/dokumen.pdf', '%PDF-1.4');

    expect(PdfImage::thumbnail('evidence/rusak.jpg'))->toBeNull()
        ->and(PdfImage::thumbnail('evidence/dokumen.pdf'))->toBeNull()
        ->and(PdfImage::thumbnail('evidence/tidak-ada.jpg'))->toBeNull()
        ->and(PdfImage::thumbnail(null))->toBeNull();
});

test('GD melewati foto yang terlalu besar untuk sisa memori, bukan kehabisan memori', function () {
    $fits = new ReflectionMethod(PdfImage::class, 'gdFits');
    $limit = ini_get('memory_limit');
    ini_set('memory_limit', '256M');

    try {
        expect($fits->invoke(null, 1600, 1200, 500_000))->toBeTrue()          // foto biasa
            ->and($fits->invoke(null, 12000, 9000, 20_000_000))->toBeFalse(); // 108 MP
    } finally {
        ini_set('memory_limit', $limit);
    }
});
