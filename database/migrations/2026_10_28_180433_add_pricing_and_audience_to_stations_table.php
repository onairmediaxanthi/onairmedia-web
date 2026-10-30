<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            // Κοινό-στόχος (ηλικίες, φύλο, τάξη, πού ακούνε, ζώνες, προφίλ)
            $table->json('audience')->nullable()->after('coverage');

            // Τιμοκατάλογος (χωρίς ΦΠΑ)
            $table->unsignedSmallInteger('spot_seconds')->default(30);
            $table->decimal('price_spot', 8, 2)->nullable();
            $table->decimal('price_mention_live', 8, 2)->nullable();
            $table->decimal('price_mention_recorded', 8, 2)->nullable();
            $table->decimal('pct_first_position', 5, 2)->default(0);
            $table->decimal('pct_middle_spot', 5, 2)->default(0);
            $table->decimal('pct_sandwich', 5, 2)->default(0);
            $table->decimal('pct_fixed_hour', 5, 2)->default(0);
            $table->unsignedTinyInteger('max_spots_per_hour')->nullable();
            $table->boolean('is_bookable')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn([
                'audience', 'spot_seconds', 'price_spot', 'price_mention_live', 'price_mention_recorded',
                'pct_first_position', 'pct_middle_spot', 'pct_sandwich', 'pct_fixed_hour', 'max_spots_per_hour', 'is_bookable',
            ]);
        });
    }
};
