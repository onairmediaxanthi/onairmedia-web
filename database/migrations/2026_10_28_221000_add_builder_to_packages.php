<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Πακέτα/προσφορές που φτιάχνονται από τα μέσα του τιμοκαταλόγου
        Schema::table('packages', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('includes');
            $table->string('pricing', 10)->default('fixed')->after('price'); // fixed | auto
            $table->decimal('discount_percent', 5, 2)->nullable()->after('pricing');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['specs', 'pricing', 'discount_percent']);
        });
    }
};
