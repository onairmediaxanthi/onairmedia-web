<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('business_type', 60);
            $table->string('business_type_other')->nullable();
            $table->string('vat', 9)->unique();
            $table->string('tax_office')->nullable();
            $table->string('gemi', 12)->nullable();
            $table->string('address');
            $table->string('city', 80);
            $table->string('postal_code', 5);
            $table->string('mobile', 20);
            $table->string('phone', 20)->nullable();
            $table->string('contact_person');
            $table->string('website')->nullable();
            $table->timestamp('vat_verified_at')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->string('terms_version', 20);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->string('signup_ip', 45)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
