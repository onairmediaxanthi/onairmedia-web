<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Υπενθυμίσεις προσφορών κάθε πρωί (χρειάζεται cron: * * * * * php artisan schedule:run)
\Illuminate\Support\Facades\Schedule::command('quotes:remind')->dailyAt('10:00')->withoutOverlapping()->onOneServer();

// Μαζικές αποστολές προσφορών, σε δόσεις κάθε λεπτό
\Illuminate\Support\Facades\Schedule::command('campaigns:send')->everyMinute()->withoutOverlapping(10);
