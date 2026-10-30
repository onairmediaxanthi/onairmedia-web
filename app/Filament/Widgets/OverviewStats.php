<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\CallbackRequests\CallbackRequestResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Business;
use App\Models\CallbackRequest;
use App\Models\Quote;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Αρχική πίνακα ελέγχου: τι περιμένει απάντηση και πώς πάμε */
class OverviewStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    public static function canView(): bool
    {
        return (bool) Filament::auth()->user()?->canAccessArea('sales');
    }

    protected function getStats(): array
    {
        $week = now()->subDays(7);
        $eur = fn ($v) => number_format((float) $v, 0, ',', '.').' €';

        $newCallbacks = CallbackRequest::query()->where('status', 'new')->count();
        $oldestCallback = CallbackRequest::query()->where('status', 'new')->oldest()->value('created_at');
        $toConfirm = \App\Models\Order::query()->where('stage', 'received');
        $deposits = \App\Models\Order::query()->where('stage', 'payment_review')->count();
        $unpaid = \App\Models\Order::query()->whereNull('paid_at')->whereIn('stage', \App\Models\Order::OPEN);
        $openQuotes = Quote::query()->where('status', 'saved')->whereDate('valid_until', '>=', today());
        $customers = Business::query()->count();

        return [
            Stat::make('Αιτήματα «Να σας καλέσουμε»', $newCallbacks)
                ->description($newCallbacks ? 'σε αναμονή από '.$oldestCallback?->locale('el')->diffForHumans() : 'κανένα σε αναμονή')
                ->descriptionIcon('heroicon-m-phone')->color($newCallbacks ? 'warning' : 'success')
                ->url(CallbackRequestResource::getUrl('index', ['filters' => ['status' => ['value' => 'new']]])),
            Stat::make('Παραγγελίες για επιβεβαίωση', (clone $toConfirm)->count())
                ->description($eur((clone $toConfirm)->sum('total')).' με ΦΠΑ'.($deposits ? ' · '.$deposits.' κατάθεση/εις για έλεγχο' : ''))
                ->descriptionIcon('heroicon-m-shopping-bag')->color((clone $toConfirm)->exists() || $deposits ? 'danger' : 'success')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('index')),
            Stat::make('Ανεξόφλητες παραγγελίες', (clone $unpaid)->count())
                ->description('Υπόλοιπο '.$eur((clone $unpaid)->get()->sum(fn ($o) => $o->balance())).' με ΦΠΑ')
                ->descriptionIcon('heroicon-m-banknotes')->color((clone $unpaid)->exists() ? 'warning' : 'success')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('index', ['activeTab' => 'unpaid'])),
            Stat::make('Ανοιχτές προσφορές', (clone $openQuotes)->count())
                ->description($eur((clone $openQuotes)->sum('total')).' σε ισχύ')
                ->descriptionIcon('heroicon-m-document-text')->color('info')
                ->url(QuoteResource::getUrl('index')),
            Stat::make('Νέες προσφορές · 7 ημέρες', Quote::query()->where('created_at', '>=', $week)->count())
                ->description(Quote::query()->where('created_at', '>=', $week)->where('source', '!=', 'staff')->count().' από πελάτες στο site')
                ->descriptionIcon('heroicon-m-sparkles')->color('primary')
                ->chart($this->daily(Quote::query())),
            Stat::make('Σύνολο πελατών', $customers)
                ->description(Business::query()->where('created_at', '>=', $week)->count().' νέες σε 7 ημέρες · '.Business::query()->whereNotNull('marketing_consent_at')->count().' με συναίνεση για ενημερωτικά')
                ->descriptionIcon('heroicon-m-user-group')->color('primary')
                ->chart($this->daily(Business::query()))
                ->url(BusinessResource::getUrl('index')),
            Stat::make('Καμπάνιες σε προβολή', \App\Models\Order::query()->where('stage', 'running')->count())
                ->description(\App\Models\Order::query()->where('stage', 'scheduled')->count().' προγραμματισμένες')
                ->descriptionIcon('heroicon-m-signal')->color('success')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('index', ['activeTab' => 'progress'])),
            Stat::make('Αιτήματα επικοινωνίας · 30 ημέρες', CallbackRequest::query()->where('created_at', '>=', now()->subDays(30))->count())
                ->description(CallbackRequest::query()->where('created_at', '>=', now()->subDays(30))->whereNotNull('business_id')->count().' από υπάρχοντες πελάτες')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')->color('gray')
                ->chart($this->daily(CallbackRequest::query())),
        ];
    }

    /** πλήθος ανά ημέρα για τις τελευταίες 14 ημέρες (μικρό γράφημα) */
    private function daily($query): array
    {
        $rows = $query->where('created_at', '>=', today()->subDays(13))->pluck('created_at')
            ->countBy(fn ($d) => $d->format('Y-m-d'));

        return collect(range(13, 0))->map(fn ($i) => $rows[today()->subDays($i)->format('Y-m-d')] ?? 0)->all();
    }
}
