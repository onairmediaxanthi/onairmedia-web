<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Ψηφιακές πινακίδες LED */
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('location', 200)->nullable();
            $table->string('map_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('dimensions', 60)->nullable();
            $table->string('resolution', 30)->nullable();
            $table->unsignedSmallInteger('slot_seconds')->default(10);
            $table->unsignedSmallInteger('loops_per_hour')->nullable();
            $table->time('operating_from')->nullable();
            $table->time('operating_to')->nullable();
            $table->decimal('price_per_day', 8, 2)->nullable();
            $table->unsignedSmallInteger('min_days')->default(1);
            $table->unsignedTinyInteger('max_advertisers')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
