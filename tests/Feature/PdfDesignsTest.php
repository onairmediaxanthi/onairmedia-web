<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Setting;
use App\Models\Station;
use App\Models\User;
use App\Services\Quotes\QuotePdf;
use App\Services\Quotes\QuoteService;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PdfDesignsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_design_renders_and_the_chosen_one_is_used(): void
    {
        $this->seed(StationSeeder::class);
        Station::query()->orderBy('sort')->first()->update(['price_spot' => 5, 'is_bookable' => true]);
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->business()->save(tap(new Business(['legal_name' => 'Καφέ ΟΕ', 'business_type' => 'food', 'vat' => '090000045', 'address' => 'Α', 'city' => 'Ξάνθη',
            'postal_code' => '67100', 'mobile' => '6941234567', 'contact_person' => 'Νίκος']), fn ($b) => [$b->terms_accepted_at = now(), $b->terms_version = '2026-10']));
        $q = app(QuoteService::class)->create($u, 'Χριστούγεννα', 'manual',
            [['type' => 'radio', 'station_id' => Station::query()->orderBy('sort')->value('id'), 'spots_per_day' => 4], ['type' => 'creative', 'key' => 'spot_production']], 10, null);

        foreach (array_keys(QuotePdf::DESIGNS) as $d) {
            $pdf = QuotePdf::render($q, $d);
            $this->assertStringStartsWith('%PDF', $pdf, $d);
            $this->assertGreaterThan(5000, strlen($pdf), $d);
        }

        $this->assertSame('classic', QuotePdf::design());
        Setting::putMany(['pdf_design' => 'aurora']);
        $this->assertSame('aurora', QuotePdf::design());
        Setting::putMany(['pdf_design' => 'nope']);
        $this->assertSame('classic', QuotePdf::design());

        // Προεπισκόπηση μόνο για το προσωπικό
        $this->get(route('admin.quote-pdf', [$q->id, 'night']))->assertRedirect();
        $staff = User::factory()->create();
        $staff->role = UserRole::Sales;
        $staff->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $staff->save();
        $this->actingAs($staff, 'admin')->get(route('admin.quote-pdf', [$q->id, 'editorial']))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('admin.quote-pdf', [$q->id, 'xyz']))->assertNotFound();
    }

    public function test_greek_capitals_drop_accents(): void
    {
        $up = QuotePdf::data(new \App\Models\Quote)['up'] ?? null;
        $this->assertNotNull($up);
        $this->assertSame('ΠΟΣΟΤΗΤΑ ΜΕΣΟ ΕΝΑΡΞΗ', $up('Ποσότητα Μέσο Έναρξη'));
    }
}
