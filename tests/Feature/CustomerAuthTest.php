<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\ResetPasswordGreek;
use App\Notifications\VerifyEmailGreek;
use App\Rules\GreekVat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.preview_password' => null]);
        // «Έχει διαρρεύσει ο κωδικός;» → ψεύτικη απάντηση: όχι
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200), '*' => Http::response('', 200)]);
    }

    /** @return array<string, mixed> */
    protected function form(array $over = []): array
    {
        return array_merge([
            'legal_name' => 'Παπαδόπουλος Ι. & ΣΙΑ ΟΕ',
            'trade_name' => 'Καφέ Αστέρι',
            'business_type' => 'food',
            'vat' => '094014201',
            'tax_office' => 'Ξάνθης',
            'gemi' => '123456789000',
            'address' => 'Βενιζέλου 12',
            'city' => 'Ξάνθη',
            'postal_code' => '671 00',
            'mobile' => '+30 694 123 4567',
            'contact_person' => 'Ιωάννης Παπαδόπουλος',
            'email' => 'Info@Asteri.gr',
            'password' => 'Asteri-Xanthi-2026',
            'password_confirmation' => 'Asteri-Xanthi-2026',
            'terms' => '1',
            'marketing' => '1',
        ], $over);
    }

    public function test_vat_checksum(): void
    {
        $this->assertTrue(GreekVat::isValid('094014201'));
        $this->assertFalse(GreekVat::isValid('123456789'));
        $this->assertFalse(GreekVat::isValid('12345'));
    }

    public function test_business_can_register_and_gets_greek_verification_email(): void
    {
        Notification::fake();

        $this->post('/eggrafi', $this->form())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'info@asteri.gr')->firstOrFail();
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);

        $b = $user->business;
        $this->assertSame('6941234567', $b->mobile);
        $this->assertSame('67100', $b->postal_code);
        $this->assertNotNull($b->terms_accepted_at);
        $this->assertSame(Business::TERMS_VERSION, $b->terms_version);
        $this->assertNotNull($b->marketing_consent_at);

        Notification::assertSentTo($user, VerifyEmailGreek::class);
    }

    public function test_registration_rejects_bad_data(): void
    {
        $this->post('/eggrafi', $this->form(['vat' => '123456789', 'mobile' => '2541012345', 'terms' => null, 'postal_code' => '123']))
            ->assertSessionHasErrors(['vat', 'mobile', 'terms', 'postal_code']);

        $this->assertSame(0, User::count());
    }

    public function test_honeypot_blocks_bots(): void
    {
        $this->post('/eggrafi', $this->form(['website_url' => 'http://spam']))->assertSessionHasErrors('website_url');
        $this->assertSame(0, User::count());
    }

    public function test_duplicate_vat_is_rejected(): void
    {
        $this->post('/eggrafi', $this->form());
        auth()->logout();

        $this->post('/eggrafi', $this->form(['email' => 'other@asteri.gr']))->assertSessionHasErrors('vat');
    }

    public function test_marketing_is_optional(): void
    {
        $this->post('/eggrafi', $this->form(['marketing' => null]));

        $this->assertNull(Business::first()->marketing_consent_at);
    }

    public function test_verification_link_verifies_email(): void
    {
        $this->post('/eggrafi', $this->form());
        $user = User::first();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect(route('account'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_login_logout_and_rate_limit(): void
    {
        $this->post('/eggrafi', $this->form());
        $this->post('/aposyndesi')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/syndesi', ['email' => 'info@asteri.gr', 'password' => 'Asteri-Xanthi-2026'])->assertRedirect(route('account'));
        $this->assertAuthenticated();
        $this->post('/aposyndesi');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/syndesi', ['email' => 'info@asteri.gr', 'password' => 'wrong']);
        }
        $this->post('/syndesi', ['email' => 'info@asteri.gr', 'password' => 'Asteri-Xanthi-2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_staff_cannot_log_in_from_public_form(): void
    {
        $admin = User::factory()->create(['password' => 'Admin-Pass-2026x']);
        $admin->role = UserRole::Admin;
        $admin->save();

        $this->post('/syndesi', ['email' => $admin->email, 'password' => 'Admin-Pass-2026x'])->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_password_reset_email_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $this->post('/eggrafi', $this->form());
        $this->post('/aposyndesi');

        $this->post('/kodikos', ['email' => 'nobody@example.com'])->assertSessionHas('status');
        $this->post('/kodikos', ['email' => 'info@asteri.gr'])->assertSessionHas('status');

        Notification::assertSentTo(User::first(), ResetPasswordGreek::class);
    }

    public function test_account_pages_and_update(): void
    {
        $this->post('/eggrafi', $this->form());

        $this->get('/to-studio-mou')->assertOk()->assertSee('Καφέ Αστέρι')->assertSee('Επιβεβαιώστε το email σας');
        $this->get('/to-studio-mou/stoixeia')->assertOk();

        $this->put('/to-studio-mou/stoixeia', $this->form(['city' => 'Κομοτηνή']))->assertRedirect(route('account'));
        $this->assertSame('Κομοτηνή', Business::first()->city);

        $this->put('/to-studio-mou/newsletter', ['marketing' => '0']);
        $this->assertNull(Business::first()->marketing_consent_at);
    }

    public function test_account_deletion_requires_password(): void
    {
        $this->post('/eggrafi', $this->form());

        $this->delete('/to-studio-mou', ['password' => 'wrong'])->assertSessionHasErrors('password', null, 'delete');
        $this->assertSame(1, User::count());

        $this->delete('/to-studio-mou', ['password' => 'Asteri-Xanthi-2026'])->assertRedirect(route('home'));
        $this->assertSame(0, User::count());
        $this->assertSame(0, Business::count());
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/to-studio-mou')->assertRedirect(route('login'));
    }

    public function test_pages_render(): void
    {
        foreach (['/eggrafi', '/syndesi', '/kodikos', '/oroi-xrisis', '/prostasia-dedomenon'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
