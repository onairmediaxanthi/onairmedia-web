<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\StaffUsers\Pages\CreateStaffUser;
use App\Filament\Resources\StaffUsers\Pages\EditStaffUser;
use App\Filament\Resources\StaffUsers\Pages\ListStaffUsers;
use App\Mail\StaffInvite;
use App\Models\User;
use App\Services\StaffAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class StaffUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(UserRole $role, string $email): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->role = $role;
        $u->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $u->save();

        return $u;
    }

    public function test_admin_invites_a_new_staff_member(): void
    {
        Mail::fake();
        $admin = $this->staff(UserRole::Admin, 'boss@example.com');
        User::factory()->create(['email' => 'pelatis@example.com']); // πελάτης: δεν φαίνεται εδώ
        $this->actingAs($admin, 'admin');

        Livewire::test(ListStaffUsers::class)->assertOk()->assertSee('boss@example.com')->assertDontSee('pelatis@example.com');
        Livewire::test(CreateStaffUser::class)
            ->fillForm(['name' => 'Μαρία Πωλήσεις', 'email' => 'Maria@Example.com', 'role' => 'sales', 'send_invite' => true])
            ->call('create')->assertHasNoFormErrors();

        $maria = User::query()->where('email', 'maria@example.com')->firstOrFail();
        $this->assertSame(UserRole::Sales, $maria->role);
        Mail::assertSent(StaffInvite::class, fn ($m) => $m->hasTo('maria@example.com') && str_contains($m->url, '/admin/password-reset/reset'));
    }

    public function test_roles_see_only_their_areas(): void
    {
        $sales = $this->staff(UserRole::Sales, 'sales@example.com');
        $this->actingAs($sales, 'admin');

        $this->get('/admin/prosfores')->assertOk();
        $this->get('/admin/stations')->assertForbidden();
        $this->get('/admin/xristes')->assertForbidden();
        $this->get('/admin/timokatalogos/radiofona')->assertForbidden();
        $this->get('/admin/rythmiseis-prosforon')->assertForbidden();

        $studio = $this->staff(UserRole::Studio, 'studio@example.com');
        $this->actingAs($studio, 'admin');
        $this->get('/admin/stations')->assertOk();
        $this->get('/admin/prosfores')->assertForbidden();
    }

    public function test_safety_rules_and_disabling(): void
    {
        $admin = $this->staff(UserRole::Admin, 'boss@example.com');
        $other = $this->staff(UserRole::Sales, 'x@example.com');
        $svc = app(StaffAccounts::class);

        foreach ([
            fn () => $svc->guard($admin, $admin, disable: true),
            fn () => $svc->guard($admin, $admin, newRole: UserRole::Sales),
            fn () => $svc->guard($admin, $other, newRole: UserRole::Sales), // τελευταίος διαχειριστής
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Έπρεπε να απαγορευτεί');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }

        $this->actingAs($admin, 'admin');
        Livewire::test(EditStaffUser::class, ['record' => $other->id])->callAction('toggle')->assertHasNoActionErrors();
        $this->assertNotNull($other->refresh()->disabled_at);

        // Ο απενεργοποιημένος δεν μπαίνει στη διαχείριση
        $this->actingAs($other, 'admin')->get('/admin/prosfores')->assertForbidden();

        $this->actingAs($admin, 'admin');
        Livewire::test(EditStaffUser::class, ['record' => $other->id])->callAction('reset_mfa');
        $this->assertNull($other->refresh()->app_authentication_secret);
    }
}
