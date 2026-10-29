<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Mail\WelcomeCoupon;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use App\Services\Coupons;
use App\Services\Quotes\QuoteService;
use Database\Seeders\StationSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class OffersCouponsTest extends TestCase
{
    use RefreshDatabase;

    protected Station $star;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed(StationSeeder::class);
        $this->star = Station::query()->orderBy('sort')->first();
        $this->star->update(['price_spot' => 5, 'is_bookable' => true]);
        $this->customer = User::factory()->create(['email_verified_at' => now()]);
        $this->customer->business()->save(tap(new \App\Models\Business([
            'legal_name' => 'Πελάτης ΟΕ', 'business_type' => 'food', 'vat' => '090000045', 'address' => 'Α', 'city' => 'Ξάνθη', 'postal_code' => '67100',
            'mobile' => '6941234567', 'contact_person' => 'Νίκος',
        ]), fn ($b) => [$b->terms_accepted_at = now(), $b->terms_version = '2026-10']));
    }

    protected function admin(): User
    {
        $a = User::factory()->create();
        $a->role = UserRole::Admin;
        $a->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $a->save();

        return $a;
    }

    protected function quote(int $spots = 4, ?User $user = null): Quote
    {
        // 4 × 10 ημέρες × 5 € = 200 € χωρίς ΦΠΑ
        return app(QuoteService::class)->create($user ?? $this->customer, 'Δοκιμή', 'manual',
            [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => $spots]], 10, null);
    }

    public function test_coupon_is_applied_before_vat_and_redeemed_at_checkout(): void
    {
        Coupon::query()->create(['code' => 'blackfriday', 'name' => 'Black Friday', 'type' => 'percent', 'value' => 15]);
        $q = $this->quote();
        $this->actingAs($this->customer);

        $this->post(route('quotes.coupon', $q), ['code' => 'nope'])->assertSessionHasErrors('code');
        $this->post(route('quotes.coupon', $q), ['code' => ' BlackFriday '])->assertSessionHasNoErrors();

        $q->refresh();
        $this->assertSame('170.00', $q->subtotal); // 200 − 15%
        $this->assertSame('40.80', $q->vat);       // ΦΠΑ στο ποσό μετά την έκπτωση
        $this->assertSame('210.80', $q->total);
        $this->get(route('quotes.checkout', $q))->assertSee('Κουπόνι BLACKFRIDAY (15%)');

        $this->post(route('quotes.submit', $q), ['accept' => '1'])->assertRedirect(route('orders.show', $q->refresh()->order));
        $this->assertDatabaseHas('coupon_redemptions', ['quote_id' => $q->id, 'amount' => 30]);

        // Το PDF δείχνει την έκπτωση
        $this->get(route('quotes.pdf', $q))->assertOk();
    }

    public function test_coupon_rules(): void
    {
        $other = User::factory()->create(['email_verified_at' => now()]);
        $svc = app(Coupons::class);
        $q = $this->quote();

        $expired = Coupon::query()->create(['code' => 'OLD', 'name' => 'x', 'type' => 'fixed', 'value' => 20, 'ends_at' => now()->subDay()]);
        $minimum = Coupon::query()->create(['code' => 'BIG', 'name' => 'x', 'type' => 'fixed', 'value' => 20, 'min_subtotal' => 500]);
        $personal = Coupon::query()->create(['code' => 'MINE', 'name' => 'x', 'type' => 'fixed', 'value' => 20, 'user_id' => $other->id]);
        foreach ([[$expired, 'έληξε'], [$minimum, 'από'], [$personal, 'λογαριασμό']] as [$c, $msg]) {
            try {
                $svc->apply($q, $c->code, $this->customer);
                $this->fail('Έπρεπε να απορριφθεί: '.$c->code);
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($msg, $e->getMessage());
            }
        }

        // Μία χρήση ανά πελάτη: μετά την εξαργύρωση δεν ξαναπερνά σε άλλη προσφορά
        $once = Coupon::query()->create(['code' => 'ONCE', 'name' => 'x', 'type' => 'fixed', 'value' => 50, 'max_discount' => null]);
        $svc->apply($q, 'ONCE', $this->customer);
        $this->assertSame('150.00', $q->refresh()->subtotal);
        app(QuoteService::class)->submit($q, null);
        $q2 = $this->quote();
        $this->expectException(InvalidArgumentException::class);
        $svc->apply($q2, 'ONCE', $this->customer);
    }

    public function test_deleting_an_unconfirmed_quote_releases_the_coupon(): void
    {
        Coupon::query()->create(['code' => 'ONCE', 'name' => 'x', 'type' => 'fixed', 'value' => 10]);
        $q = $this->quote();
        app(Coupons::class)->apply($q, 'ONCE', $this->customer);
        app(QuoteService::class)->submit($q, null);
        $this->assertSame(1, CouponRedemption::query()->count());

        app(QuoteService::class)->delete($q->refresh());
        $this->assertSame(0, CouponRedemption::query()->count());
    }

    public function test_coupons_skip_packages_unless_allowed(): void
    {
        $p = Package::query()->create(['slug' => 'p', 'name' => 'Πακέτο', 'price' => 300, 'is_active' => true]);
        $q = app(QuoteService::class)->create($this->customer, 'Π', 'package', [['type' => 'package', 'package_id' => $p->id]], 1, null);
        Coupon::query()->create(['code' => 'NOPKG', 'name' => 'x', 'type' => 'percent', 'value' => 10]);
        Coupon::query()->create(['code' => 'PKG', 'name' => 'x', 'type' => 'percent', 'value' => 10, 'applies_to_packages' => true]);

        try {
            app(Coupons::class)->apply($q, 'NOPKG', $this->customer);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('πακέτα', $e->getMessage());
        }
        app(Coupons::class)->apply($q, 'PKG', $this->customer);
        $this->assertSame('270.00', $q->refresh()->subtotal);
    }

    public function test_welcome_coupon_is_emailed_once_after_verification(): void
    {
        Setting::putMany(['welcome_coupon_enabled' => true, 'welcome_coupon_type' => 'percent', 'welcome_coupon_value' => 10, 'welcome_coupon_days' => 30]);
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->business()->save(tap(new \App\Models\Business([
            'legal_name' => 'Νέα ΟΕ', 'business_type' => 'food', 'vat' => '094014201', 'address' => 'Α', 'city' => 'Ξάνθη', 'postal_code' => '67100',
            'mobile' => '6941234567', 'contact_person' => 'Ελένη',
        ]), fn ($b) => [$b->terms_accepted_at = now(), $b->terms_version = '2026-10']));

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user)->get($url)->assertRedirect();

        $coupon = Coupon::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('welcome', $coupon->source);
        $this->assertStringStartsWith('KALOS-', $coupon->code);
        Mail::assertSent(WelcomeCoupon::class, fn ($m) => $m->hasTo($user->email));
        $this->get(route('account'))->assertSee($coupon->code);

        app(Coupons::class)->issueWelcome($user->refresh());
        $this->assertSame(1, Coupon::query()->where('user_id', $user->id)->count());
    }

    public function test_offers_panels_hide_prices_from_guests_and_slide_when_many(): void
    {
        HomeSection::query()->create(['type' => 'offers', 'title' => 'Προσφορές της εποχής', 'settings' => ['source' => 'offers'], 'is_active' => true, 'sort' => 1]);
        foreach (range(1, 4) as $i) {
            Package::query()->create(['slug' => 'o'.$i, 'name' => 'Προσφορά '.$i, 'price' => 100 * $i, 'compare_at_price' => 150 * $i, 'is_offer' => true,
                'available_until' => now()->addDays(5), 'includes' => ['5 μεταδόσεις την ημέρα'], 'is_active' => true]);
        }

        $this->get('/')->assertOk()->assertSee('Προσφορά 4')->assertSee('5 μεταδόσεις την ημέρα')->assertSee('Δείτε την τιμή')
            ->assertSee('Επόμενες προσφορές')->assertDontSee('400 €');
        $this->actingAs($this->customer)->get('/')->assertSee('400 €')->assertSee('600 €');
    }

    public function test_admin_builds_an_offer_from_media_with_auto_price(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $undo = Repeater::fake();
        Livewire::test(CreatePackage::class)->fillForm([
            'name' => 'Black Friday Combo', 'slug' => 'black-friday', 'duration_days' => 10, 'pricing' => 'auto', 'discount_percent' => 20,
            'builder' => ['radio' => [['station_id' => $this->star->id, 'spots_per_day' => 4, 'seconds' => 30, 'position' => 'standard', 'fixed_hour' => false, 'mentions_live' => 0, 'mentions_recorded' => 0]]],
            'is_offer' => true, 'available_until' => now()->addWeek(), 'is_active' => true, 'color' => '#c80c92',
        ])->call('create')->assertHasNoFormErrors();
        $undo();

        $p = Package::query()->firstOrFail();
        $this->assertSame('160.00', $p->price);          // 200 − 20%
        $this->assertSame('200.00', $p->compare_at_price);
        $this->assertSame('radio', $p->media_mix);
        $this->assertNotEmpty($p->includes);
        $this->assertSame($this->star->id, $p->stations()->first()->id);
    }

    public function test_admin_edits_saved_quote_and_customer_sees_it(): void
    {
        $q = $this->quote();
        $number = $q->number;
        $this->actingAs($this->admin(), 'admin');
        $undo = Repeater::fake();
        Livewire::test(EditQuote::class, ['record' => $q->ulid])
            ->assertFormSet(['days' => 10])
            ->fillForm(['radio' => [['station_id' => $this->star->id, 'spots_per_day' => 6, 'seconds' => 30, 'position' => 'standard', 'fixed_hour' => false, 'mentions_live' => 0, 'mentions_recorded' => 0]], 'discount' => 10])
            ->call('save')->assertHasNoFormErrors();
        $undo();

        $q->refresh();
        $this->assertSame($number, $q->number);
        $this->assertSame('270.00', $q->subtotal); // 6 × 10 × 5 = 300 − 10%
        $this->assertNotNull($q->updated_by_id);
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_edited', 'quote_id' => $q->id]);
        $this->actingAs($this->customer)->get(route('quotes.show', $q))->assertSee('334,80 €');
    }

    public function test_admin_coupon_pages(): void
    {
        $this->actingAs($this->admin(), 'admin');
        Livewire::test(CreateCoupon::class)
            ->fillForm(['code' => 'xmas-26', 'name' => 'Χριστούγεννα', 'type' => 'fixed', 'value' => 50, 'per_customer_limit' => 1, 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();
        $this->assertSame('XMAS-26', Coupon::query()->first()->code);
        Livewire::test(ListCoupons::class)->assertOk()->assertSee('XMAS-26');
    }

    public function test_customer_changes_own_saved_quote_keeping_number_and_staff_discount(): void
    {
        $admin = $this->admin();
        $q = app(QuoteService::class)->createForCustomer($admin, $this->customer, 'Από την ομάδα', [
            ['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 4], ['type' => 'discount', 'percent' => 10],
        ], 10, null, null);
        $number = $q->number;
        $this->actingAs($this->customer);

        $this->get(route('quotes.show', $q))->assertSee('Αλλαγές στην προσφορά');
        Livewire::withQueryParams(['allagi' => $q->ulid])->test(\App\Livewire\QuoteBuilder::class)
            ->assertSet('mode', 'manual')->assertSet("radio.{$this->star->id}.spots", 4)->assertSee($number)
            ->set("radio.{$this->star->id}.spots", 6)
            ->call('saveManual')->assertRedirect(route('quotes.show', $q));

        $q->refresh();
        $this->assertSame($number, $q->number);
        $this->assertSame(1, Quote::query()->count());
        $this->assertSame('270.00', $q->subtotal); // 6 × 10 × 5 = 300 − 10% της ομάδας
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_changed', 'quote_id' => $q->id]);

        // Ξένη ή σταλμένη προσφορά δεν ανοίγει για αλλαγές
        $q->forceFill(['status' => 'submitted'])->save();
        Livewire::withQueryParams(['allagi' => $q->ulid])->test(\App\Livewire\QuoteBuilder::class)->assertSet('editing', null);
    }

    public function test_header_shows_who_is_logged_in_with_logout(): void
    {
        $this->get('/')->assertSee('Σύνδεση')->assertDontSee('Αποσύνδεση');
        $this->actingAs($this->customer)->get('/')->assertSee('Συνδεδεμένος ως')->assertSee('Πελάτης ΟΕ')->assertSee('Αποσύνδεση')->assertSee('Οι προσφορές μου');
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest('web');
    }

    public function test_offers_can_be_fixed_grid_instead_of_slider(): void
    {
        HomeSection::query()->create(['type' => 'offers', 'settings' => ['source' => 'offers', 'many_layout' => 'grid'], 'is_active' => true, 'sort' => 1]);
        foreach (range(1, 5) as $i) {
            Package::query()->create(['slug' => 'g'.$i, 'name' => 'Πλέγμα '.$i, 'price' => 100, 'is_offer' => true, 'available_until' => now()->addDays(5), 'is_active' => true]);
        }
        $this->get('/')->assertOk()->assertSee('Πλέγμα 5')->assertDontSee('Επόμενες προσφορές')->assertSee('grid gap-5 sm:grid-cols-2 lg:grid-cols-3', false);
    }
}
