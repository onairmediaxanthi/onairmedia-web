<?php

namespace Tests\Feature;

use App\Models\Slide;
use App\Models\Station;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SlideSeeder;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.preview_password' => null]);
        Http::fake(['*' => Http::response('', 200)]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_home_shows_slider_dial_and_stations(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Στον αέρα τώρα')
            ->assertSee('Star 88.8')
            ->assertSee('Διαφημιστείτε εκεί που σας ακούνε')
            ->assertSee('stations-data', false);
    }

    public function test_expired_slides_are_hidden(): void
    {
        Slide::query()->update(['ends_at' => now()->subDay()]);

        $this->get('/')->assertOk()->assertDontSee('Διαφημιστείτε εκεί που σας ακούνε');
    }

    public function test_unsafe_slide_link_is_not_rendered(): void
    {
        Slide::query()->update(['cta_url' => 'javascript:alert(1)']);

        $this->get('/')->assertOk()->assertDontSee('javascript:alert(1)', false);
    }

    public function test_station_page_has_structured_data_and_sanitized_profile(): void
    {
        Station::where('slug', 'star-888')->update(['description' => '<p>Γεια</p><script>alert(1)</script><a href="javascript:x">κλικ</a>']);

        $this->get('/radio/star-888')
            ->assertOk()
            ->assertSee('"@type":"RadioStation"', false)
            ->assertSee('<p>Γεια</p>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('javascript:x', false);
    }

    public function test_inactive_station_returns_404(): void
    {
        Station::where('slug', 'star-888')->update(['is_active' => false]);

        $this->get('/radio/star-888')->assertNotFound();
    }
}
