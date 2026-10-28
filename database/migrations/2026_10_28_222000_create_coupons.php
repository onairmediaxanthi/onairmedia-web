<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->string('description', 300)->nullable();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->decimal('value', 8, 2);
            $table->decimal('min_subtotal', 10, 2)->nullable();
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedSmallInteger('per_customer_limit')->default(1);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // προσωπικό κουπόνι
            $table->string('source', 12)->default('campaign'); // campaign | welcome | personal
            $table->boolean('applies_to_packages')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('total')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', fn (Blueprint $table) => $table->dropConstrainedForeignId('coupon_id'));
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
