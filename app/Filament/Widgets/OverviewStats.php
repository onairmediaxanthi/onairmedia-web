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
        $submitted = Quote::query()->where('status', 'submitted');
        $openQuotes = Quote::query()->where('status', 'saved')->whereDate('valid_until', '>=', today());
        $customers = Business::query()->count();

        return [
            Stat::make('Αιτήματα «Να σας καλέσουμε»', $newCallbacks)
                ->description($newCallbacks ? 'σε αναμονή από '.$oldestCallback?->locale('el')->diffForHumans() : 'κανένα σε αναμονή')
                ->descriptionIcon('heroicon-m-phone')->color($newCallbacks ? 'warning' : 'success')
                ->url(CallbackRequestResource::getUrl('index', ['filters' => ['status' => ['value' => 'new']]])),
            Stat::make('Παραγγελίες για επιβεβαίωση', (clone $submitted)->count())
                ->description($eur((clone $submitted)->sum('total')).' με ΦΠΑ')
                ->descriptionIcon('heroicon-m-shopping-cart')->color((clone $submitted)->exists() ? 'danger' : 'success')
                ->url(QuoteResource::getUrl('index')),
            Stat::make('Ανοιχτές προσφορές', (clone $openQuotes)->count())
                ->description($eur((clone $openQuotes)->sum('total')).' σε ισχύ')
                ->descriptionIcon('heroicon-m-document-text')->color('info')
                ->url(QuoteResource::getUrl('index')),
            Stat::make('Νέες προσφορές · 7 ημέρες', Quote::query()->where('created_at', '>=', $week)->count())
                ->description(Quote::query()->where('created_at', '>=', $week)->where('source', '!=', 'staff')->count().' από πελάτες στο site')
                ->descriptionIcon('heroicon-m-sparkles')->color('primary')
                ->chart($this->daily(Quote::query())),
            Stat::make('Σύνολο πελατών', $customers)
                ->description(Business::query()->where('created_at', '>=', $week)->count().' νέες εγγραφές σε 7 ημέρες')
                ->descriptionIcon('heroicon-m-user-group')->color('primary')
                ->chart($this->daily(Business::query()))
                ->url(BusinessResource::getUrl('index')),
            Stat::make('Με συναίνεση για ενημερωτικά', Business::query()->whereNotNull('marketing_consent_at')->count())
                ->description($customers ? round(100 * Business::query()->whereNotNull('marketing_consent_at')->count() / $customers).'% των πελατών' : '—')
                ->descriptionIcon('heroicon-m-envelope')->color('gray'),
            Stat::make('Επιβεβαιωμένες · 30 ημέρες', Quote::query()->where('status', 'ordered')->where('updated_at', '>=', now()->subDays(30))->count())
                ->description($eur(Quote::query()->where('status', 'ordered')->where('updated_at', '>=', now()->subDays(30))->sum('total')).' με ΦΠΑ')
                ->descriptionIcon('heroicon-m-check-badge')->color('success'),
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
