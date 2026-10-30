<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ειδήσεις από τη ροή RSS του thrakitoday.gr.
 * Ανανέωση κάθε 5 λεπτά· αν η ροή αργεί ή πέσει, δείχνουμε την τελευταία καλή εκδοχή.
 */
class NewsFeed
{
    public const TTL_SECONDS = 300;

    protected const CACHE_KEY = 'news:thrakitoday';

    protected const LAST_GOOD_KEY = 'news:thrakitoday:last-good';

    /**
     * @return list<array{title: string, url: string, date: ?string, category: ?string, excerpt: ?string, image: ?string}>
     */
    public function latest(int $limit = 12): array
    {
        $items = Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, function () {
            $fresh = $this->fetch();

            if ($fresh !== []) {
                Cache::forever(self::LAST_GOOD_KEY, $fresh);

                return $fresh;
            }

            return Cache::get(self::LAST_GOOD_KEY, []);
        });

        return array_slice($items, 0, $limit);
    }

    protected function feedUrl(): string
    {
        return (string) config('services.news.feed_url');
    }

    /**
     * @return list<array{title: string, url: string, date: ?string, category: ?string, excerpt: ?string, image: ?string}>
     */
    protected function fetch(): array
    {
        try {
            $response = Http::timeout(8)
                ->withUserAgent('OnAirMedia/1.0 (+https://onairmedia.gr)')
                ->accept('application/rss+xml, application/xml')
                ->get($this->feedUrl());

            if (! $response->successful()) {
                return [];
            }

            return $this->parse($response->body());
        } catch (Throwable $e) {
            Log::warning('News feed failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array{title: string, url: string, date: ?string, category: ?string, excerpt: ?string, image: ?string}>
     */
    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET: ποτέ εξωτερικές οντότητες (XXE)
        $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($doc === false || ! isset($doc->channel->item)) {
            return [];
        }

        $allowedHost = parse_url($this->feedUrl(), PHP_URL_HOST);
        $items = [];

        foreach ($doc->channel->item as $item) {
            $url = trim((string) $item->link);
            $title = trim(html_entity_decode(strip_tags((string) $item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($title === '' || ! $this->isSafeUrl($url, $allowedHost)) {
                continue;
            }

            $image = null;
            $media = $item->children('http://search.yahoo.com/mrss/');
            if (isset($media->content)) {
                $image = (string) $media->content->attributes()->url;
            } elseif (isset($item->enclosure)) {
                $image = (string) $item->enclosure->attributes()->url;
            }

            $excerpt = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

            $items[] = [
                'title' => $title,
                'url' => $url,
                'date' => $this->date((string) $item->pubDate),
                'category' => trim((string) $item->category) ?: null,
                'excerpt' => $excerpt !== '' ? Str::limit($excerpt, 180) : null,
                'image' => $image && $this->isSafeUrl($image, $allowedHost) ? $image : null,
            ];
        }

        return $items;
    }

    /** Μόνο https και μόνο από το domain της ροής: κανένα ξένο link ή script στη σελίδα μας. */
    protected function isSafeUrl(string $url, ?string $host): bool
    {
        $parts = parse_url($url);

        return ($parts['scheme'] ?? null) === 'https'
            && $host !== null
            && isset($parts['host'])
            && ($parts['host'] === $host || str_ends_with($parts['host'], '.'.$host));
    }

    protected function date(string $value): ?string
    {
        try {
            return $value !== '' ? CarbonImmutable::parse($value)->setTimezone(config('app.timezone'))->toIso8601String() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
