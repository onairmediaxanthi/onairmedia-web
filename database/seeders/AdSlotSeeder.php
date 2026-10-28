<?php

namespace Database\Seeders;

use App\Models\AdSlot;
use Illuminate\Database\Seeder;

/** Τυπικές θέσεις banner ειδησεογραφικού site. Χωρίς τιμές: μπαίνουν από τον τιμοκατάλογο. */
class AdSlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['header', 'Κορυφή σελίδας (leaderboard)', '728×90 · κινητό 320×100', 'all', 'Πάνω από τις ειδήσεις, σε όλες τις σελίδες.'],
            ['billboard', 'Billboard αρχικής', '970×250', 'desktop', 'Μεγάλη οριζόντια θέση κάτω από τα κύρια θέματα.'],
            ['sidebar-top', 'Πλαϊνή στήλη πάνω', '300×250', 'all', 'Δίπλα στις ειδήσεις, ορατή χωρίς κύλιση.'],
            ['sidebar-half', 'Πλαϊνή στήλη ψηλή (half page)', '300×600', 'desktop', 'Μεγάλη κάθετη θέση στην πλαϊνή στήλη.'],
            ['in-article', 'Μέσα στο άρθρο', '300×250 / 336×280', 'all', 'Ανάμεσα στις παραγράφους κάθε άρθρου.'],
            ['article-end', 'Τέλος άρθρου', '728×90 · κινητό 300×250', 'all', 'Αμέσως μετά το κείμενο του άρθρου.'],
            ['mobile-sticky', 'Σταθερή μπάρα κινητού', '320×50', 'mobile', 'Μένει στο κάτω μέρος της οθόνης στο κινητό.'],
            ['skin', 'Skin / takeover', 'Φόντο σελίδας', 'desktop', 'Το φόντο του site στα πλάγια, αποκλειστικά για έναν διαφημιζόμενο.'],
        ];

        foreach ($slots as $i => [$code, $name, $dim, $device, $desc]) {
            AdSlot::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'dimensions' => $dim, 'device' => $device, 'description' => $desc,
                'max_concurrent' => $code === 'skin' ? 1 : 3, 'sort' => ($i + 1) * 10,
            ]);
        }
    }
}
