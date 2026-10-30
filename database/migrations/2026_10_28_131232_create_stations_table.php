<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->decimal('frequency', 4, 1)->nullable();
            $table->string('slogan')->nullable();
            $table->text('description')->nullable();
            $table->string('genre')->nullable();
            $table->string('coverage')->nullable();
            $table->string('stream_url');
            $table->string('now_playing_mount')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('color', 7)->default('#e11d48');
            $table->string('website')->nullable();
            $table->json('socials')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->unsignedSmallInteger('sort')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
