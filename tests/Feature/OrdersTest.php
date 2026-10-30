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

    protected function fixture(string $name): UploadedFile
    {
        return new UploadedFile(base_path('tests/fixtures/'.$name), $name, null, null, true);
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

        // Ο πελάτης ανεβάζει το σποτ στο σωστό κουτί και ολοκληρώνει
        $this->get(route('orders.show', $order->refresh()))->assertSee('Περιμένουμε το υλικό σας')->assertSee('Ραδιοφωνικό σποτ 30″')->assertSee('Λείπει:')->assertSee('Έστειλα όλο το υλικό');
        $this->post(route('orders.materials-done', $order))->assertSessionHas('error'); // λείπει το σποτ
        $this->post(route('orders.upload', $order), ['slot' => 'radio:30', 'files' => [$this->fixture('spot-10s-320.mp3')]])->assertSessionHas('status');
        $this->post(route('orders.upload', $order), ['slot' => 'radio:30', 'files' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors();
        $this->assertSame(1, $order->files()->count());
        $file = $order->files()->first();
        $this->assertSame('radio:30', $file->slot);
        $this->assertSame(320, $file->meta['kbps']);
        $this->assertNotEmpty($file->warnings()); // 10″ αντί για 30″: σημείωση, όχι απόρριψη
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
        app(OrderService::class)->addFiles($order, [UploadedFile::fake()->image('logo.png', 800, 400)], $this->customer, slot: 'logo');
        $f = $order->files()->first();

        $this->actingAs($this->makeCustomer('094014201'));
        $this->get(route('orders.show', $order))->assertNotFound();
        $this->get(route('orders.file', [$order, $f]))->assertNotFound();
        $this->post(route('orders.upload', $order), ['slot' => 'logo', 'files' => [UploadedFile::fake()->image('a.png')]])->assertNotFound();

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

    public function test_staff_can_preview_sent_email_and_send_test_mail(): void
    {
        $order = $this->order();
        app(OrderService::class)->confirm($order, null, 'Γεια σας');
        $event = $order->events()->where('notified', true)->first();

        $this->actingAs($this->staff(), 'admin');
        $this->get(route('admin.order-email', $event))->assertOk()->assertSee('Η παραγγελία επιβεβαιώθηκε')->assertSee('GR16 0110 1250 0000 0001 2300 695');
        $this->get('/admin/paraggelies/'.$order->ulid)->assertSee('προβολή email');

        $this->actingAs($this->staff(UserRole::Admin), 'admin');
        Livewire::test(\App\Filament\Pages\QuoteSettings::class)->callAction('testMail', ['to' => 'test@example.com']);
        Mail::assertSent(\App\Mail\TestMail::class, fn ($m) => $m->hasTo('test@example.com'));
    }

    public function test_material_slots_follow_what_was_bought(): void
    {
        $slot = \App\Models\AdSlot::query()->create(['code' => 'lb', 'name' => 'Κορυφή σελίδας', 'dimensions' => '728×90 · κινητό 320×100', 'price_per_day' => 5, 'is_active' => true]);
        $screen = \App\Models\Screen::query()->create(['slug' => 'othoni', 'name' => 'Οθόνη Πόλης', 'resolution' => '1280x720', 'slot_seconds' => 10, 'price_per_day' => 10, 'is_active' => true]);
        \App\Models\Setting::putMany(['price_spot_production' => 60]);
        $station = Station::query()->orderBy('sort')->value('id');
        $q = app(QuoteService::class)->create($this->customer, 'Όλα', 'manual', [
            ['type' => 'radio', 'station_id' => $station, 'spots_per_day' => 4, 'seconds' => 20],
            ['type' => 'banner', 'slot_id' => $slot->id],
            ['type' => 'led', 'screen_id' => $screen->id],
        ], 10, null);
        app(QuoteService::class)->submit($q, null);
        $order = $q->refresh()->order;
        $plan = \App\Services\Orders\MaterialPlan::for($order);

        $this->assertSame(['radio:20', 'banner:'.$slot->id, 'led:'.$screen->id, 'logo'], array_keys($plan));
        $this->assertFalse($plan['radio:20']['we_make']);
        $this->assertSame(['mp3', 'wav'], $plan['radio:20']['accept']);
        $this->assertCount(2, $plan['banner:'.$slot->id]['image']['sizes']); // υπολογιστής + κινητό
        $svc = app(OrderService::class);

        // Ήχος: πολύ χαμηλή ποιότητα απορρίπτεται, 192 kbps γίνεται δεκτό με σημείωση, η διάρκεια δεν απορρίπτει ποτέ
        $this->expectFail(fn () => $svc->addFiles($order, [$this->fixture('spot-10s-64.mp3')], $this->customer, slot: 'radio:20'), 'χαμηλή ποιότητα');
        $svc->addFiles($order, [$this->fixture('spot-14s-192.mp3')], $this->customer, slot: 'radio:20');
        $f = $order->files()->where('slot', 'radio:20')->first();
        $this->assertSame(192, $f->meta['kbps']);
        $this->assertCount(2, $f->warnings()); // 192 kbps + διάρκεια 14″ αντί 20″

        // Η ανοχή διάρκειας ορίζεται στις ρυθμίσεις
        \App\Models\Setting::putMany(['spot_tolerance_seconds' => 10]);
        $svc->addFiles($order, [$this->fixture('spot-14s-192.mp3')], $this->customer, slot: 'radio:20');
        $this->assertCount(1, $order->files()->where('slot', 'radio:20')->latest('id')->first()->warnings());

        // WAV
        $wav = tempnam(sys_get_temp_dir(), 'w').'.wav';
        $data = str_repeat("\0", 44100 * 4 * 2);
        file_put_contents($wav, 'RIFF'.pack('V', 36 + strlen($data)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 2, 44100, 44100 * 4, 4, 16).'data'.pack('V', strlen($data)).$data);
        $this->assertEquals(['channels' => 2, 'sample_rate' => 44100, 'bits' => 16, 'seconds' => 2.0], \App\Support\FileInspector::inspect($wav, 'wav'));

        // Banner: λάθος διάσταση απορρίπτεται, το διπλάσιο γίνεται δεκτό, χρειάζονται και οι δύο διαστάσεις
        $this->expectFail(fn () => $svc->addFiles($order, [UploadedFile::fake()->image('b.png', 700, 90)], $this->customer, slot: 'banner:'.$slot->id), '728×90');
        $svc->addFiles($order, [UploadedFile::fake()->image('b.png', 1456, 180)], $this->customer, slot: 'banner:'.$slot->id);
        $st = \App\Services\Orders\MaterialPlan::status($order->refresh());
        $this->assertSame('missing', $st['banner:'.$slot->id]['state']);
        $this->assertStringContainsString('320×100', implode(' ', $st['banner:'.$slot->id]['missing']));
        $svc->addFiles($order, [UploadedFile::fake()->image('m.png', 320, 100)], $this->customer, slot: 'banner:'.$slot->id);

        // LED: ακριβής ανάλυση
        $this->expectFail(fn () => $svc->addFiles($order, [UploadedFile::fake()->image('l.png', 1920, 1080)], $this->customer, slot: 'led:'.$screen->id), '1280×720');
        $svc->addFiles($order, [UploadedFile::fake()->image('l.png', 1280, 720)], $this->customer, slot: 'led:'.$screen->id);
        $this->assertTrue(\App\Services\Orders\MaterialPlan::complete($order->refresh()));

        // Με αγορασμένη παραγωγή σποτ, ζητείται κείμενο αντί για αρχείο
        $q2 = app(QuoteService::class)->create($this->customer, 'Σποτ από εμάς', 'manual', [
            ['type' => 'radio', 'station_id' => $station, 'spots_per_day' => 2], ['type' => 'creative', 'key' => 'spot_production'],
        ], 5, null);
        app(QuoteService::class)->submit($q2, null);
        $o2 = $q2->refresh()->order;
        $p2 = \App\Services\Orders\MaterialPlan::for($o2);
        $this->assertTrue($p2['radio:30']['we_make']);
        $this->assertFalse(\App\Services\Orders\MaterialPlan::complete($o2));
        $this->actingAs($this->customer)->post(route('orders.brief', $o2), ['slot' => 'radio:30', 'text' => 'Black Friday στο Καφέ Πλατεία'])->assertSessionHas('status');
        $this->assertTrue(\App\Services\Orders\MaterialPlan::complete($o2->refresh()));
        $this->get(route('orders.show', $o2))->assertSee('Αυτό το φτιάχνουμε εμείς')->assertSee('Black Friday στο Καφέ Πλατεία');
    }

    public function test_files_are_purged_only_after_campaign_and_archive_confirmation(): void
    {
        $order = $this->order();
        $svc = app(OrderService::class);
        $staff = $this->staff();
        $svc->addFiles($order, [$this->fixture('spot-10s-320.mp3')], $this->customer, slot: 'radio:30');
        $svc->addFiles($order, [UploadedFile::fake()->image('logo.png', 900, 300)], $this->customer, slot: 'logo');
        $paths = $order->files()->pluck('path');

        $this->expectFail(fn () => $svc->purgeFiles($order, $staff, true), 'μετά τη λήξη');
        $svc->setStage($order, 'completed', $staff, null, false);
        $this->assertTrue($order->canPurgeFiles());
        $this->assertTrue($staff->notifications()->get()->contains(fn ($n) => str_starts_with($n->data['title'] ?? '', 'Ολοκληρώθηκε'))); // υπενθύμιση στο προσωπικό

        $this->actingAs($staff, 'admin');
        $this->get(route('admin.order-zip', $order))->assertOk()->assertDownload();
        $this->get('/admin/paraggelies?activeTab=purge')->assertOk()->assertSee($order->number);

        $this->expectFail(fn () => $svc->purgeFiles($order, $staff, false), 'αρχειοθετηθεί');
        Livewire::test(ViewOrder::class, ['record' => $order->ulid])->callAction('purge', ['archived' => true])->assertHasNoActionErrors();

        foreach ($paths as $p) {
            Storage::disk('local')->assertMissing($p);
        }
        $order->refresh();
        $this->assertNotNull($order->files_purged_at);
        $this->assertSame(2, $order->files()->whereNotNull('purged_at')->count()); // οι εγγραφές μένουν
        $this->assertFalse($order->canPurgeFiles());
        $this->actingAs($this->customer)->get(route('orders.show', $order))->assertSee('Τα αρχεία αρχειοθετήθηκαν');
    }

    protected function expectFail(callable $fn, string $contains): void
    {
        try {
            $fn();
            $this->fail('Περίμενα απόρριψη: '.$contains);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString($contains, $e->getMessage());
        }
    }

    public function test_customer_can_message_about_order_and_staff_is_alerted(): void
    {
        $order = $this->order();
        $staff = $this->staff();
        $this->actingAs($this->customer);

        $page = $this->get(route('orders.show', $order))->assertOk()->assertSee('Αποστολή μηνύματος');
        $this->assertMatchesRegularExpression('/x-data @click="\$dispatch\(\'open-callback\'\)"/', $page->getContent()); // το κουμπί δουλεύει
        $this->assertStringContainsString('090000045', $page->getContent()); // «Να σας καλέσουμε» με τα στοιχεία του

        $this->post(route('orders.message', $order), ['message' => ''])->assertSessionHasErrors('message');
        $this->post(route('orders.message', $order), ['message' => 'Μπορούμε να ξεκινήσουμε μια μέρα νωρίτερα;'])->assertSessionHas('status');

        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'title' => 'Μήνυμα από εσάς', 'note' => 'Μπορούμε να ξεκινήσουμε μια μέρα νωρίτερα;']);
        $this->assertTrue($staff->notifications()->get()->contains(fn ($n) => ($n->data['title'] ?? '') === 'Μήνυμα για παραγγελία'));
        Mail::assertSent(\App\Mail\CompanyAlert::class, fn ($m) => $m->type === 'order_message' && str_contains($m->render(), 'μια μέρα νωρίτερα') && str_contains($m->render(), $order->number));
        $this->get(route('orders.show', $order))->assertSee('μια μέρα νωρίτερα');
    }
}
