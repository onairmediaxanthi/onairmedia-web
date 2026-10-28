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
            'settings' => ['menu_label' => 'Προσφορά', 'primary_label' => 'Βγάλτε προσφορά σε 2 λεπτά', 'secondary_label' => 'Δωρεάν εγγραφή επιχείρησης', 'show_steps' => true]];
    }

    public function run(): void
    {
        // Νεότερες ενότητες που προστίθενται και σε υπάρχουσα αρχική, μία φορά
        if (HomeSection::exists()) {
            if (! HomeSection::where('type', 'quote_cta')->exists()) {
                HomeSection::create(self::quoteCta() + ['sort' => 15, 'is_active' => true]);
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
