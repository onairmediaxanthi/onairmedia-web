<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** QR code ως inline SVG (χωρίς εξωτερικές υπηρεσίες) */
final class Qr
{
    public static function svg(string $text): ?string
    {
        try {
            return Cache::rememberForever('qr:'.md5($text), fn () => (new QRCode(new QROptions([
                'outputInterface' => QRMarkupSVG::class,
                'outputBase64' => false,
                'addQuietzone' => true,
                'quietzoneSize' => 1,
                'svgAddXmlHeader' => false,
                'drawLightModules' => false,
                'markupDark' => '#121a33',
            ])))->render($text));
        } catch (Throwable) {
            return null;
        }
    }
}
