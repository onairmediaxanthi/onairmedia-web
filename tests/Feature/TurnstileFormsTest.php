<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TurnstileFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.turnstile.site_key' => 'site-test', 'services.turnstile.secret_key' => 'secret-test']);
    }

    public function test_widget_shows_on_public_forms(): void
    {
        foreach (['login', 'register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('cf-turnstile', false)->assertSee('site-test', false);
        }
    }

    public function test_login_and_password_reset_need_robot_check(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'Kodikos!2026']);
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => false])->push(['success' => true])]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Kodikos!2026'])->assertSessionHasErrors('robot');
        $this->assertGuest();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('robot');
        // λάθος token: το Cloudflare απαντά success=false
        $this->post(route('password.email'), ['email' => $user->email, 'cf-turnstile-response' => 'bad'])->assertSessionHasErrors('robot');
        Notification::assertNothingSent();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'Kodikos!2026', 'cf-turnstile-response' => 'ok'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }
}
