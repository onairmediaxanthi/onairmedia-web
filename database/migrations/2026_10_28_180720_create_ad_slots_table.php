<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Θέσεις banner στο thrakitoday.gr */
    public function up(): void
    {
        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 100);
            $table->string('description', 300)->nullable();
            $table->string('dimensions', 60)->nullable();
            $table->string('device', 10)->default('all'); // all | desktop | mobile
            $table->decimal('price_per_day', 8, 2)->nullable();
            $table->unsignedSmallInteger('min_days')->default(1);
            $table->unsignedTinyInteger('max_concurrent')->default(1);
            $table->string('preview_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_slots');
    }
};
