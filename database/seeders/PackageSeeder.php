<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Προτάσεις πακέτων από την ανάλυση. Μπαίνουν ΑΝΕΝΕΡΓΑ και χωρίς τιμή:
 * η εταιρεία ορίζει τιμές και περιεχόμενο και τα ενεργοποιεί από τη διαχείριση.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        if (Package::exists()) {
            return;
        }

        $packages = [
            ['first-frequency', 'Πρώτη Συχνότητα', 'Ένας σταθμός, η πρώτη σας γνωριμία με το ραδιόφωνο.', 'radio', '#0ea5e9', null],
            ['prime-time', 'Prime Time', 'Τα σποτ σας στις ώρες με τους περισσότερους ακροατές.', 'radio', '#f59e0b', 'Δημοφιλές'],
            ['city-signal', 'Σήμα Πόλης', 'Ραδιόφωνο και Οθόνη Πόλης: ακούγεστε και φαίνεστε.', 'radio,led', '#c80c92', null],
            ['sound-news', 'Ήχος & Είδηση', 'Ραδιόφωνο μαζί με banner ή άρθρο στο thrakitoday.gr.', 'radio,news', '#16a34a', null],
            ['all-thrace', 'Πανθρακικό', 'Όλα τα μέσα, όλη η Ξάνθη, μία καμπάνια.', 'radio,news,led,creative', '#7c3aed', 'Πλήρες'],
        ];

        foreach ($packages as $i => [$slug, $name, $tagline, $mix, $color, $badge]) {
            Package::create([
                'slug' => $slug, 'name' => $name, 'tagline' => $tagline, 'media_mix' => $mix,
                'color' => $color, 'badge' => $badge, 'includes' => [],
                'sort' => ($i + 1) * 10, 'is_active' => false, 'is_featured' => $slug === 'city-signal',
            ]);
        }
    }
}
