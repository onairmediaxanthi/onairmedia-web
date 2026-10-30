<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Πόσες φορές έπαιξε ένα τραγούδι σε έναν σταθμό μια μέρα (για στατιστικά και Top 40) */
class AirplayDaily extends Model
{
    protected $table = 'airplay_daily';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = ['station_id', 'song_id', 'day', 'plays'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }
}
