<?php

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Seeder;

/** Τα 3 slides του παλιού site, ως αφετηρία. Τρέχει μόνο αν δεν υπάρχει κανένα slide. */
class SlideSeeder extends Seeder
{
    public function run(): void
    {
        if (Slide::exists()) {
            return;
        }

        $slides = [
            ['Διαφημιστείτε εκεί που σας ακούνε', 'Δώδεκα σταθμοί, χιλιάδες ακροατές κάθε μέρα σε όλη την Ξάνθη.', 'slides/slide1.webp', 'Δείτε τα πακέτα', '/#paketa', 'zoom'],
            ['Το μεγαλύτερο ραδιοφωνικό δίκτυο της Θράκης', 'Από τα λαϊκά ως το rock: ένα κοινό για κάθε επιχείρηση.', 'slides/slide2.webp', 'Ακούστε ζωντανά', '/#stathmoi', 'pan'],
            ['Το σποτ σας, έτοιμο από εμάς', 'Κείμενο, φωνή, μουσική και μίξη από το δικό μας studio.', 'slides/slide3.webp', 'Ζητήστε προσφορά', '/#epikoinonia', 'zoom'],
        ];

        foreach ($slides as $i => [$title, $subtitle, $image, $cta, $url, $effect]) {
            Slide::create([
                'title' => $title, 'subtitle' => $subtitle, 'image_path' => $image,
                'cta_label' => $cta, 'cta_url' => $url, 'effect' => $effect,
                'sort' => ($i + 1) * 10, 'is_active' => true,
            ]);
        }
    }
}
