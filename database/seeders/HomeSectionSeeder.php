<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use Illuminate\Database\Seeder;

/** Η αρχική διάταξη. Τρέχει μόνο αν δεν υπάρχει καμία ενότητα. */
class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        if (HomeSection::exists()) {
            return;
        }

        $sections = [
            ['type' => 'slider', 'settings' => ['interval' => 7, 'height' => 'tall']],
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
