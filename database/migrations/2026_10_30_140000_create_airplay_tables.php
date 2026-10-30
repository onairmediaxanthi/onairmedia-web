<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Καταγραφή «τι παίζει» ανά σταθμό, σχεδιασμένη για ελάχιστο χώρο:
 * κάθε τραγούδι μία φορά (songs), κάθε μετάδοση μια μικρή γραμμή (airplays),
 * ημερήσια σύνοψη (airplay_daily, προσωρινή) και Top 40 εβδομάδας/μήνα (charts· ο μήνας είναι το μόνιμο αρχείο).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('artist', 200)->nullable();
            $table->string('title', 200);
            $table->char('norm', 40)->unique(); // sha1 του κανονικοποιημένου «καλλιτέχνης|τίτλος»
            $table->boolean('ignored')->default(false); // jingle, διαφήμιση, εκπομπή: εκτός Top 40
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('airplays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->dateTime('played_at');
            $table->index(['station_id', 'played_at']);
            $table->index('played_at');
        });

        Schema::create('airplay_daily', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedSmallInteger('plays')->default(0);
            $table->primary(['station_id', 'day', 'song_id']);
            $table->index(['song_id', 'day']);
        });

        // Λίστες: εβδομαδιαίες (για τη σελίδα του σταθμού, σβήνονται μόνες τους)
        // και μηνιαίες (το μόνιμο αρχείο· σβήνονται μόνο από τη διαχείριση)
        Schema::create('charts', function (Blueprint $table) {
            $table->foreignId('station_id')->constrained()->cascadeOnDelete();
            $table->string('period', 5); // week | month
            $table->date('period_start');
            $table->unsignedTinyInteger('position');
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('plays');
            $table->unsignedTinyInteger('prev_position')->nullable();
            $table->unsignedTinyInteger('peak');
            $table->unsignedSmallInteger('periods_on')->default(1);
            $table->primary(['station_id', 'period', 'period_start', 'position']);
            $table->index(['song_id', 'period']);
        });

        Schema::table('stations', function (Blueprint $table) {
            $table->boolean('airplay_enabled')->default(true);
            $table->boolean('chart_public')->default(true);
            $table->string('last_title', 400)->nullable();
            $table->timestamp('last_airplay_at')->nullable();
            $table->timestamp('last_seen_at')->nullable(); // τελευταία φορά που ήρθε οποιαδήποτε πληροφορία από το live24
        });
    }

    public function down(): void
    {
        Schema::table('stations', fn (Blueprint $table) => $table->dropColumn(['airplay_enabled', 'chart_public', 'last_title', 'last_airplay_at', 'last_seen_at']));
        Schema::dropIfExists('charts');
        Schema::dropIfExists('airplay_daily');
        Schema::dropIfExists('airplays');
        Schema::dropIfExists('songs');
    }
};
