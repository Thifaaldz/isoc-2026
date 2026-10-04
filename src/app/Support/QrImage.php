<?php

namespace App\Support;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/** QR code PNG (data URI) untuk ditampilkan dan diunduh dari panel. */
class QrImage
{
    public static function png(string $url, int $scale = 10): ?string
    {
        try {
            return (new QRCode(new QROptions([
                'outputType' => QROutputInterface::GDIMAGE_PNG,
                'scale' => $scale,
                'quietzoneSize' => 2,
                'outputBase64' => true,
            ])))->render($url);
        } catch (\Throwable) {
            return null;
        }
    }
}
