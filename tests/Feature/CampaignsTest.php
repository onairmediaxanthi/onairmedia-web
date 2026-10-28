<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Mail\CampaignMail;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CampaignsTest extends TestCase
{
    use RefreshDatabase;

    protected static int $n = 0;

    protected function business(bool $consent, bool $verified = true, string $type = 'food'): Business
    {
        $n = ++self::$n;
        $user = User::factory()->create(['email_verified_at' => $verified ? now() : null, 'email' => "c{$n}@example.com"]);
        $d = str_pad((string) (20000000 + $n), 8, '0', STR_PAD_LEFT);
        $sum = 0;
        foreach (str_split($d) as $i => $c) {
            $sum += (int) $c * (2 ** (8 - $i));
        }
        $b = new Business(['legal_name' => "Επιχείρηση {$n}", 'business_type' => $type, 'vat' => $d.(($sum % 11) % 10), 'address' => 'Α', 'city' => 'Ξάνθη',
            'postal_code' => '67100', 'mobile' => '6941234567', 'contact_person' => "Πελάτης {$n}"]);
        $b->terms_accepted_at = now();
        $b->terms_version = '2026-10';
        $b->marketing_consent_at = $consent ? now() : null;
        $user->business()->save($b);

        return $b;
    }

    protected function admin(): User
    {
        $a = User::factory()->create(['email' => 'boss@example.com']);
        $a->role = UserRole::Admin;
        $a->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $a->save();

        return $a;
    }

    public function test_only_consenting_verified_customers_are_reachable(): void
    {
        $yes = $this->business(true);
        $this->business(false);
        $this->business(true, verified: false);
        $shop = $this->business(true, type: 'retail');

        $this->assertEqualsCanonicalizing([$yes->id, $shop->id], Campaign::consenting()->pluck('id')->all());
        $c = new Campaign(['audience' => ['mode' => 'types', 'types' => ['retail']]]);
        $this->assertSame([$shop->id], $c->audienceQuery()->pluck('id')->all());
    }

    public function test_campaign_is_sent_in_batches_with_one_click_unsubscribe(): void
    {
        Mail::fake();
        Setting::putMany(['campaign_per_minute' => 1]);
        $a = $this->business(true);
        $b = $this->business(true);
        $this->business(false);
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(CreateCampaign::class)->fillForm([
            'name' => 'Black Friday', 'subject' => 'Προσφορές Black Friday', 'body' => '<p>Μεγάλες εκπτώσεις!</p>', 'audience' => ['mode' => 'all'],
        ])->call('create')->assertHasNoFormErrors();
        $campaign = Campaign::query()->firstOrFail();

        Livewire::test(EditCampaign::class, ['record' => $campaign->id])->callAction('test');
        Mail::assertSent(CampaignMail::class, fn ($m) => $m->hasTo('boss@example.com'));

        Livewire::test(EditCampaign::class, ['record' => $campaign->id])->callAction('send')->assertHasNoActionErrors();
        $this->assertSame('scheduled', $campaign->refresh()->status);

        $this->artisan('campaigns:send')->assertSuccessful();
        $this->assertSame(2, $campaign->recipients()->count());
        $this->assertSame(1, $campaign->recipients()->where('status', 'sent')->count()); // όριο 1/λεπτό

        // Στο μεταξύ ο δεύτερος σταματά τις προσφορές → δεν του στέλνουμε
        $pending = $campaign->recipients()->where('status', 'pending')->first();
        $pending->user->business->setMarketingConsent(false, 'account');
        $this->artisan('campaigns:send');
        $this->assertSame('skipped', $pending->refresh()->status);
        $this->assertSame('sent', $campaign->refresh()->status);

        Mail::assertSent(CampaignMail::class, function (CampaignMail $m) {
            $h = $m->headers()->text;

            return str_contains($h['List-Unsubscribe'], '/aposyndromi/') && $h['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click'
                && str_contains($m->render(), 'Μεγάλες εκπτώσεις') && str_contains($m->render(), 'Δεν θέλω άλλα τέτοια email');
        });
    }

    public function test_unsubscribe_works_without_login_and_one_click(): void
    {
        $b = $this->business(true);
        $campaign = Campaign::query()->create(['name' => 'x', 'subject' => 'x']);
        $r = CampaignRecipient::query()->create(['campaign_id' => $campaign->id, 'user_id' => $b->user_id, 'email' => $b->user->email, 'token' => str_repeat('a', 48)]);

        $this->get('/aposyndromi/'.$r->token)->assertOk()->assertSee('Ναι, διαγραφή');
        // Gmail/Yahoo: POST χωρίς cookies/CSRF
        $this->post('/aposyndromi/'.$r->token, ['List-Unsubscribe' => 'One-Click'])->assertOk();

        $b->refresh();
        $this->assertNull($b->marketing_consent_at);
        $this->assertNotNull($b->marketing_withdrawn_at);
        $this->assertSame('unsubscribe', $b->marketing_consent_source);
        $this->assertNotNull($r->refresh()->unsubscribed_at);
        $this->assertDatabaseHas('customer_activities', ['type' => 'marketing_off', 'user_id' => $b->user_id]);
        $this->get('/aposyndromi/nope')->assertNotFound();
    }
}
