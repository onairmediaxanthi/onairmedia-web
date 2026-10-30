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
    /** PNG σε data URI, για τα PDF (το dompdf αποδίδει πιο σωστά εικόνα παρά SVG) */
    public static function pngDataUri(string $text, string $dark = '#121a33'): ?string
    {
        try {
            return Cache::rememberForever('qrpng:'.md5($text.$dark), function () use ($text, $dark) {
                [$r, $g, $b] = sscanf($dark, '#%02x%02x%02x');

                return (new QRCode(new QROptions([
                    'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                    'outputBase64' => true,
                    'scale' => 8,
                    'addQuietzone' => true,
                    'quietzoneSize' => 1,
                    'moduleValues' => [\chillerlan\QRCode\Data\QRMatrix::M_DATA_DARK => [$r, $g, $b], \chillerlan\QRCode\Data\QRMatrix::M_FINDER_DARK => [$r, $g, $b],
                        \chillerlan\QRCode\Data\QRMatrix::M_ALIGNMENT_DARK => [$r, $g, $b], \chillerlan\QRCode\Data\QRMatrix::M_TIMING_DARK => [$r, $g, $b],
                        \chillerlan\QRCode\Data\QRMatrix::M_FORMAT_DARK => [$r, $g, $b], \chillerlan\QRCode\Data\QRMatrix::M_VERSION_DARK => [$r, $g, $b],
                        \chillerlan\QRCode\Data\QRMatrix::M_FINDER_DOT => [$r, $g, $b]],
                ])))->render($text);
            });
        } catch (Throwable) {
            return null;
        }
    }

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
