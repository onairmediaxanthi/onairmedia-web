<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Όλοι οι seeders είναι ασφαλείς να ξανατρέξουν: δεν πατάνε αλλαγές της διαχείρισης. */
    public function run(): void
    {
        $this->call([
            StationSeeder::class,
            SlideSeeder::class,
            HomeSectionSeeder::class,
            PackageSeeder::class,
        ]);
    }
}
