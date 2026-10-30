<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Δεύτερος τιμοκατάλογος: μεγάλα αστικά κέντρα (ραδιόφωνο με χρέωση ανά δευτερόλεπτο) */
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->decimal('metro_price_second', 8, 4)->nullable()->after('price_mention_recorded');
            $table->decimal('metro_mention_live', 8, 2)->nullable()->after('metro_price_second');
            $table->decimal('metro_mention_recorded', 8, 2)->nullable()->after('metro_mention_live');
        });
        Schema::table('ad_slots', fn (Blueprint $table) => $table->decimal('metro_price_per_day', 8, 2)->nullable()->after('price_per_day'));
        Schema::table('screens', fn (Blueprint $table) => $table->decimal('metro_price_per_day', 8, 2)->nullable()->after('price_per_day'));
        Schema::table('quotes', fn (Blueprint $table) => $table->string('price_tier', 10)->default('local')->after('source'));
        Schema::table('packages', fn (Blueprint $table) => $table->string('price_tier', 10)->default('all')->after('pricing'));
    }

    public function down(): void
    {
        Schema::table('stations', fn (Blueprint $table) => $table->dropColumn(['metro_price_second', 'metro_mention_live', 'metro_mention_recorded']));
        Schema::table('ad_slots', fn (Blueprint $table) => $table->dropColumn('metro_price_per_day'));
        Schema::table('screens', fn (Blueprint $table) => $table->dropColumn('metro_price_per_day'));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropColumn('price_tier'));
        Schema::table('packages', fn (Blueprint $table) => $table->dropColumn('price_tier'));
    }
};
