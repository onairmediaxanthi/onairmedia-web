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

// Παραγγελίες: έναρξη και λήξη καμπανιών με βάση τις ημερομηνίες, με ενημέρωση του πελάτη
Artisan::command('orders:advance', function () {
    [$started, $finished] = app(\App\Services\Orders\OrderService::class)->advanceByDate();
    $this->info("Ξεκίνησαν: {$started} · Ολοκληρώθηκαν: {$finished}");
})->purpose('Advance scheduled/running orders by date');
\Illuminate\Support\Facades\Schedule::command('orders:advance')->dailyAt('07:05')->withoutOverlapping()->onOneServer();

// Τι παίζουν οι σταθμοί: καταγραφή κάθε λεπτό, Top 40 κάθε Δευτέρα, καθαρισμός κάθε βράδυ
Artisan::command('airplay:record', function () {
    $r = app(\App\Services\Airplay\AirplayRecorder::class)->record();
    $this->info("Καταγράφηκαν: {$r['recorded']} · αγνοήθηκαν: {$r['skipped']}");
})->purpose('Record what each station is playing');
Artisan::command('airplay:charts {period=week : week ή month} {--date= : μια μέρα της περιόδου (Y-m-d)· κενό = η περίοδος που μόλις έκλεισε}', function () {
    $period = $this->argument('period') === 'month' ? 'month' : 'week';
    $date = $this->option('date') ? \Illuminate\Support\Carbon::parse($this->option('date')) : null;
    foreach (app(\App\Services\Airplay\ChartBuilder::class)->buildAll($period, $date) as $name => $n) {
        $this->line("{$name}: {$n} θέσεις");
    }
})->purpose('Build Top 40 per station (week or month)');
Artisan::command('airplay:prune', function () {
    $this->info(json_encode(app(\App\Services\Airplay\AirplayPruner::class)->prune(), JSON_UNESCAPED_UNICODE));
})->purpose('Delete old airplay history');
\Illuminate\Support\Facades\Schedule::command('airplay:record')->everyMinute()->withoutOverlapping(5);
\Illuminate\Support\Facades\Schedule::command('airplay:charts week')->weeklyOn(1, '00:20')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('airplay:charts month')->monthlyOn(1, '00:40')->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('airplay:prune')->dailyAt('04:15')->withoutOverlapping();
