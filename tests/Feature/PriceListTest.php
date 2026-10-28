<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ExtraCharges;
use App\Filament\Resources\AdSlots\Pages\ManageAdSlots;
use App\Filament\Resources\RadioPrices\Pages\EditRadioPrice;
use App\Filament\Resources\RadioPrices\Pages\ListRadioPrices;
use App\Filament\Resources\Screens\Pages\ManageScreens;
use App\Filament\Resources\Stations\Pages\EditStation;
use App\Models\AdSlot;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\AdSlotSeeder;
use Database\Seeders\ScreenSeeder;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PriceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([StationSeeder::class, AdSlotSeeder::class, ScreenSeeder::class]);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $admin->save();

        return $admin;
    }

    public function test_price_list_pages_render(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListRadioPrices::class)->assertOk()->assertCanSeeTableRecords(Station::all());
        Livewire::test(ManageAdSlots::class)->assertOk()->assertCanSeeTableRecords(AdSlot::all());
        Livewire::test(ManageScreens::class)->assertOk()->assertCanSeeTableRecords(Screen::all());
        Livewire::test(ExtraCharges::class)->assertOk();
    }

    public function test_radio_prices_save_and_validate(): void
    {
        $this->actingAs($this->admin());
        $station = Station::query()->first();

        Livewire::test(EditRadioPrice::class, ['record' => $station->getRouteKey()])
            ->fillForm(['price_spot' => 4.5, 'price_mention_live' => 6, 'pct_first_position' => 20, 'pct_sandwich' => 50, 'is_bookable' => true])
            ->call('save')->assertHasNoFormErrors();

        $station->refresh();
        $this->assertSame('4.50', $station->price_spot);
        $this->assertSame('50.00', $station->pct_sandwich);
        $this->assertTrue($station->is_bookable);

        Livewire::test(EditRadioPrice::class, ['record' => $station->getRouteKey()])
            ->fillForm(['price_spot' => -1])->call('save')->assertHasFormErrors(['price_spot']);
    }

    public function test_banner_price_inline_edit(): void
    {
        $this->actingAs($this->admin());
        $slot = AdSlot::query()->where('code', 'sidebar-top')->first();

        Livewire::test(ManageAdSlots::class)->call('updateTableColumnState', 'price_per_day', (string) $slot->getKey(), '12.5');

        $this->assertSame('12.50', $slot->refresh()->price_per_day);
    }

    public function test_extra_charges_save_to_settings(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ExtraCharges::class)
            ->fillForm(['price_sponsored_article' => 40, 'vat_rate' => 24])
            ->call('save')->assertHasNoFormErrors();

        $this->assertEquals(40, Setting::get('price_sponsored_article'));
    }

    public function test_audience_saves_from_station_card_and_shows_on_page(): void
    {
        $this->actingAs($this->admin());
        $station = Station::query()->first();

        Livewire::test(EditStation::class, ['record' => $station->getRouteKey()])
            ->fillForm(['audience' => ['ages' => ['25-34', '35-44'], 'gender' => 'female', 'profiles' => ['drivers']]])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame(['25-34', '35-44'], $station->refresh()->audience['ages']);

        auth()->logout();
        $this->get(route('stations.show', $station))
            ->assertOk()->assertSee('Ποιοι ακούνε')->assertSee('25–34')->assertSee('Κυρίως γυναίκες')
            ->assertSee('Δωρεάν εγγραφή για να δείτε τιμές');
    }

    public function test_station_prices_only_for_verified_customers(): void
    {
        $station = Station::query()->first();
        $station->update(['price_spot' => 7.25]);

        $this->get(route('stations.show', $station))->assertDontSee('7,25 €');

        $this->actingAs(User::factory()->create(['email_verified_at' => now()]));
        $this->get(route('stations.show', $station))->assertSee('7,25 €');
    }
}
