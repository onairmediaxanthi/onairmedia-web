<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function customer(bool $verified = true, bool $marketing = true): Business
    {
        $user = User::factory()->create(['email_verified_at' => $verified ? now() : null]);
        $b = new Business([
            'legal_name' => 'Δοκιμή ΑΕ '.$user->id, 'business_type' => 'food', 'vat' => $user->id === 1 ? '094014201' : '090000045',
            'address' => 'Οδός 1', 'city' => 'Ξάνθη', 'postal_code' => '67100', 'mobile' => '6941234567', 'contact_person' => 'Γιάννης',
        ]);
        $b->terms_accepted_at = now();
        $b->terms_version = Business::TERMS_VERSION;
        $b->marketing_consent_at = $marketing ? now() : null;
        $user->business()->save($b);

        return $b;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer(true, true);
        $this->customer(false, false);

        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $admin->save();
        $this->actingAs($admin);
    }

    public function test_list_renders_with_customers_and_filters_work(): void
    {
        Livewire::test(ListBusinesses::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Business::all())
            ->filterTable('verified', true)
            ->assertCanSeeTableRecords(Business::where('legal_name', 'like', '%1')->get())
            ->assertCanNotSeeTableRecords(Business::where('legal_name', 'like', '%2')->get())
            ->resetTableFilters()
            ->filterTable('marketing', false)
            ->assertCanNotSeeTableRecords(Business::where('legal_name', 'like', '%1')->get())
            ->searchTable('094014201')
            ->assertCountTableRecords(0);
    }

    public function test_edit_page_saves_notes(): void
    {
        $b = Business::first();

        Livewire::test(EditBusiness::class, ['record' => $b->getRouteKey()])
            ->fillForm(['admin_notes' => 'Τηλεφώνησε για πακέτο Πάσχα'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Τηλεφώνησε για πακέτο Πάσχα', $b->fresh()->admin_notes);
    }
}
