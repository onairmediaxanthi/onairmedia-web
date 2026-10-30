<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Υλικό καμπάνιας: αρχεία που ανεβάζει ο πελάτης (ή η ομάδα, π.χ. το τελικό σποτ για έγκριση) */
class OrderFile extends Model
{
    protected $fillable = ['path', 'name', 'size', 'from', 'user_id', 'slot', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'purged_at' => 'datetime'];
    }

    public function isPurged(): bool
    {
        return $this->purged_at !== null;
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return array_values((array) ($this->meta['warnings'] ?? []));
    }

    /** Σύντομη τεχνική περιγραφή: «MP3 · 320 kbps · 30,2″» ή «PNG · 728×90 px» */
    public function techLabel(): string
    {
        $m = (array) $this->meta;
        $parts = [strtoupper($this->extension())];
        if (! empty($m['kbps'])) {
            $parts[] = $m['kbps'].' kbps';
        }
        if (! empty($m['sample_rate'])) {
            $parts[] = number_format($m['sample_rate'] / 1000, 1, ',', '').' kHz';
        }
        if (! empty($m['channels'])) {
            $parts[] = $m['channels'] >= 2 ? 'stereo' : 'mono';
        }
        if (! empty($m['seconds'])) {
            $parts[] = number_format((float) $m['seconds'], 1, ',', '').'″';
        }
        if (! empty($m['width'])) {
            $parts[] = $m['width'].'×'.$m['height'].' px';
        }
        $parts[] = $this->sizeLabel();

        return implode(' · ', $parts);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sizeLabel(): string
    {
        $b = (int) $this->size;

        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.').' MB' : max(1, (int) round($b / 1024)).' KB';
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }
}
