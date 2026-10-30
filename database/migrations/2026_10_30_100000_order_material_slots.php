<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_files', function (Blueprint $table) {
            $table->string('slot', 40)->nullable()->after('order_id'); // σε ποιο «κουτί» υλικού ανήκει (radio:30, banner:3, led:1, logo…)
            $table->json('meta')->nullable()->after('size');           // διάρκεια, kbps, διαστάσεις, προειδοποιήσεις
            $table->timestamp('purged_at')->nullable();                 // το αρχείο σβήστηκε από τον server, η εγγραφή μένει
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->json('brief')->nullable()->after('deposit_note');   // κείμενα πελάτη ανά κουτί (κείμενο σποτ, σύνδεσμος banner…)
            $table->timestamp('files_purged_at')->nullable()->after('invoice_path');
        });
    }

    public function down(): void
    {
        Schema::table('order_files', fn (Blueprint $table) => $table->dropColumn(['slot', 'meta', 'purged_at']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['brief', 'files_purged_at']));
    }
};
