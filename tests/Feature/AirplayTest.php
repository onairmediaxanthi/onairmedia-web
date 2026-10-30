<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Airplays\Pages\ListAirplays;
use App\Models\Airplay;
use App\Models\Chart;
use App\Models\Setting;
use App\Models\Song;
use App\Models\Station;
use App\Models\User;
use App\Services\Airplay\AirplayPruner;
use App\Services\Airplay\AirplayRecorder;
use App\Services\Airplay\ChartBuilder;
use Database\Seeders\StationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AirplayTest extends TestCase
{
    use RefreshDatabase;

    protected Station $star;

    protected string $now = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StationSeeder::class);
        // Μόνο ένας σταθμός με mount, για απλότητα
        Station::query()->update(['now_playing_mount' => null]);
        $this->star = Station::query()->orderBy('sort')->first();
        $this->star->update(['now_playing_mount' => '/star888xanthi', 'airplay_enabled' => true, 'chart_public' => true]);
        Http::fake(['songs.live24.gr/*' => fn () => Http::response(json_encode(['icestats' => ['/star888xanthi' => ['title' => $this->now]]]))]);
    }

    protected function playing(string $title, ?Carbon $at = null): array
    {
        $this->now = $title;
        Cache::flush();

        return app(AirplayRecorder::class)->record($at ?? now());
    }

    public function test_records_each_new_song_once_and_skips_non_songs(): void
    {
        $this->playing('ΑΝΔΡΙΑΝΑ ΜΠΑΜΠΑΛΗ - ΚΑΤΙ ΣΑΝ ΕΣΕΝΑ');
        $this->playing('ΑΝΔΡΙΑΝΑ ΜΠΑΜΠΑΛΗ - ΚΑΤΙ ΣΑΝ ΕΣΕΝΑ');           // ίδιο λεπτό μετά: όχι διπλό
        $this->playing('Star 88.8');                                   // όνομα σταθμού / χωρίς καλλιτέχνη
        $this->playing('ON AIR - Πρωινή εκπομπή');                     // λέξη-κλειδί
        $this->playing('Ανδριάνα Μπαμπαλή - Κάτι σαν εσένα (Radio Edit)'); // ίδιο τραγούδι, άλλη γραφή
        $this->playing('Dua Lipa feat. X - Houdini');

        $this->assertSame(1, Song::query()->where('title', 'like', '%ΚΑΤΙ%')->count(), 'ίδιο τραγούδι με άλλη γραφή = ένα');
        $this->assertSame(3, Airplay::query()->count()); // Μπαμπαλή ×2 (ξαναμπήκε μετά από άλλο), Dua Lipa ×1
        $this->assertEquals(2, DB::table('airplay_daily')->where('song_id', Song::query()->where('title', 'like', '%ΚΑΤΙ%')->value('id'))->value('plays'));
        $this->assertNotNull($this->star->refresh()->last_seen_at);

        // Εξαιρεμένο τραγούδι δεν γράφεται
        Song::query()->where('title', 'Houdini')->update(['ignored' => true]);
        $this->playing('Κάτι άλλο - Ενδιάμεσο');
        $this->playing('Dua Lipa - Houdini');
        $this->assertSame(1, Airplay::query()->whereHas('song', fn ($q) => $q->where('title', 'Houdini'))->count());

        // Γενικός διακόπτης
        Setting::putMany(['airplay_enabled' => false]);
        $this->assertSame(['recorded' => 0, 'skipped' => 0], $this->playing('Νέος - Τίτλος'));
    }

    public function test_weekly_chart_with_movement_weeks_and_peak(): void
    {
        $a = Song::query()->create(['artist' => 'Α', 'title' => 'Ένα', 'norm' => Song::normalize('Α', 'Ένα')]);
        $b = Song::query()->create(['artist' => 'Β', 'title' => 'Δύο', 'norm' => Song::normalize('Β', 'Δύο')]);
        $c = Song::query()->create(['artist' => 'Γ', 'title' => 'Τρία', 'norm' => Song::normalize('Γ', 'Τρία')]);
        $w1 = Carbon::parse('2026-10-12'); // Δευτέρα
        $w2 = Carbon::parse('2026-10-19');
        $add = fn ($song, $day, $n) => DB::table('airplay_daily')->insert(['station_id' => $this->star->id, 'song_id' => $song->id, 'day' => $day, 'plays' => $n]);
        $add($a, '2026-10-13', 10); $add($b, '2026-10-14', 4); $add($c, '2026-10-15', 1); // Γ κάτω από το ελάχιστο (2)
        $add($a, '2026-10-20', 3); $add($b, '2026-10-21', 8); $add($c, '2026-10-22', 6);

        $builder = app(ChartBuilder::class);
        $this->assertSame(2, $builder->build($this->star, 'week', $w1));
        $this->assertSame(3, $builder->build($this->star, 'week', $w2->copy()->addDays(3))); // οποιαδήποτε μέρα της εβδομάδας

        $w = Chart::query()->where('station_id', $this->star->id)->where('period', 'week')->whereDate('period_start', '2026-10-19')->orderBy('position')->get()->keyBy('song_id');
        $this->assertSame([1, 2, 3], [$w[$b->id]->position, $w[$c->id]->position, $w[$a->id]->position]);
        $this->assertSame('up', $w[$b->id]->movement());
        $this->assertSame('new', $w[$c->id]->movement());
        $this->assertSame('down', $w[$a->id]->movement());
        $this->assertSame(2, $w[$a->id]->periods_on);
        $this->assertSame(1, $w[$a->id]->peak);

        // Μήνας: Οκτώβριος, και έτος από τους μήνες
        $this->assertSame(3, $builder->build($this->star, 'month', Carbon::parse('2026-10-31')));
        $m = Chart::query()->where('period', 'month')->orderBy('position')->get();
        $this->assertSame('2026-10-01', $m[0]->period_start->toDateString());
        $this->assertSame($a->id, $m[0]->song_id); // 13 μεταδόσεις
        $this->assertSame('Οκτώβριος 2026', Chart::periodLabel('month', $m[0]->period_start));
        $year = ChartBuilder::year($this->star, 2026);
        $this->assertSame([$a->id, $b->id, $c->id], $year->map(fn ($r) => $r->song->id)->all());
        $this->assertSame([2026], ChartBuilder::years($this->star));
        $this->assertCount(1, ChartBuilder::search('Δύο'));

        // Στη σελίδα του σταθμού: εβδομάδα και μήνας
        $this->get(route('stations.show', $this->star))->assertOk()->assertSee('Τα πιο αγαπημένα')->assertSee('Του μήνα')->assertSee('Οκτώβριος 2026')->assertSee('ΝΕΟ');
        $this->star->update(['chart_public' => false]);
        Cache::flush();
        $this->get(route('stations.show', $this->star))->assertDontSee('Τα πιο αγαπημένα');

        // Αυτόματος καθαρισμός: οι εβδομάδες σβήνονται μετά από 8, ο μήνας μένει
        $this->travelTo(Carbon::parse('2027-03-10'));
        app(AirplayPruner::class)->prune();
        $this->assertSame(0, Chart::query()->where('period', 'week')->count());
        $this->assertSame(3, Chart::query()->where('period', 'month')->count());
        $this->assertSame(0, DB::table('airplay_daily')->count());
        $this->assertSame(3, Song::query()->count()); // τα τραγούδια του αρχείου μένουν

        // Διαγραφή αρχείου μόνο από τη διαχείριση
        app(AirplayPruner::class)->wipeCharts(null, '2026-10-01');
        $this->assertSame(0, Chart::query()->count());
        $this->assertSame(0, Song::query()->count());
    }

    public function test_pruning_and_manual_wipe(): void
    {
        $this->playing('Παλιός - Τίτλος', now()->subDays(120));
        $this->playing('Νέος - Τίτλος', now());
        $this->assertSame(2, Airplay::query()->count());

        $r = app(AirplayPruner::class)->prune();
        $this->assertSame(1, $r['airplays']);
        $this->assertSame(1, Airplay::query()->count());

        // Από τη διαχείριση: «όλο το ιστορικό» του σταθμού
        $staff = User::factory()->create();
        $staff->role = UserRole::Studio; // περιοχή «Μέσα»
        $staff->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $staff->save();
        $this->actingAs($staff, 'admin');
        $this->get('/admin/mesa/ti-paizoun')->assertOk()->assertSee('Τώρα στον αέρα');
        $this->get('/admin/mesa/top40')->assertOk();
        $this->get('/admin/mesa/tragoudia')->assertOk();

        Livewire::test(ListAirplays::class)->callAction('wipe', ['station_id' => null, 'scope' => 'all', 'sure' => false])->assertHasActionErrors(['sure']);
        Livewire::test(ListAirplays::class)->callAction('wipe', ['station_id' => $this->star->id, 'scope' => 'all', 'sure' => true])->assertHasNoActionErrors();
        Livewire::test(\App\Filament\Pages\Top40::class)->set('period', 'year')->assertOk()->set('search', 'Τίτλος')->assertOk();
        $this->assertSame(0, Airplay::query()->count());
        $this->assertSame(0, Song::query()->count()); // ορφανά τραγούδια σβήνονται
        $this->assertNull($this->star->refresh()->last_title);

        // Οι πωλήσεις δεν βλέπουν τα «Μέσα»
        $sales = User::factory()->create();
        $sales->role = UserRole::Sales;
        $sales->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $sales->save();
        $this->actingAs($sales, 'admin')->get('/admin/mesa/ti-paizoun')->assertForbidden();
    }

    public function test_csv_export(): void
    {
        $this->playing('Μαρινέλλα - Άνοιξε πέτρα');
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $admin->save();
        $this->actingAs($admin, 'admin');

        Livewire::test(ListAirplays::class)
            ->callAction('export', ['station_id' => null, 'from' => today()->toDateString(), 'to' => today()->toDateString(), 'mode' => 'plays'])
            ->assertFileDownloaded();
    }

    public function test_merge_songs(): void
    {
        $this->playing('Α - Ένα');
        $this->playing('Α - Δύο');
        $this->playing('Α - Ένα (2)'); // «(2)» αφαιρείται στην κανονικοποίηση → ίδιο με «Ένα»
        $one = Song::query()->where('title', 'Ένα')->first();
        $two = Song::query()->where('title', 'Δύο')->first();
        \App\Filament\Resources\Songs\SongResource::merge($two, $one);
        $this->assertSame(1, Song::query()->count());
        $this->assertEquals(3, DB::table('airplay_daily')->where('song_id', $one->id)->sum('plays'));
    }
}
