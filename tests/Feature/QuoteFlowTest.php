<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\QuoteSettings;
use App\Filament\Resources\CustomerActivities\Pages\ListCustomerActivities;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Livewire\QuoteBuilder;
use App\Mail\CompanyAlert;
use App\Mail\QuoteReminder;
use App\Mail\QuoteSubmitted;
use App\Models\AdSlot;
use App\Models\Business;
use App\Models\CustomerActivity;
use App\Models\Quote;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteService;
use App\Services\Quotes\Recommender;
use Database\Seeders\AdSlotSeeder;
use Database\Seeders\HomeSectionSeeder;
use Database\Seeders\ScreenSeeder;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class QuoteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Station $star;

    protected Station $other;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed([StationSeeder::class, AdSlotSeeder::class, ScreenSeeder::class]);
        Setting::putMany(['email' => 'sales@example.com', 'price_spot_production' => 50]);

        [$this->star, $this->other] = Station::query()->orderBy('sort')->take(2)->get()->all();
        $this->star->update(['price_spot' => 5, 'spot_seconds' => 30, 'pct_sandwich' => 50, 'price_mention_live' => 6, 'is_bookable' => true,
            'audience' => ['ages' => ['25-34', '35-44'], 'profiles' => ['drivers']]]);
        $this->other->update(['price_spot' => 4, 'is_bookable' => true, 'audience' => ['ages' => ['55-64', '65+']]]);
        AdSlot::query()->where('code', 'sidebar-top')->update(['price_per_day' => 10, 'min_days' => 7]);
        Screen::query()->first()->update(['price_per_day' => 20]);
    }

    protected function customer(bool $verified = true): User
    {
        static $n = 0;
        $n++;
        $user = User::factory()->create(['email_verified_at' => $verified ? now() : null]);
        $b = new Business([
            'legal_name' => 'Πελάτης '.$n.' ΟΕ', 'business_type' => 'food', 'vat' => self::vat(10000000 + $n),
            'address' => 'Οδός 1', 'city' => 'Ξάνθη', 'postal_code' => '67100', 'mobile' => '6941234567', 'contact_person' => 'Γιάννης',
        ]);
        $b->terms_accepted_at = now();
        $b->terms_version = Business::TERMS_VERSION;
        $user->business()->save($b);

        return $user;
    }

    /** Έγκυρος ελληνικός ΑΦΜ από 8 ψηφία (modulo 11) */
    protected static function vat(int $base): string
    {
        $d = str_pad((string) $base, 8, '0', STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($d) as $i => $c) {
            $sum += (int) $c * (2 ** (8 - $i));
        }

        return $d.(($sum % 11) % 10);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $admin->save();

        return $admin;
    }

    public function test_calculator_prices_positions_mentions_and_min_days(): void
    {
        $slot = AdSlot::query()->where('code', 'sidebar-top')->first();
        $out = (new QuoteCalculator)->price([
            ['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 4, 'seconds' => 30, 'position' => 'sandwich', 'mentions_live' => 2],
            ['type' => 'banner', 'slot_id' => $slot->id],
            ['type' => 'radio', 'station_id' => Station::query()->where('is_bookable', false)->first()->id, 'spots_per_day' => 3],
        ], 5);

        // 4 × 5 ημέρες × (5 € +50%) = 150 · αναφορές 2 × 5 × 6 = 60 · banner 7 ημέρες (ελάχιστο) × 10 = 70
        $this->assertSame([150.0, 60.0, 70.0], array_column($out['lines'], 'amount'));
        $this->assertSame(280.0, $out['subtotal']);
        $this->assertSame(67.2, $out['vat']);
        $this->assertSame(347.2, $out['total']);
        $this->assertCount(2, $out['warnings']);

        // Μεγαλύτερο σποτ χρεώνεται αναλογικά
        $this->assertSame(7.5, QuoteCalculator::spotUnit($this->star, 45));
    }

    public function test_recommender_stays_within_budget_and_ranks_by_audience(): void
    {
        $out = (new Recommender)->recommend(['budget' => 1240, 'days' => 10, 'goal' => 'awareness', 'ages' => ['25-34'], 'profiles' => ['drivers'],
            'media' => ['radio', 'news', 'led'], 'material' => 'need']);

        $this->assertNotEmpty($out['proposals']);
        foreach ($out['proposals'] as $p) {
            $this->assertLessThanOrEqual(1240, $p['priced']['total']);
            $this->assertGreaterThan(0, $p['priced']['subtotal']);
        }
        $focus = collect($out['proposals'])->firstWhere('key', 'focus');
        $radio = collect($focus['specs'])->where('type', 'radio');
        $this->assertCount(1, $radio);
        $this->assertSame($this->star->id, $radio->first()['station_id']);
        $this->assertTrue(collect($focus['specs'])->contains(fn ($s) => $s['type'] === 'creative'));
        $this->assertNotEmpty($focus['reasons']);
    }

    public function test_recommender_explains_when_budget_is_too_small(): void
    {
        $out = (new Recommender)->recommend(['budget' => 50, 'days' => 30, 'goal' => 'event', 'media' => ['radio']]);

        $this->assertSame([], $out['proposals']);
        $this->assertGreaterThan(50, $out['min_budget']);
    }

    public function test_guest_answers_are_kept_through_registration(): void
    {
        Livewire::test(QuoteBuilder::class)
            ->call('choose', 'wizard')
            ->set('goal', 'offer')->call('next')
            ->set('budget', 800)->set('days', 14)->call('next')
            ->set('ages', ['25-34'])->call('next')
            ->call('next')
            ->assertRedirect(route('register'));

        $this->assertSame(800.0, session(QuoteBuilder::SESSION_KEY)['budget']);
        $this->assertSame(route('quote.builder'), session('url.intended'));

        // Μετά την εγγραφή/επιβεβαίωση βλέπει κατευθείαν τις προτάσεις
        $this->actingAs($this->customer());
        Livewire::test(QuoteBuilder::class)
            ->assertSet('showResults', true)->assertSet('goal', 'offer')->assertSet('budget', 800.0)
            ->assertSee('Οι προτάσεις μας για εσάς');
    }

    public function test_customer_saves_a_proposal_and_company_is_notified(): void
    {
        $user = $this->customer();
        $this->admin();
        $this->actingAs($user);

        $c = Livewire::test(QuoteBuilder::class)
            ->set('mode', 'wizard')->set('budget', 1000)->set('days', 10)->set('ages', ['25-34'])
            ->call('results')->assertSet('showResults', true);

        $c->call('saveProposal', 0);
        $quote = Quote::query()->firstOrFail();
        $c->assertRedirect(route('quotes.show', $quote));

        $this->assertSame('wizard', $quote->source);
        $this->assertMatchesRegularExpression('/^ΠΡ-\d{4}-0001$/u', $quote->number);
        $this->assertSame(today()->addDays(15)->toDateString(), $quote->valid_until->toDateString());
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_saved', 'quote_id' => $quote->id]);
        Mail::assertSent(CompanyAlert::class, fn ($m) => $m->hasTo('sales@example.com') && $m->type === 'quote_saved');
        $this->assertSame(1, \Illuminate\Notifications\DatabaseNotification::query()->count());
    }

    public function test_manual_mode_prices_live_and_saves(): void
    {
        $this->actingAs($this->customer());

        Livewire::test(QuoteBuilder::class)
            ->set('mode', 'manual')->set('days', 10)
            ->set("radio.{$this->star->id}.on", true)->set("radio.{$this->star->id}.spots", 3)
            ->assertSee('186,00 €') // 3 × 10 × 5 € = 150 + 24% ΦΠΑ
            ->set('title', 'Εκπτώσεις')
            ->call('saveManual');

        $quote = Quote::query()->firstOrFail();
        $this->assertSame('Εκπτώσεις', $quote->title);
        $this->assertSame('150.00', $quote->subtotal);
        $this->assertSame('186.00', $quote->total);
    }

    public function test_tampered_manual_input_is_rejected(): void
    {
        $this->actingAs($this->customer());

        Livewire::test(QuoteBuilder::class)
            ->set('mode', 'manual')
            ->set("radio.{$this->star->id}.on", true)->set("radio.{$this->star->id}.spots", 999)
            ->call('saveManual')->assertHasErrors("radio.{$this->star->id}.spots");

        $this->assertSame(0, Quote::query()->count());
    }

    public function test_unverified_customer_cannot_see_prices_or_save(): void
    {
        $this->actingAs($this->customer(false));

        Livewire::test(QuoteBuilder::class)
            ->set('mode', 'manual')->set("radio.{$this->star->id}.on", true)
            ->assertDontSee('5,00 €')
            ->call('saveManual');

        $this->assertSame(0, Quote::query()->count());
    }

    public function test_quote_pages_are_private_pdf_delete_and_checkout(): void
    {
        $owner = $this->customer();
        $quote = app(QuoteService::class)->create($owner, 'Δοκιμή', 'manual',
            [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2]], 7, today()->addDay()->toDateString());

        $this->actingAs($this->customer())->get(route('quotes.show', $quote))->assertNotFound();
        $this->actingAs($this->customer())->get(route('quotes.pdf', $quote))->assertNotFound();

        $this->actingAs($owner)->get(route('quotes.show', $quote))->assertOk()->assertSee($quote->number)->assertSee('Προχώρηση στο ταμείο');
        $this->get(route('account'))->assertSee('Δοκιμή');

        $pdf = $this->get(route('quotes.pdf', $quote));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_pdf']);

        $this->post(route('quotes.submit', $quote), [])->assertSessionHasErrors('accept');
        $this->post(route('quotes.submit', $quote), ['accept' => '1', 'notes' => 'Πρωινές ώρες'])->assertRedirect(route('quotes.show', $quote));
        $this->assertSame('submitted', $quote->refresh()->status);
        Mail::assertSent(QuoteSubmitted::class, fn ($m) => $m->hasTo($owner->email));
        Mail::assertSent(CompanyAlert::class, fn ($m) => $m->type === 'quote_submitted');

        $this->delete(route('quotes.destroy', $quote))->assertRedirect(route('quotes.index'));
        $this->assertSoftDeleted($quote);
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_deleted', 'quote_id' => $quote->id]);
    }

    public function test_reminders_follow_admin_settings(): void
    {
        Setting::putMany(['quote_reminder_days' => 3, 'quote_reminder_max' => 2]);
        $user = $this->customer();
        $service = app(QuoteService::class);
        $spec = [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2]];

        $this->travel(-4)->days();
        $due = $service->create($user, 'Παλιά', 'manual', $spec, 5, null);
        $off = $service->create($user, 'Χωρίς', 'manual', $spec, 5, null);
        $off->forceFill(['reminders_off' => true])->save();
        $this->travelBack();
        $fresh = $service->create($user, 'Νέα', 'manual', $spec, 5, null);

        $this->artisan('quotes:remind')->assertSuccessful();
        Mail::assertSent(QuoteReminder::class, 1);
        Mail::assertSent(QuoteReminder::class, fn ($m) => $m->quote->is($due));
        $this->assertSame(1, $due->refresh()->reminders_sent);

        // Όχι ξανά πριν περάσουν 3 ημέρες
        $this->artisan('quotes:remind');
        Mail::assertSent(QuoteReminder::class, 1);

        // Λήξη
        $this->travel(20)->days();
        $this->artisan('quotes:remind');
        $this->assertSame('expired', $fresh->refresh()->status);
    }

    public function test_reminder_stop_link_works_without_login(): void
    {
        $quote = app(QuoteService::class)->create($this->customer(), 'Χ', 'manual',
            [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2]], 5, null);
        $url = URL::signedRoute('quotes.reminders-off', $quote);

        $this->get(route('quotes.reminders-off', $quote))->assertForbidden();
        $this->get($url)->assertOk()->assertSee('Ναι, σταματήστε τις');
        $this->post($url)->assertRedirect();
        $this->assertTrue($quote->refresh()->reminders_off);
    }

    public function test_homepage_has_quote_button_and_registration_is_logged(): void
    {
        $this->seed(HomeSectionSeeder::class);
        $this->get('/')->assertOk()->assertSee('Βγάλτε προσφορά σε 2 λεπτά')->assertSee(route('quote.builder'))->assertSee('Δωρεάν εγγραφή επιχείρησης');
        $this->get(route('quote.builder'))->assertOk()->assertSee('Βοηθήστε με να διαλέξω');
    }

    public function test_admin_sees_quotes_activity_and_settings(): void
    {
        $quote = app(QuoteService::class)->create($this->customer(), 'Για admin', 'manual',
            [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2]], 5, null);
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(ListQuotes::class)->assertOk()->assertCanSeeTableRecords([$quote]);
        Livewire::test(ViewQuote::class, ['record' => $quote->id])->assertOk()->assertSee('Για admin')
            ->callAction('update', ['status' => 'ordered', 'admin_notes' => 'Πληρώθηκε'])->assertHasNoActionErrors();
        $this->assertSame('ordered', $quote->refresh()->status);
        Livewire::test(ListCustomerActivities::class)->assertOk()->assertCanSeeTableRecords(CustomerActivity::all());

        Livewire::test(QuoteSettings::class)->assertOk()
            ->fillForm(['quote_reminder_days' => 5, 'notify_emails' => 'not-an-email'])->call('save')->assertHasFormErrors(['notify_emails']);
        Livewire::test(QuoteSettings::class)
            ->fillForm(['quote_reminder_days' => 5, 'notify_emails' => 'a@example.com, b@example.com'])->call('save')->assertHasNoFormErrors();
        $this->assertEquals(5, Setting::get('quote_reminder_days'));
    }
}
