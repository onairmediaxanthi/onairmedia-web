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
     * Ίδιο τραγούδι, όπως κι αν γραφτεί: πεζά, χωρίς τόνους, χωρίς «(Radio Edit)», «feat.», σημεία στίξης,
     * και ελληνικά ή greeklish να καταλήγουν στο ίδιο (πολλοί σταθμοί στέλνουν «Nikos Oikonomopoulos»).
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

            return self::phonetic(trim(preg_replace('/\s+/u', ' ', $s)));
        };

        return sha1($clean($artist, true).'|'.$clean($title, false));
    }

    /** «Οικονομόπουλος» και «Oikonomopoulos» → «ikonomopulos» (το ίδιο κλειδί) */
    public static function phonetic(string $s): string
    {
        // Ελληνικά → λατινικά (πρώτα τα δίψηφα)
        $s = strtr($s, [
            'ου' => 'u', 'οι' => 'i', 'ει' => 'i', 'υι' => 'i', 'αι' => 'e', 'αυ' => 'av', 'ευ' => 'ev',
            'μπ' => 'b', 'ντ' => 'd', 'γκ' => 'g', 'γγ' => 'g', 'τζ' => 'tz', 'τσ' => 'ts',
        ]);
        $s = strtr($s, [
            'α' => 'a', 'β' => 'v', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'i', 'θ' => 'th', 'ι' => 'i',
            'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'σ' => 's',
            'ς' => 's', 'τ' => 't', 'υ' => 'i', 'φ' => 'f', 'χ' => 'h', 'ψ' => 'ps', 'ω' => 'o',
        ]);
        // Greeklish → ίδια μορφή
        $s = strtr($s, [
            'ou' => 'u', 'oi' => 'i', 'ei' => 'i', 'ai' => 'e', 'af' => 'av', 'ef' => 'ev',
            'mp' => 'b', 'nt' => 'd', 'gk' => 'g', 'gg' => 'g', 'ch' => 'x', 'kh' => 'x', 'ks' => 'x', 'ph' => 'f',
            'y' => 'i', 'w' => 'o', 'h' => 'x', // χ γράφεται x / h / ch, ξ γράφεται x / ks: ένα κλειδί
        ]);

        return preg_replace('/([a-z])\1+/', '$1', $s); // διπλά γράμματα: λλ, νν, ss…
    }

    /** Ξαναϋπολογίζει τα κλειδιά (μετά από αλλαγή κανόνων) και ενώνει όσα τραγούδια βγαίνουν ίδια */
    public static function renormalizeAll(): int
    {
        $merged = 0;
        $seen = [];
        foreach (static::query()->orderBy('id')->get() as $song) {
            $norm = static::normalize($song->artist, $song->title);
            if (isset($seen[$norm])) {
                \App\Filament\Resources\Songs\SongResource::merge($song, static::query()->find($seen[$norm]));
                $merged++;

                continue;
            }
            $seen[$norm] = $song->id;
            if ($song->norm !== $norm) {
                static::query()->whereKey($song->id)->update(['norm' => 'tmp-'.substr($norm, 0, 36)]); // αποφυγή σύγκρουσης unique
                $song->norm = $norm;
            }
        }
        foreach ($seen as $norm => $id) {
            static::query()->whereKey($id)->update(['norm' => $norm]);
        }

        return $merged;
    }
}
