<?php

use Illuminate\Database\Migrations\Migration;

/** Νέοι κανόνες αναγνώρισης (ελληνικά = greeklish): ξαναϋπολογισμός και ένωση διπλών */
return new class extends Migration
{
    public function up(): void
    {
        \App\Models\Song::renormalizeAll();
    }

    public function down(): void {}
};
