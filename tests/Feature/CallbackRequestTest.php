<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Controllers\CallbackRequestController;
use App\Mail\CallbackRequested;
use App\Models\CallbackRequest;
use App\Models\Setting;
use App\Models\User;
use App\Filament\Resources\CallbackRequests\Pages\EditCallbackRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class CallbackRequestTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        $this->travel(-10)->seconds();
        $t = CallbackRequestController::stamp();
        $this->travelBack();

        return array_merge([
            'first_name' => 'Γιώργος', 'last_name' => 'Παπαδόπουλος', 'mobile' => '+30 694 123 4567',
            'vat' => '094014201', 'consent' => '1', 't' => $t, 'company_url' => '',
        ], $over);
    }

    private function staff(UserRole $role = UserRole::Sales): User
    {
        $u = User::factory()->create();
        $u->role = $role;
        $u->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $u->save();

        return $u;
    }

    public function test_valid_request_reaches_admin_bell_and_declared_email(): void
    {
        Mail::fake();
        Setting::putMany(['callback_emails' => 'sales@onairmedia.gr, boss@onairmedia.gr']);
        $sales = $this->staff();

        $this->postJson(route('callback.store'), $this->payload())->assertOk()->assertJson(['ok' => true]);

        $cb = CallbackRequest::query()->sole();
        $this->assertSame('6941234567', $cb->mobile);
        $this->assertSame('new', $cb->status);
        $this->assertSame(1, $sales->notifications()->count());
        Mail::assertSent(CallbackRequested::class, fn ($m) => $m->hasTo('sales@onairmedia.gr') && $m->hasTo('boss@onairmedia.gr'));
    }

    public function test_bots_are_silently_dropped(): void
    {
        Mail::fake();
        $this->postJson(route('callback.store'), $this->payload(['company_url' => 'http://spam']))->assertOk();
        $this->postJson(route('callback.store'), $this->payload(['t' => CallbackRequestController::stamp()]))->assertOk(); // σε 0″
        $this->postJson(route('callback.store'), $this->payload(['t' => 'xyz']))->assertOk();

        $this->assertSame(0, CallbackRequest::query()->count());
        Mail::assertNothingSent();
    }

    public function test_invalid_vat_mobile_and_missing_consent_are_rejected(): void
    {
        $this->postJson(route('callback.store'), $this->payload(['vat' => '123456789', 'mobile' => '2541012345', 'consent' => null]))
            ->assertStatus(422)->assertJsonValidationErrors(['vat', 'mobile', 'consent']);
        $this->assertSame(0, CallbackRequest::query()->count());
    }

    public function test_staff_handle_request_and_studio_cannot_see_it(): void
    {
        $cb = CallbackRequest::query()->create(['first_name' => 'Α', 'last_name' => 'Β', 'mobile' => '6941234567', 'vat' => '094014201']);
        $sales = $this->staff();
        $this->actingAs($sales, 'admin');

        $this->get('/admin')->assertOk()->assertSee('Να σας καλέσουμε');
        Livewire::test(EditCallbackRequest::class, ['record' => $cb->getRouteKey()])
            ->fillForm(['status' => 'contacted', 'notes' => 'Κλείσαμε ραντεβού'])
            ->call('save')->assertHasNoFormErrors();
        $cb->refresh();
        $this->assertSame('contacted', $cb->status);
        $this->assertSame($sales->id, $cb->handled_by_id);

        $this->actingAs($this->staff(UserRole::Studio), 'admin');
        $this->get('/admin/aitimata')->assertForbidden();
    }

    public function test_new_customer_form_is_prefilled_from_request(): void
    {
        $this->actingAs($this->staff(), 'admin');
        Livewire::withQueryParams(['afm' => '094014201', 'onoma' => 'Γιώργος Π.', 'kinito' => '6941234567'])
            ->test(\App\Filament\Resources\Businesses\Pages\CreateBusiness::class)
            ->assertSchemaStateSet(['vat' => '094014201', 'contact_person' => 'Γιώργος Π.', 'mobile' => '6941234567', 'send_invite' => true]);
    }
}
