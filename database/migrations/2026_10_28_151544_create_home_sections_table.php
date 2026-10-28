<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('admin_label')->nullable();
            $table->string('title')->nullable();
            $table->string('subtitle', 300)->nullable();
            $table->string('anchor', 40)->nullable();
            $table->json('settings')->nullable();
            $table->boolean('show_mobile')->default(true);
            $table->boolean('show_tablet')->default(true);
            $table->boolean('show_desktop')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('sort')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
