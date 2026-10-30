<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Μία μετάδοση τραγουδιού από έναν σταθμό */
class Airplay extends Model
{
    public $timestamps = false;

    protected $fillable = ['station_id', 'song_id', 'played_at'];

    protected function casts(): array
    {
        return ['played_at' => 'datetime'];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
