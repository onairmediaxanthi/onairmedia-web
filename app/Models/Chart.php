<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Μία θέση στο Top 40 ενός σταθμού (εβδομάδας ή μήνα) */
class Chart extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    public const PERIODS = ['week' => 'Εβδομάδα', 'month' => 'Μήνας'];

    protected $fillable = ['station_id', 'period', 'period_start', 'position', 'song_id', 'plays', 'prev_position', 'peak', 'periods_on'];

    protected function casts(): array
    {
        return ['period_start' => 'date'];
    }

    /** «Οκτώβριος 2026» ή «12/10 – 18/10/2026» */
    public static function periodLabel(string $period, \Illuminate\Support\Carbon $start): string
    {
        return $period === 'month'
            ? mb_convert_case($start->locale('el')->isoFormat('MMMM YYYY'), MB_CASE_TITLE)
            : $start->format('d/m').' – '.$start->copy()->addDays(6)->format('d/m/Y');
    }

    public function song(): BelongsTo
    {
        return $this->belongsTo(Song::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /** 'new' | 'up' | 'down' | 'same' */
    public function movement(): string
    {
        return match (true) {
            $this->prev_position === null => 'new',
            $this->prev_position > $this->position => 'up',
            $this->prev_position < $this->position => 'down',
            default => 'same',
        };
    }
}
