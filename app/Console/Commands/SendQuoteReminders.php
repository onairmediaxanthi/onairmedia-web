<?php

namespace App\Console\Commands;

use App\Mail\QuoteReminder;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\CustomerEvents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Υπενθυμίσεις για προσφορές που περιμένουν στον λογαριασμό, και σήμανση όσων έληξαν */
class SendQuoteReminders extends Command
{
    protected $signature = 'quotes:remind';

    protected $description = 'Στέλνει υπενθυμίσεις για αποθηκευμένες προσφορές και σημαίνει όσες έληξαν';

    public function handle(): int
    {
        $expired = Quote::query()->where('status', 'saved')->whereDate('valid_until', '<', today())->update(['status' => 'expired']);

        $every = (int) Setting::get('quote_reminder_days', 3);
        $max = (int) Setting::get('quote_reminder_max', 3);
        $sent = 0;

        if ($every > 0 && $max > 0) {
            $due = now()->subDays($every)->addHour(); // ανοχή, ώστε η ώρα αποστολής να μη «γλιστράει» κάθε μέρα
            Quote::query()
                ->where('status', 'saved')->where('reminders_off', false)
                ->where('reminders_sent', '<', $max)
                ->whereDate('valid_until', '>=', today())
                ->where(fn ($q) => $q->where('last_reminded_at', '<=', $due)
                    ->orWhere(fn ($q) => $q->whereNull('last_reminded_at')->where('created_at', '<=', $due)))
                ->whereHas('user', fn ($q) => $q->whereNotNull('email_verified_at'))
                ->with('user.business')
                ->chunkById(100, function ($quotes) use (&$sent) {
                    foreach ($quotes as $quote) {
                        try {
                            Mail::to($quote->user->email)->send(new QuoteReminder($quote));
                        } catch (Throwable $e) {
                            $this->warn($quote->number.': '.$e->getMessage());

                            continue;
                        }
                        $quote->forceFill(['reminders_sent' => $quote->reminders_sent + 1, 'last_reminded_at' => now()])->save();
                        CustomerEvents::record('reminder_sent', $quote->user, $quote, ['n' => $quote->reminders_sent]);
                        $sent++;
                    }
                });
        }

        $this->info("Υπενθυμίσεις: {$sent} · έληξαν: {$expired}");

        return self::SUCCESS;
    }
}
