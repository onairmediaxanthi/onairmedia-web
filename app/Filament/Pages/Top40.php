<?php

namespace App\Filament\Pages;

use App\Models\Chart;
use App\Models\Song;
use App\Models\Station;
use App\Services\Airplay\AirplayPruner;
use App\Services\Airplay\ChartBuilder;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

/**
 * Top 40 ανά σταθμό: εβδομάδα (πρόσφατες), μήνας (μόνιμο αρχείο), έτος (από τους μήνες),
 * ζωντανή εικόνα της τρέχουσας περιόδου και αναζήτηση τραγουδιού στο αρχείο.
 */
class Top40 extends Page
{
    use \App\Filament\Concerns\PageRestrictedToArea;

    protected static string $area = 'media';

    protected static ?string $slug = 'mesa/top40';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Top 40';

    protected static ?string $title = 'Top 40 ανά σταθμό';

    protected static string|\UnitEnum|null $navigationGroup = 'Μέσα';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.airplay.top40';

    public ?int $station = null;

    /** week | month | year */
    public string $period = 'month';

    /** 'live' ή Y-m-d (εβδομάδα/μήνας) ή έτος */
    public string $which = 'live';

    public string $search = '';

    public function mount(): void
    {
        $this->station = (int) request()->query('stathmos') ?: Station::query()->where('airplay_enabled', true)->orderBy('sort')->value('id');
    }

    public function updatedPeriod(): void
    {
        $st = Station::query()->find($this->station);
        $this->which = $this->period === 'year' ? (string) (($st ? ChartBuilder::years($st)[0] ?? null : null) ?? now()->year) : 'live';
    }

    public function updatedStation(): void
    {
        $this->updatedPeriod();
    }

    protected function getHeaderActions(): array
    {
        $stations = fn () => Station::query()->orderBy('sort')->pluck('name', 'id')->all();

        return [
            Action::make('build')->label('Ξαναβγάλε τη λίστα')->icon('heroicon-o-arrow-path')->color('gray')
                ->modalDescription('Οι λίστες βγαίνουν αυτόματα: της εβδομάδας κάθε Δευτέρα, του μήνα την 1η. Χρήσιμο αν διορθώσατε ή συγχωνεύσατε τραγούδια.')
                ->schema([ToggleButtons::make('period')->label('Ποια')->inline()->required()->default('week')
                    ->options(['week' => 'Προηγούμενη εβδομάδα', 'month' => 'Προηγούμενος μήνας'])])
                ->action(function (array $data) {
                    $r = app(ChartBuilder::class)->buildAll($data['period']);
                    Notification::make()->success()->title('Η λίστα ξαναβγήκε')->body(collect($r)->map(fn ($n, $s) => $s.': '.$n)->implode(' · '))->send();
                }),
            Action::make('wipeCharts')->label('Διαγραφή από το αρχείο')->icon('heroicon-o-trash')->color('danger')
                ->modalHeading('Διαγραφή Top 40 από το αρχείο')
                ->modalDescription('Σβήνει τα Top 40 (μηνών και εβδομάδων) έως και τον μήνα που διαλέγετε. Δεν αναιρείται.')
                ->modalSubmitActionLabel('Οριστική διαγραφή')
                ->schema([
                    Select::make('station_id')->label('Σταθμός')->options($stations)->placeholder('Όλοι οι σταθμοί'),
                    Select::make('until')->label('Έως και')->required()
                        ->options(fn () => Chart::query()->where('period', 'month')->distinct()->orderByDesc('period_start')->pluck('period_start')
                            ->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => Chart::periodLabel('month', Carbon::parse($d))])->all()),
                    Checkbox::make('sure')->label('Καταλαβαίνω ότι η διαγραφή είναι οριστική')->accepted()
                        ->validationMessages(['accepted' => 'Επιβεβαιώστε τη διαγραφή.']),
                ])
                ->action(function (array $data) {
                    $r = app(AirplayPruner::class)->wipeCharts($data['station_id'] ? (int) $data['station_id'] : null, $data['until']);
                    Notification::make()->success()->title('Διαγράφηκαν '.$r['charts'].' θέσεις Top 40')->send();
                    $this->updatedPeriod();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $station = Station::query()->find($this->station);
        $options = [];
        $rows = collect();
        $note = null;

        if ($station) {
            if ($this->period === 'year') {
                foreach (ChartBuilder::years($station) as $y) {
                    $options[(string) $y] = 'Έτος '.$y;
                }
                if ((int) $this->which) {
                    $rows = ChartBuilder::year($station, (int) $this->which);
                    $note = 'Από τα μηνιαία Top 40 της χρονιάς: σύνολο μεταδόσεων, σε πόσους μήνες μπήκε και καλύτερη θέση.';
                }
            } else {
                $start = ChartBuilder::start($this->period, now());
                $options['live'] = ($this->period === 'month' ? 'Τρέχων μήνας' : 'Τρέχουσα εβδομάδα').' (ζωντανά)';
                foreach (ChartBuilder::available($station, $this->period) as $d) {
                    $options[$d] = Chart::periodLabel($this->period, Carbon::parse($d));
                }
                if ($this->which === 'live') {
                    $rank = ChartBuilder::ranking($station, $start, now());
                    $songs = Song::query()->whereIn('id', $rank->pluck('song_id'))->get()->keyBy('id');
                    $last = ChartBuilder::latest($station, $this->period);
                    $prev = $last ? Chart::query()->where('station_id', $station->id)->where('period', $this->period)->whereDate('period_start', $last->toDateString())->pluck('position', 'song_id') : collect();
                    $rows = $rank->values()->map(fn ($r, $i) => (object) ['position' => $i + 1, 'song' => $songs[$r->song_id] ?? null, 'plays' => (int) $r->plays,
                        'prev_position' => $prev[$r->song_id] ?? null, 'periods_on' => null, 'peak' => null]);
                    $note = 'Ζωντανή κατάταξη από '.$start->format('d/m').': αλλάζει όσο παίζουν τραγούδια. Η επίσημη λίστα βγαίνει στο τέλος της περιόδου.';
                } else {
                    $rows = Chart::query()->with('song')->where('station_id', $station->id)->where('period', $this->period)->whereDate('period_start', $this->which)->orderBy('position')->get();
                }
            }
        }

        return [
            'stations' => Station::query()->where('airplay_enabled', true)->orderBy('sort')->get(),
            'current' => $station,
            'options' => $options,
            'rows' => $rows,
            'note' => $note,
            'found' => ChartBuilder::search($this->search),
        ];
    }
}
