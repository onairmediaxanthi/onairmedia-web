<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_is_locked_when_preview_password_is_set(): void
    {
        config(['app.preview_password' => 'secret-preview']);

        $this->get('/')->assertStatus(401)->assertSee('Κωδικός προεπισκόπησης');
        $this->get('/up')->assertOk();
    }

    public function test_correct_preview_password_unlocks_site(): void
    {
        config(['app.preview_password' => 'secret-preview']);

        $this->post('/preview-unlock', ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post('/preview-unlock', ['password' => 'secret-preview'])->assertRedirect('/');
        $this->get('/')->assertOk();
    }

    public function test_site_is_open_without_preview_password(): void
    {
        config(['app.preview_password' => null]);

        $this->get('/')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_only_staff_can_access_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $customer = User::factory()->create();
        $this->assertSame(UserRole::Customer, $customer->fresh()->role);
        $this->assertFalse($customer->fresh()->canAccessPanel($panel));

        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->save();
        $this->assertTrue($admin->fresh()->canAccessPanel($panel));
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'x@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }
}
