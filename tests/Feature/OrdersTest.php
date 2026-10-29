<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Mail\OrderUpdated;
use App\Models\Business;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use App\Services\Orders\OrderService;
use App\Services\Quotes\QuoteService;
use App\Support\BankAccounts;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('local');
        $this->seed(StationSeeder::class);
        Station::query()->orderBy('sort')->first()->update(['price_spot' => 5, 'is_bookable' => true]);
        Setting::putMany(['bank_accounts' => [['bank' => 'Εθνική Τράπεζα', 'iban' => 'GR1601101250000000012300695', 'beneficiary' => null]]]);
        $this->customer = $this->makeCustomer('090000045');
    }

    protected function makeCustomer(string $vat): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->business()->save(tap(new Business([
            'legal_name' => 'Πελάτης '.$vat, 'business_type' => 'food', 'vat' => $vat, 'address' => 'Α', 'city' => 'Ξάνθη', 'postal_code' => '67100',
            'mobile' => '6941234567', 'contact_person' => 'Νίκος',
        ]), fn ($b) => [$b->terms_accepted_at = now(), $b->terms_version = '2026-10']));

        return $u;
    }

    protected function staff(UserRole $role = UserRole::Sales): User
    {
        $u = User::factory()->create();
        $u->role = $role;
        $u->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $u->save();

        return $u;
    }

    protected function order(string $method = 'bank'): Order
    {
        $q = app(QuoteService::class)->create($this->customer, 'Καμπάνια', 'manual',
            [['type' => 'radio', 'station_id' => Station::query()->orderBy('sort')->value('id'), 'spots_per_day' => 4]], 10, null);
        app(QuoteService::class)->submit($q, null, $method);

        return $q->refresh()->order;
    }

    public function test_checkout_creates_order_with_payment_method(): void
    {
        $q = app(QuoteService::class)->create($this->customer, 'Καμπάνια', 'manual',
            [['type' => 'radio', 'station_id' => Station::query()->orderBy('sort')->value('id'), 'spots_per_day' => 4]], 10, null);
        $this->actingAs($this->customer);

        $this->get(route('quotes.checkout', $q))->assertSee('Τραπεζική κατάθεση')->assertDontSee('Με πίστωση');
        $this->post(route('quotes.submit', $q), ['accept' => '1', 'payment_method' => 'credit'])->assertSessionHasErrors('payment_method');

        $this->post(route('quotes.submit', $q), ['accept' => '1', 'payment_method' => 'office'])->assertRedirect();
        $order = $q->refresh()->order;
        $this->assertSame('received', $order->stage);
        $this->assertSame('office', $order->payment_method);
        $this->assertSame($q->total, $order->total);
        $this->assertStringStartsWith('ΠΑΡ-', $order->number);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Λάβαμε την παραγγελία')->assertSee($order->number);

        // Πίστωση μόνο όταν την ενεργοποιήσει η ομάδα στον πελάτη
        $this->customer->business->forceFill(['credit_allowed' => true])->save();
        $this->assertContains('credit', OrderService::methodsFor($this->customer->refresh()));
    }

    public function test_full_bank_flow_with_customer_emails(): void
    {
        $order = $this->order();
        $staff = $this->staff();
        $svc = app(OrderService::class);

        $svc->confirm($order, $staff, 'Θα ηχογραφήσουμε εμείς το σποτ.');
        $this->assertSame('awaiting_payment', $order->stage);
        $this->assertSame('ordered', $order->quote->status);
        $this->assertNotNull($order->payment_due_on);
        Mail::assertSent(OrderUpdated::class, fn ($m) => $m->hasTo($this->customer->email) && str_contains($m->render(), 'GR16 0110 1250 0000 0001 2300 695') && str_contains($m->render(), $order->number));

        // Ο πελάτης βλέπει IBAN και αιτιολογία, δηλώνει κατάθεση με αποδεικτικό
        $this->actingAs($this->customer);
        $this->get(route('orders.show', $order))->assertSee('GR16 0110 1250 0000 0001 2300 695')->assertSee('Έκανα την κατάθεση');
        $this->post(route('orders.deposit', $order), ['note' => 'Από Πειραιώς', 'receipt' => UploadedFile::fake()->create('apodeixi.pdf', 120, 'application/pdf')])
            ->assertSessionHasNoErrors();
        $order->refresh();
        $this->assertSame('payment_review', $order->stage);
        Storage::disk('local')->assertExists($order->receipt_path);
        $this->assertSame(1, $staff->notifications()->count());

        // Δεν βρέθηκε → πίσω· μετά βρέθηκε → εξόφληση, και αφού δεν έχουμε υλικό, ζητάμε υλικό
        $svc->rejectDeposit($order, $staff, 'Δεν τη βλέπουμε ακόμα.');
        $this->assertSame('awaiting_payment', $order->stage);
        $svc->confirmPayment($order, $staff);
        $this->assertTrue($order->isPaid());
        $this->assertSame('materials', $order->stage);

        // Ο πελάτης ανεβάζει υλικό και ολοκληρώνει
        $this->get(route('orders.show', $order->refresh()))->assertSee('Περιμένουμε από εσάς')->assertSee('Έστειλα όλο το υλικό');
        $this->post(route('orders.materials-done', $order))->assertSessionHas('error'); // χωρίς αρχεία
        $this->post(route('orders.upload', $order), ['files' => [UploadedFile::fake()->create('spot.mp3', 900, 'audio/mpeg'), UploadedFile::fake()->image('logo.png')]])
            ->assertSessionHasNoErrors();
        $this->post(route('orders.upload', $order), ['files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors();
        $this->assertSame(2, $order->files()->count());
        $file = $order->files()->first();
        $this->get(route('orders.file', [$order, $file]))->assertOk();
        $this->post(route('orders.materials-done', $order))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('production', $order->refresh()->stage);

        // Προγραμματισμός και αυτόματη έναρξη / λήξη
        $svc->schedule($order, $staff, today()->addDays(2), today()->addDays(5));
        $this->assertSame('scheduled', $order->stage);
        $this->travel(2)->days();
        $svc->advanceByDate();
        $this->assertSame('running', $order->refresh()->stage);
        $this->travel(4)->days();
        $svc->advanceByDate();
        $this->assertSame('completed', $order->refresh()->stage);
        Mail::assertSent(OrderUpdated::class, fn ($m) => $m->event->title === 'Η καμπάνια ολοκληρώθηκε');

        // Εσωτερική σημείωση: δεν τη βλέπει ο πελάτης
        $svc->note($order, $staff, 'Μυστικό σημείωμα', true, true);
        $this->get(route('orders.show', $order))->assertDontSee('Μυστικό σημείωμα');
    }

    public function test_cancel_releases_coupon_and_expires_quote(): void
    {
        Coupon::query()->create(['code' => 'DOKIMI', 'name' => 'Δοκιμή', 'type' => 'percent', 'value' => 10, 'max_uses' => 1]);
        $q = app(QuoteService::class)->create($this->customer, 'Καμπάνια', 'manual',
            [['type' => 'radio', 'station_id' => Station::query()->orderBy('sort')->value('id'), 'spots_per_day' => 4]], 10, null);
        app(\App\Services\Coupons::class)->apply($q, 'DOKIMI', $this->customer);
        app(QuoteService::class)->submit($q->refresh(), null);
        $order = $q->refresh()->order;
        $this->assertDatabaseCount('coupon_redemptions', 1);

        app(OrderService::class)->cancel($order, $this->staff(), 'Ο πελάτης άλλαξε γνώμη.');
        $this->assertSame('cancelled', $order->stage);
        $this->assertSame('expired', $q->refresh()->status);
        $this->assertDatabaseCount('coupon_redemptions', 0);
    }

    public function test_orders_are_private(): void
    {
        $order = $this->order();
        $file = app(OrderService::class)->addFiles($order, [UploadedFile::fake()->create('spot.mp3', 10)], $this->customer);
        $f = $order->files()->first();

        $this->actingAs($this->makeCustomer('094014201'));
        $this->get(route('orders.show', $order))->assertNotFound();
        $this->get(route('orders.file', [$order, $f]))->assertNotFound();
        $this->post(route('orders.upload', $order), ['files' => [UploadedFile::fake()->create('a.mp3', 10)]])->assertNotFound();

        $this->actingAs($this->staff(UserRole::Studio), 'admin');
        $this->get(route('admin.order-file', $f))->assertForbidden();
        $this->actingAs($this->staff(), 'admin');
        $this->get(route('admin.order-file', $f))->assertOk();
    }

    public function test_admin_order_page_and_actions(): void
    {
        $order = $this->order();
        $staff = $this->staff();
        $this->actingAs($staff, 'admin');

        $this->get('/admin/paraggelies')->assertOk()->assertSee($order->number);
        $this->get('/admin/paraggelies/'.$order->ulid)->assertOk()->assertSee('Επιβεβαίωση παραγγελίας')->assertSee('Υλικό καμπάνιας');

        Livewire::test(ViewOrder::class, ['record' => $order->ulid])
            ->callAction('confirm', ['note' => 'ΟΚ', 'notify' => true])->assertHasNoActionErrors();
        $this->assertSame('awaiting_payment', $order->refresh()->stage);

        Livewire::test(ViewOrder::class, ['record' => $order->ulid])
            ->callAction('pay', ['amount' => 50, 'next' => 'production', 'notify' => false])->assertHasNoActionErrors();
        $order->refresh();
        $this->assertSame('awaiting_payment', $order->stage); // μερική πληρωμή
        $this->assertEquals(50, (float) $order->paid_amount);

        Livewire::test(ViewOrder::class, ['record' => $order->ulid])
            ->callAction('pay', ['amount' => $order->balance(), 'next' => 'production', 'notify' => true])->assertHasNoActionErrors();
        $this->assertSame('production', $order->refresh()->stage);

        Livewire::test(ViewOrder::class, ['record' => $order->ulid])
            ->callAction('materials', ['note' => 'Λογότυπο', 'notify' => true])->assertHasNoActionErrors();
        $this->assertSame('materials', $order->refresh()->stage);

        Livewire::test(ViewOrder::class, ['record' => $order->ulid])
            ->callAction('schedule', ['starts_on' => today()->format('Y-m-d'), 'ends_on' => today()->addDays(9)->format('Y-m-d'), 'notify' => true])
            ->assertHasNoActionErrors();
        $this->assertSame('running', $order->refresh()->stage); // ξεκινά σήμερα

        $this->get('/admin')->assertOk()->assertSee('Καμπάνιες σε προβολή')->assertSee('Ανεξόφλητες παραγγελίες');
    }

    public function test_iban_validation(): void
    {
        $this->assertTrue(BankAccounts::valid('GR16 0110 1250 0000 0001 2300 695'));
        $this->assertFalse(BankAccounts::valid('GR16 0110 1250 0000 0001 2300 696'));
        $this->assertSame('GR16 0110 1250 0000 0001 2300 695', BankAccounts::format('gr1601101250000000012300695'));
    }

    public function test_too_large_upload_shows_friendly_page(): void
    {
        $order = $this->order();
        $this->actingAs($this->customer);
        $this->withServerVariables(['CONTENT_LENGTH' => (string) (200 * 1048576)])
            ->post(route('orders.upload', $order), [], ['Referer' => route('orders.show', $order)])
            ->assertStatus(413)->assertSee('Πολύ μεγάλα αρχεία')->assertSee(route('orders.show', $order), false);
    }
}
