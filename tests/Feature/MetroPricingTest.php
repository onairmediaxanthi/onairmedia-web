<?php

namespace Tests\Feature;

use App\Models\AdSlot;
use App\Models\Business;
use App\Models\Package;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use App\Services\Quotes\PriceBook;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteService;
use Database\Seeders\AdSlotSeeder;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MetroPricingTest extends TestCase
{
    use RefreshDatabase;

    protected Station $star;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StationSeeder::class, AdSlotSeeder::class]);
        $this->star = Station::query()->orderBy('sort')->first();
        $this->star->update(['price_spot' => 5, 'spot_seconds' => 30, 'metro_price_second' => 0.15, 'pct_first_position' => 20, 'is_bookable' => true]);
        AdSlot::query()->where('code', 'header')->update(['price_per_day' => 10, 'metro_price_per_day' => 25]);
        Setting::putMany(['metro_cities' => ['Αθήνα', 'Θεσσαλονίκη', 'Athens']]);
    }

    protected function customer(string $city, string $vat): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $b = new Business(['legal_name' => 'Π '.$city, 'business_type' => 'food', 'vat' => $vat, 'address' => 'Α', 'city' => $city, 'postal_code' => '10000',
            'mobile' => '6941234567', 'contact_person' => 'Χ']);
        $b->terms_accepted_at = now();
        $b->terms_version = '2026-10';
        $u->business()->save($b);

        return $u->load('business');
    }

    public function test_city_decides_the_price_list(): void
    {
        foreach (['Αθήνα', 'ΑΘΗΝΑ', 'αθηνα ', 'Athens', 'Θεσσαλονίκη'] as $c) {
            $this->assertTrue(PriceBook::isMetroCity($c), $c);
        }
        $this->assertFalse(PriceBook::isMetroCity('Ξάνθη'));
        $this->assertSame('metro', PriceBook::tierFor($this->customer('Αθηνα', '094014201')));
        $this->assertSame('local', PriceBook::tierFor($this->customer('Ξάνθη', '090000045')));
        $this->assertSame('local', PriceBook::tierFor(null));
    }

    public function test_metro_customer_pays_per_second_and_metro_prices(): void
    {
        $athens = $this->customer('Αθήνα', '094014201');
        $xanthi = $this->customer('Ξάνθη', '090000045');
        $slot = AdSlot::query()->where('code', 'header')->first();
        $specs = [
            ['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2, 'seconds' => 20, 'position' => 'first'],
            ['type' => 'banner', 'slot_id' => $slot->id],
        ];

        $qa = app(QuoteService::class)->create($athens, 'Α', 'manual', $specs, 5, null);
        // 20″ × 0,15 € = 3 € +20% = 3,60 € × 10 μεταδόσεις = 36 € · banner 25 € × 5 = 125 €
        $this->assertSame('metro', $qa->price_tier);
        $this->assertEquals([36, 125], array_column($qa->items, 'amount'));

        $qx = app(QuoteService::class)->create($xanthi, 'Ξ', 'manual', $specs, 5, null);
        // βασικός: 5 € (20″ < 30″) +20% = 6 € × 10 = 60 € · banner 10 € × 5 = 50 €
        $this->assertEquals([60, 50], array_column($qx->items, 'amount'));

        // Ανανέωση: μένει στον τιμοκατάλογο της προσφοράς
        $this->assertSame('161.00', app(QuoteService::class)->renew($qa)->subtotal);
    }

    public function test_metro_customer_sees_only_metro_prices_and_packages(): void
    {
        $athens = $this->customer('Αθήνα', '094014201');
        $other = Station::query()->orderBy('sort')->skip(1)->first();
        $other->update(['price_spot' => 7, 'is_bookable' => true]); // μόνο βασική τιμή
        Package::query()->create(['slug' => 'metro', 'name' => 'Αθηναϊκό πακέτο', 'price' => 900, 'price_tier' => 'metro', 'is_active' => true]);
        Package::query()->create(['slug' => 'local', 'name' => 'Ξανθιώτικο πακέτο', 'price' => 300, 'price_tier' => 'local', 'is_active' => true]);
        $this->actingAs($athens);

        $this->get(route('stations.show', $this->star))->assertSee('ανά δευτερόλεπτο')->assertSee('0,1500 €')->assertDontSee('5,00 €');
        Livewire::test(\App\Livewire\QuoteBuilder::class)->set('mode', 'manual')
            ->assertSee('0,1500 € / δευτ.')->assertDontSee($other->name)->assertSee('25,00 € / ημέρα')->assertDontSee('10,00 € / ημέρα');
        Livewire::test(\App\Livewire\QuoteBuilder::class)->set('mode', 'packages')
            ->assertSee('Αθηναϊκό πακέτο')->assertDontSee('Ξανθιώτικο πακέτο');
    }

    public function test_staff_discount_limit_comes_from_settings(): void
    {
        Setting::putMany(['max_staff_discount' => 15]);
        $p = (new QuoteCalculator(staff: true))->price([
            ['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2], ['type' => 'discount', 'percent' => 40],
        ], 10);
        $this->assertSame('Έκπτωση 15%', collect($p['lines'])->last()['label']);
        $this->assertSame(85.0, $p['subtotal']);
    }
}
