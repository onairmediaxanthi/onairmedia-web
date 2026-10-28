<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** PDF προσφοράς (A4, ελληνικά με DejaVu Sans) */
class QuotePdf
{
    public static function render(Quote $quote): string
    {
        $quote->loadMissing('user.business');

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'business' => $quote->user?->business,
            'company' => Setting::allValues(),
            'logo' => self::logoDataUri(),
        ])->setPaper('a4')->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false])->output();
    }

    public static function filename(Quote $quote): string
    {
        return 'prosfora-'.preg_replace('/[^0-9-]/', '', (string) $quote->number).'.pdf';
    }

    /** Το λογότυπο ως PNG (το dompdf δεν διαβάζει πάντα webp) */
    protected static function logoDataUri(): ?string
    {
        try {
            $path = Setting::get('logo_light');
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return null;
            }
            $bytes = Storage::disk('public')->get($path);
            if (str_ends_with(strtolower($path), '.png')) {
                return 'data:image/png;base64,'.base64_encode($bytes);
            }
            if (! function_exists('imagecreatefromstring') || ! ($img = @imagecreatefromstring($bytes))) {
                return null;
            }
            imagesavealpha($img, true);
            ob_start();
            imagepng($img);
            $png = ob_get_clean();
            imagedestroy($img);

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (Throwable) {
            return null;
        }
    }
}
