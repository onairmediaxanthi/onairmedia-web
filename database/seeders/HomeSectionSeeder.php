<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/** Η αρχική διάταξη. Τρέχει μόνο αν δεν υπάρχει καμία ενότητα. */
class HomeSectionSeeder extends Seeder
{
    public static function quoteCta(): array
    {
        return ['type' => 'quote_cta', 'anchor' => 'prosfora', 'title' => 'Η διαφήμισή σας, σε δύο λεπτά.',
            'subtitle' => 'Πείτε μας τον στόχο, το ποσό και ποιους θέλετε να φτάσετε. Σας προτείνουμε τον καλύτερο συνδυασμό ραδιοφώνου, thrakitoday.gr και πινακίδας LED.',
            'settings' => ['primary_label' => 'Βγάλτε προσφορά σε 2 λεπτά', 'secondary_label' => 'Δωρεάν εγγραφή επιχείρησης', 'show_steps' => true]];
    }

    public static function offers(): array
    {
        return ['type' => 'offers', 'anchor' => 'prosfores', 'admin_label' => 'Πάνελ προσφορών', 'title' => 'Προσφορές της εποχής',
            'subtitle' => 'Έτοιμοι συνδυασμοί με έκπτωση, για περιορισμένο διάστημα.',
            'settings' => ['menu_label' => 'Προσφορές', 'source' => 'offers', 'limit' => 12, 'autoplay' => true]];
    }

    public function run(): void
    {
        // Νεότερες ενότητες που προστίθενται και σε υπάρχουσα αρχική, μία φορά
        if (HomeSection::exists()) {
            if (! HomeSection::where('type', 'quote_cta')->exists()) {
                HomeSection::create(self::quoteCta() + ['sort' => 15, 'is_active' => true]);
            }
            if (! HomeSection::where('type', 'offers')->exists()) {
                HomeSection::create(self::offers() + ['sort' => 17, 'is_active' => false]);
                // «Προσφορά» και «Προσφορές» δίπλα-δίπλα στο μενού μπερδεύουν: το κουμπί «Βγάλτε προσφορά» υπάρχει ήδη πάνω δεξιά
                HomeSection::where('type', 'quote_cta')->get()->each(function (HomeSection $s) {
                    if ($s->setting('menu_label') === 'Προσφορά') {
                        $s->settings = array_merge((array) $s->settings, ['menu_label' => null]);
                        $s->save();
                    }
                });
            }

            return;
        }

        $sections = [
            ['type' => 'slider', 'settings' => ['interval' => 7, 'height' => 'tall']],
            self::quoteCta(),
            ['type' => 'dial', 'anchor' => 'stathmoi', 'settings' => ['menu_label' => 'Σταθμοί']],
            ['type' => 'stations', 'settings' => ['layout' => 'cards']],
            ['type' => 'packages', 'anchor' => 'paketa', 'title' => 'Έτοιμα πακέτα προβολής',
                'subtitle' => 'Διαλέξτε ένα έτοιμο πακέτο ή ξεκινήστε από αυτό και φτιάξτε το δικό σας.',
                'settings' => ['menu_label' => 'Πακέτα', 'source' => 'all', 'limit' => 6]],
            ['type' => 'news', 'anchor' => 'eidiseis', 'title' => 'Η Θράκη σήμερα', 'settings' => ['menu_label' => 'Ειδήσεις', 'count' => 7]],
            ['type' => 'media', 'anchor' => 'diafimisi', 'title' => 'Ακούγεσαι. Φαίνεσαι. Μετράς.',
                'subtitle' => 'Τρία μέσα, ένα κοινό: όλη η Ξάνθη. Συνδυάστε τα όπως ταιριάζει στην επιχείρησή σας.',
                'settings' => ['menu_label' => 'Διαφήμιση']],
        ];

        foreach ($sections as $i => $data) {
            HomeSection::create($data + ['sort' => ($i + 1) * 10, 'is_active' => true]);
        }
    }
}
