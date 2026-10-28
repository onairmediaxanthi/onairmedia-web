<?php

namespace Tests\Feature;

use App\Services\NewsFeed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsFeedTest extends TestCase
{
    protected function rss(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel><title>ThrakiToday</title><link>https://thrakitoday.gr</link>
<item>
  <title>Νέα γέφυρα στην Ξάνθη &amp; έργα</title>
  <link>https://thrakitoday.gr/2026/10/18_1.html</link>
  <pubDate>Wed, 28 Oct 2026 12:43:08 +0200</pubDate>
  <category>Ελλάδα</category>
  <description><![CDATA[<p>Ξεκινούν <b>σήμερα</b> τα έργα.</p><script>alert(1)</script>]]></description>
  <media:content url="https://thrakitoday.gr/storage/articles/a.jpg" medium="image"/>
</item>
<item>
  <title>Κακόβουλο</title>
  <link>javascript:alert(1)</link>
</item>
<item>
  <title>Ξένο link</title>
  <link>https://evil.example.com/x</link>
</item>
</channel></rss>
XML;
    }

    public function test_parses_items_and_drops_unsafe_links(): void
    {
        $items = app(NewsFeed::class)->parse($this->rss());

        $this->assertCount(1, $items);
        $this->assertSame('Νέα γέφυρα στην Ξάνθη & έργα', $items[0]['title']);
        $this->assertSame('https://thrakitoday.gr/storage/articles/a.jpg', $items[0]['image']);
        $this->assertSame('Ελλάδα', $items[0]['category']);
        $this->assertStringNotContainsString('<', $items[0]['excerpt']);
        $this->assertStringStartsWith('2026-10-28T12:43:08', $items[0]['date']);
    }

    public function test_keeps_last_good_copy_when_feed_fails(): void
    {
        Cache::flush();
        Http::fake(['thrakitoday.gr/*' => Http::sequence()->push($this->rss())->push('', 500)]);

        $this->assertCount(1, app(NewsFeed::class)->latest());

        Cache::forget('news:thrakitoday');
        $this->assertCount(1, app(NewsFeed::class)->latest());
    }
}
