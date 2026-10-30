<?php

namespace Database\Seeders;

use App\Models\Screen;
use Illuminate\Database\Seeder;

/** Η πρώτη πινακίδα. Τα στοιχεία συμπληρώνονται από τον τιμοκατάλογο. */
class ScreenSeeder extends Seeder
{
    public function run(): void
    {
        Screen::query()->firstOrCreate(['slug' => 'othoni-polis'], [
            'name' => 'Οθόνη Πόλης', 'location' => 'Ξάνθη', 'slot_seconds' => 10, 'sort' => 10,
        ]);
    }
}
