<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF πρότασης (A4, ελληνικά με DejaVu Sans, λογότυπα μέσων) */
class QuotePdf
{
    public static function render(Quote $quote): string
    {
        $quote->loadMissing(['user.business', 'createdBy']);

        return Pdf::loadView('pdf.quote', [
            'quote' => $quote,
            'business' => $quote->user?->business,
            'company' => Setting::allValues(),
            'logo' => QuotePresenter::companyLogo('data'),
        ])->setPaper('a4')->setOption(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false])->output();
    }

    public static function filename(Quote $quote): string
    {
        return 'prosfora-'.preg_replace('/[^0-9-]/', '', (string) $quote->number).'.pdf';
    }
}
