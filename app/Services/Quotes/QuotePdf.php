<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\Setting;
use App\Support\Qr;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF πρότασης (A4, ελληνικά, λογότυπα μέσων), σε ένα από τα σχέδια που διαλέγει η εταιρεία */
class QuotePdf
{
    public const DESIGNS = [
        'classic' => ['label' => 'Κλασικό', 'text' => 'Το σημερινό: σκούρα κεφαλίδα, μπλοκ ανά μέσο.'],
        'aurora' => ['label' => 'Aurora · έντονο', 'text' => 'Μεγάλο εξώφυλλο στο ροζ της On Air, κάρτες μέσων, QR για έγκριση online.'],
        'editorial' => ['label' => 'Editorial · κομψό', 'text' => 'Λευκό, καθαρό, σαν περιοδικό: μεγάλοι τίτλοι, λεπτές γραμμές, πολύς αέρας.'],
        'night' => ['label' => 'Night · premium', 'text' => 'Σκούρο, με χρυσές λεπτομέρειες και το χρώμα κάθε σταθμού.'],
    ];

    public static function design(?string $design = null): string
    {
        $d = $design ?? (string) Setting::get('pdf_design', 'classic');

        return isset(self::DESIGNS[$d]) ? $d : 'classic';
    }

    public static function render(Quote $quote, ?string $design = null): string
    {
        $quote->loadMissing(['user.business', 'createdBy']);
        $design = self::design($design);
        if (! is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0775, true);
        }

        return Pdf::loadView($design === 'classic' ? 'pdf.quote' : 'pdf.designs.'.$design, self::data($quote))
            ->setPaper('a4')
            ->setOption(['defaultFont' => $design === 'classic' ? 'DejaVu Sans' : 'Commissioner', 'isRemoteEnabled' => false, 'isFontSubsettingEnabled' => true] + ($design === 'classic' ? [] : ['dpi' => 144]))
            ->output();
    }

    /** Όλα όσα χρειάζεται ένα σχέδιο, έτοιμα (κοινά για όλα) */
    public static function data(Quote $quote): array
    {
        $blocks = QuotePresenter::blocks($quote, 'data');
        $business = $quote->user?->business;
        $discounts = QuotePresenter::discounts($quote);
        $link = $quote->user ? route('quotes.show', $quote) : url('/');

        return [
            'quote' => $quote,
            'business' => $business,
            'company' => Setting::allValues(),
            'logo' => QuotePresenter::companyLogo('data'),
            'logoDark' => \App\Support\LogoPng::dataUri(Setting::get('logo_dark'), 600),
            'blocks' => $blocks,
            'media' => array_values(array_filter($blocks, fn ($b) => $b['kind'] !== 'other')),
            'extras' => array_values(array_filter($blocks, fn ($b) => $b['kind'] === 'other')),
            'strip' => QuotePresenter::mediaStrip($blocks),
            'intro' => QuotePresenter::intro($quote),
            'by' => $quote->createdBy,
            'customer' => $business?->displayName() ?? $quote->user?->name,
            'vatLabel' => rtrim(rtrim((string) $quote->vat_rate, '0'), '.'),
            'discounts' => $discounts,
            'beforeDiscount' => (float) $quote->subtotal - array_sum(array_column($discounts, 'amount')),
            'm' => fn ($v) => QuoteCalculator::money($v),
            // Ελληνικά κεφαλαία χωρίς τόνους (το CSS uppercase τους κρατά)
            'up' => fn (?string $t) => strtr(mb_strtoupper((string) $t), ['Ά' => 'Α', 'Έ' => 'Ε', 'Ή' => 'Η', 'Ί' => 'Ι', 'Ό' => 'Ο', 'Ύ' => 'Υ', 'Ώ' => 'Ω', 'ΐ' => 'Ϊ', 'ΰ' => 'Ϋ']),
            'link' => $link,
            'qr' => Qr::pngDataUri($link),
            'fontDir' => resource_path('fonts/commissioner'),
            'spots' => (int) collect((array) $quote->items)->where('type', 'radio')->sum(fn ($l) => (int) ($l['quantity'] ?? 0)),
        ];
    }

    public static function filename(Quote $quote): string
    {
        return 'prosfora-'.preg_replace('/[^0-9-]/', '', (string) $quote->number).'.pdf';
    }
}
