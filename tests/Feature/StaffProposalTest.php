<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Mail\CustomerInvite;
use App\Mail\QuoteProposal;
use App\Models\Business;
use App\Models\Quote;
use App\Models\Station;
use App\Models\User;
use App\Services\CustomerInvites;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuoteService;
use Database\Seeders\AdSlotSeeder;
use Database\Seeders\ScreenSeeder;
use Database\Seeders\StationSeeder;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class StaffProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Station $star;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seed([StationSeeder::class, AdSlotSeeder::class, ScreenSeeder::class]);
        $this->star = Station::query()->orderBy('sort')->first();
        $this->star->update(['price_spot' => 5, 'is_bookable' => false, 'audience' => ['ages' => ['25-34']]]);

        $this->admin = User::factory()->create(['name' => 'Σάββας']);
        $this->admin->role = UserRole::Admin;
        $this->admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $this->admin->save();
        $this->actingAs($this->admin, 'admin');
    }

    protected function staffCustomer(bool $invite = false): Business
    {
        Livewire::test(CreateBusiness::class)
            ->fillForm([
                'account_email' => 'pelatis@example.com', 'send_invite' => $invite,
                'legal_name' => 'Φούρνος Παπαδόπουλος ΟΕ', 'business_type' => 'food', 'vat' => '094014201',
                'contact_person' => 'Νίκος', 'mobile' => '6941234567', 'address' => 'Οδός 2', 'city' => 'Ξάνθη', 'postal_code' => '67100',
            ])
            ->call('create')->assertHasNoFormErrors();

        return Business::query()->with('user')->firstOrFail();
    }

    public function test_admin_creates_customer_marked_as_staff_created_and_invites(): void
    {
        $b = $this->staffCustomer(invite: true);

        $this->assertSame('staff', $b->source);
        $this->assertSame($this->admin->id, $b->created_by_id);
        $this->assertTrue($b->user->isCustomer());
        $this->assertNull($b->user->email_verified_at);
        $this->assertNull($b->terms_accepted_at);
        $this->assertNotNull($b->invited_at);
        Mail::assertSent(CustomerInvite::class, fn ($m) => $m->hasTo('pelatis@example.com'));
        $this->assertDatabaseHas('customer_activities', ['type' => 'staff_created']);

        Livewire::test(ListBusinesses::class)
            ->filterTable('source', 'staff')->assertCanSeeTableRecords([$b])
            ->resetTableFilters()->filterTable('source', 'self')->assertCanNotSeeTableRecords([$b]);
    }

    public function test_customer_activates_from_invite_and_environments_stay_separate(): void
    {
        $b = $this->staffCustomer();
        Auth::guard('admin')->logout();
        $url = CustomerInvites::url($b);

        $this->get(route('invite.accept', ['user' => $b->user_id]))->assertForbidden();
        $this->get($url)->assertOk()->assertSee('Φούρνος Παπαδόπουλος ΟΕ');
        $this->post($url, ['password' => 'Kalimera-2026x', 'password_confirmation' => 'Kalimera-2026x'])->assertSessionHasErrors('terms');
        $this->post($url, ['password' => 'Kalimera-2026x', 'password_confirmation' => 'Kalimera-2026x', 'terms' => '1'])
            ->assertRedirect(route('account'));

        $b->refresh();
        $this->assertNotNull($b->user->email_verified_at);
        $this->assertNotNull($b->terms_accepted_at);
        $this->assertNull($b->marketing_consent_at);
        $this->assertAuthenticatedAs($b->user, 'web');
        $this->assertGuest('admin');

        // Ο πελάτης δεν «βλέπει» τη διαχείριση· και η σύνδεση εκεί είναι ξεχωριστή
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_admin_builds_quote_with_discount_and_wizard(): void
    {
        $b = $this->staffCustomer();
        $undo = Repeater::fake();

        $page = Livewire::withQueryParams(['pelatis' => $b->id])->test(CreateQuote::class)
            ->assertFormSet(['business_id' => $b->id, 'title' => 'Πρόταση προβολής · Φούρνος Παπαδόπουλος ΟΕ'])
            ->fillForm(['days' => 10, 'budget' => 1000, 'ages' => ['25-34'], 'media' => ['radio']])
            ->call('applyProposal', 'focus');
        $this->assertSame($this->star->id, (int) collect($page->get('data.radio'))->first()['station_id']);

        $page->fillForm([
            'radio' => [['station_id' => $this->star->id, 'spots_per_day' => 4, 'seconds' => 30, 'position' => 'standard', 'fixed_hour' => false, 'mentions_live' => 0, 'mentions_recorded' => 0]],
            'discount' => 10, 'intro' => 'Όπως συζητήσαμε.',
        ])->call('create')->assertHasNoFormErrors();
        $undo();

        $q = Quote::query()->firstOrFail();
        $this->assertSame('staff', $q->source);
        $this->assertSame($this->admin->id, $q->created_by_id);
        $this->assertSame($b->user_id, $q->user_id);
        $this->assertSame('180.00', $q->subtotal); // 4 × 10 × 5 = 200 − 10%
        $this->assertSame('discount', collect($q->items)->last()['type']);
        $this->assertSame('Όπως συζητήσαμε.', $q->intro);
        $this->assertStringStartsWith('%PDF', QuotePdf::render($q));
    }

    public function test_admin_sends_proposal_with_activation_link(): void
    {
        $b = $this->staffCustomer();
        $q = app(QuoteService::class)->createForCustomer($this->admin, $b->user, 'Πρόταση', [['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2]], 7, null, null);

        Livewire::test(ViewQuote::class, ['record' => $q->id])
            ->callAction('send', ['subject' => 'Η πρότασή σας', 'message' => 'Καλησπέρα κ. Νίκο', 'attach' => true])
            ->assertHasNoActionErrors();

        Mail::assertSent(QuoteProposal::class, function (QuoteProposal $m) use ($b) {
            $html = $m->render();

            return $m->hasTo('pelatis@example.com') && $m->hasReplyTo($this->admin->email)
                && str_contains($html, 'Καλησπέρα κ. Νίκο') && str_contains($html, '/energopoiisi/'.$b->user_id)
                && count($m->attachments()) === 1;
        });
        $q->refresh();
        $this->assertNotNull($q->sent_at);
        $this->assertSame('Καλησπέρα κ. Νίκο', $q->intro);
        $this->assertDatabaseHas('customer_activities', ['type' => 'quote_sent', 'quote_id' => $q->id]);
    }

    public function test_customers_cannot_give_themselves_a_discount(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $this->star->update(['is_bookable' => true]);
        $q = app(QuoteService::class)->create($customer, 'Χ', 'manual', [
            ['type' => 'radio', 'station_id' => $this->star->id, 'spots_per_day' => 2],
            ['type' => 'discount', 'percent' => 50],
        ], 5, null);

        $this->assertSame('50.00', $q->subtotal);
        $this->assertFalse(collect($q->items)->contains('type', 'discount'));
    }

    public function test_old_staff_session_is_logged_out_of_the_site(): void
    {
        $this->actingAs($this->admin, 'web')->get('/')->assertOk()->assertDontSee('Το Studio μου');
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($this->admin, 'admin');
    }
}
