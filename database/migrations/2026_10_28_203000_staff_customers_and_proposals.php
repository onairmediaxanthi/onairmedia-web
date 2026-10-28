<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ποιος άνοιξε τον πελάτη: ο ίδιος (self) ή το προσωπικό (staff)
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('source', 10)->default('self')->after('user_id');
            $table->foreignId('created_by_id')->nullable()->after('source')->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable()->after('created_by_id');
            // Πελάτης από τη διαχείριση αποδέχεται τους όρους όταν ενεργοποιήσει τον λογαριασμό του
            $table->timestamp('terms_accepted_at')->nullable()->change();
            $table->string('terms_version', 20)->nullable()->change();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->text('intro')->nullable()->after('title');
            $table->timestamp('sent_at')->nullable()->after('submitted_at');
            $table->unsignedSmallInteger('sent_count')->default(0)->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn(['intro', 'sent_at', 'sent_count']);
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn(['source', 'invited_at']);
        });
    }
};
