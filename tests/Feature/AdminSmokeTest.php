<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Κάθε σελίδα της διαχείρισης ανοίγει χωρίς σφάλμα για τον διαχειριστή */
class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_page_opens(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::factory()->create();
        $admin->role = UserRole::Admin;
        $admin->app_authentication_secret = 'JBSWY3DPEHPK3PXP';
        $admin->save();
        $this->actingAs($admin, 'admin');

        $panel = Filament::getPanel('admin');
        $urls = ['/admin'];
        foreach ($panel->getResources() as $resource) {
            $urls[] = $resource::getUrl('index', panel: 'admin');
            if ($resource::hasPage('create') && $resource::canCreate()) {
                $urls[] = $resource::getUrl('create', panel: 'admin');
            }
        }
        foreach ($panel->getPages() as $page) {
            $urls[] = $page::getUrl(panel: 'admin');
        }

        foreach (array_unique($urls) as $url) {
            $status = $this->get($url)->status();
            $this->assertSame(200, $status, "Η σελίδα {$url} επέστρεψε {$status}");
        }
    }
}
