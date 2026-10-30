<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Λογότυπα (συχνά webp) ως PNG: το dompdf και αρκετά προγράμματα email δεν διαβάζουν webp.
 * Η μετατροπή γίνεται μία φορά και φυλάσσεται στο public/cache/png.
 */
final class LogoPng
{
    public static function path(?string $source, int $maxWidth = 400): ?string
    {
        if (! $source) {
            return null;
        }
        $disk = Storage::disk('public');
        try {
            if (! $disk->exists($source)) {
                return null;
            }
            $target = 'cache/png/'.md5($source.'|'.$disk->lastModified($source).'|'.$maxWidth).'.png';
            if ($disk->exists($target)) {
                return $target;
            }
            if (! function_exists('imagecreatefromstring') || ! ($img = @imagecreatefromstring($disk->get($source)))) {
                return null;
            }
            if (imagesx($img) > $maxWidth) {
                $scaled = imagescale($img, $maxWidth, -1, IMG_BICUBIC);
                imagedestroy($img);
                $img = $scaled;
            }
            imagealphablending($img, false);
            imagesavealpha($img, true);
            ob_start();
            imagepng($img, null, 9);
            $disk->put($target, ob_get_clean());
            imagedestroy($img);

            return $target;
        } catch (Throwable) {
            return null;
        }
    }

    public static function url(?string $source, int $maxWidth = 400): ?string
    {
        $p = self::path($source, $maxWidth);

        return $p ? Storage::disk('public')->url($p) : null;
    }

    public static function dataUri(?string $source, int $maxWidth = 400): ?string
    {
        $p = self::path($source, $maxWidth);

        return $p ? 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($p)) : null;
    }
}
