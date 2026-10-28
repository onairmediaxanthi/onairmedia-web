<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Stations\Pages\EditStation;
use App\Filament\Resources\Stations\Pages\ListStations;
use App\Models\Station;
use App\Models\User;
use App\Services\NowPlaying;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StationsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->role = UserRole::Admin;
        $user->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $user->save();

        return $user;
    }

    public function test_seeder_imports_twelve_stations_and_is_idempotent(): void
    {
        $this->seed(StationSeeder::class);
        $this->seed(StationSeeder::class);

        $this->assertSame(12, Station::count());
        $star = Station::where('slug', 'star-888')->first();
        $this->assertSame('/star888xanthi', $star->now_playing_mount);
        $this->assertSame('88.8 FM', $star->frequency_label);
        $this->assertSame('100 FM', Station::where('slug', 'kral-fm-100')->first()->frequency_label);
    }

    public function test_seeder_does_not_overwrite_admin_edits(): void
    {
        $this->seed(StationSeeder::class);
        $star = Station::where('slug', 'star-888')->first();
        $this->travel(1)->minutes();
        $star->update(['slogan' => 'Νέο σλόγκαν']);

        $this->seed(StationSeeder::class);

        $this->assertSame('Νέο σλόγκαν', $star->fresh()->slogan);
    }

    public function test_now_playing_fixes_greek_encoding_and_splits_artist(): void
    {
        $this->seed(StationSeeder::class);
        Cache::flush();

        $mojibake = mb_convert_encoding('ΑΝΔΡΙΑΝΑ ΜΠΑΜΠΑΛΗ - ΜΑΖΙ ΘΕΟΣ', 'UTF-8', 'ISO-8859-1');
        Http::fake([
            'songs.live24.gr/*' => Http::response(json_encode(['icestats' => ['/star888xanthi' => ['title' => $mojibake]]])),
        ]);

        $data = app(NowPlaying::class)->all();

        $this->assertSame('ΑΝΔΡΙΑΝΑ ΜΠΑΜΠΑΛΗ', $data['star-888']['artist']);
        $this->assertSame('ΜΑΖΙ ΘΕΟΣ', $data['star-888']['title']);
    }

    public function test_now_playing_survives_provider_errors(): void
    {
        $this->seed(StationSeeder::class);
        Cache::flush();
        Http::fake(['songs.live24.gr/*' => Http::response('', 500)]);

        $this->getJson('/api/now-playing')
            ->assertOk()
            ->assertJsonPath('star-888.title', null);
    }

    public function test_admin_can_list_and_edit_stations(): void
    {
        $this->seed(StationSeeder::class);
        $this->actingAs($this->admin(), 'admin');

        Livewire::test(ListStations::class)->assertOk()->assertSee('Star 88.8');

        $star = Station::where('slug', 'star-888')->first();
        Livewire::test(EditStation::class, ['record' => $star->getRouteKey()])
            ->fillForm(['slogan' => 'Δοκιμή'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Δοκιμή', $star->fresh()->slogan);
    }

    public function test_customer_cannot_open_station_admin(): void
    {
        $customer = User::factory()->create();

        // Συνδεδεμένος στο site ≠ συνδεδεμένος στη διαχείριση
        $this->actingAs($customer)->get('/admin/stations')->assertRedirect('/admin/login');
        // Ακόμα κι αν κάποιος πελάτης περάσει στη σύνδεση της διαχείρισης, δεν έχει πρόσβαση
        $this->actingAs($customer, 'admin')->get('/admin/stations')->assertForbidden();
    }
}
