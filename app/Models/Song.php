<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Normalizer;

class Song extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['artist', 'title', 'norm', 'ignored'];

    protected function casts(): array
    {
        return ['ignored' => 'boolean'];
    }

    public function airplays(): HasMany
    {
        return $this->hasMany(Airplay::class);
    }

    public function daily(): HasMany
    {
        return $this->hasMany(AirplayDaily::class);
    }

    public function label(): string
    {
        return $this->artist ? $this->artist.' – '.$this->title : $this->title;
    }

    /**
     * Ίδιο τραγούδι, όπως κι αν γραφτεί: πεζά, χωρίς τόνους, χωρίς «(Radio Edit)», «feat.», σημεία στίξης.
     */
    public static function normalize(?string $artist, string $title): string
    {
        $clean = function (?string $s, bool $isArtist) {
            $s = mb_strtolower((string) $s);
            $s = class_exists(Normalizer::class) ? preg_replace('/\p{Mn}/u', '', Normalizer::normalize($s, Normalizer::FORM_D)) : $s;
            $s = preg_replace('/[\(\[][^\)\]]*[\)\]]/u', ' ', $s);              // (Radio Edit), [Official]
            if ($isArtist) {
                $s = preg_replace('/\s(feat\.?|ft\.?|featuring|&|και|x|vs\.?)\s.*$/u', '', $s); // κύριος καλλιτέχνης
            } else {
                $s = preg_replace('/\s(feat\.?|ft\.?)\s.*$/u', '', $s);
            }
            $s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);

            return trim(preg_replace('/\s+/u', ' ', $s));
        };

        return sha1($clean($artist, true).'|'.$clean($title, false));
    }
}
