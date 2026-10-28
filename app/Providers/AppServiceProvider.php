<?php

namespace App\Providers;

use App\Models\HomeSection;
use App\Models\Package;
use App\Models\Station;
use App\View\Composers\SiteComposer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.layouts.site', SiteComposer::class);

        // Τελευταία σύνδεση του προσωπικού στη διαχείριση
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function ($e) {
            if ($e->guard === 'admin' && $e->user instanceof \App\Models\User) {
                $e->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });

        // Αλλαγή σταθμού στη διαχείριση → ο player ενημερώνεται αμέσως
        Station::saved(fn () => Cache::forget('player:stations'));
        Station::deleted(fn () => Cache::forget('player:stations'));
        HomeSection::saved(fn () => Cache::forget('site:menu'));
        HomeSection::deleted(fn () => Cache::forget('site:menu'));
        Package::saved(fn () => Cache::forget('site:menu'));
        Package::deleted(fn () => Cache::forget('site:menu'));
    }
}
