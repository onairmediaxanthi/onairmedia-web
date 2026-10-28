<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\HomeSections\Pages\CreateHomeSection;
use App\Filament\Resources\HomeSections\Pages\ListHomeSections;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HomeBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.preview_password' => null]);
        Http::fake(['*' => Http::response('', 200)]);
        $this->seed(DatabaseSeeder::class);
        Package::query()->update(['is_active' => true, 'price' => 350, 'compare_at_price' => 500]);
    }

    protected function admin(): User
    {
        $u = User::factory()->create();
        $u->role = UserRole::Admin;
        $u->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $u->save();

        return $u;
    }

    public function test_sections_follow_admin_order_and_menu(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['data-section="slider"', 'data-section="dial"', 'data-section="packages"', 'data-section="media"'], false)
            ->assertSee('id="paketa"', false)
            ->assertSee('#paketa', false);
    }

    public function test_inactive_section_is_not_rendered(): void
    {
        HomeSection::where('type', 'packages')->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('data-section="packages"', false);
    }

    public function test_device_visibility_adds_hide_classes(): void
    {
        HomeSection::where('type', 'dial')->update(['show_mobile' => false, 'show_desktop' => false]);

        $this->get('/')->assertOk()->assertSee('max-sm:hidden lg:hidden" data-section="dial"', false);
    }

    public function test_guests_do_not_see_prices(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Δείτε την τιμή')
            ->assertDontSee('350 €');
    }

    public function test_verified_customer_sees_prices(): void
    {
        $customer = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($customer)->get('/')->assertOk()
            ->assertSee('350 €')
            ->assertSee('500 €');
    }

    public function test_unverified_customer_does_not_see_prices(): void
    {
        $customer = User::factory()->unverified()->create();

        $this->actingAs($customer)->get('/')->assertOk()->assertDontSee('350 €');
    }

    public function test_expired_offer_is_hidden(): void
    {
        Package::query()->update(['available_until' => now()->subMinute()]);

        $this->get('/')->assertOk()->assertDontSee('data-section="packages"', false);
    }

    public function test_admin_pages_render_and_settings_save(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListHomeSections::class)->assertOk()->assertSee('Δείκτης FM');
        Livewire::test(ListPackages::class)->assertOk()->assertSee('Σήμα Πόλης');

        Livewire::test(CreateHomeSection::class)
            ->fillForm(['type' => 'text', 'title' => 'Λίγα λόγια', 'settings' => ['body' => '<p>Γεια</p><script>x()</script>', 'align' => 'left'],
                'show_mobile' => true, 'show_tablet' => true, 'show_desktop' => true, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/')->assertSee('Λίγα λόγια')->assertDontSee('<script>x()</script>', false);

        Livewire::test(SiteSettings::class)
            ->fillForm(['company_name' => 'On Air Media Group', 'phone' => '25410 12345'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('25410 12345', Setting::get('phone'));
        $this->get('/')->assertSee('25410 12345');
    }

    public function test_menu_hides_packages_when_none_available(): void
    {
        Package::query()->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee('>Πακέτα<', false)->assertSee('>Σταθμοί<', false);
    }
}
