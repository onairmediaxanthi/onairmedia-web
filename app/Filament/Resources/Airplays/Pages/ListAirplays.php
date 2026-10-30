<?php

namespace App\Filament\Resources\Airplays\Pages;

use App\Filament\Resources\Airplays\AirplayResource;
use App\Models\Station;
use App\Services\Airplay\AirplayPruner;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAirplays extends ListRecords
{
    protected static string $resource = AirplayResource::class;

    protected function getHeaderWidgets(): array
    {
        return [\App\Filament\Widgets\AirplayStatus::class];
    }

    protected function getHeaderActions(): array
    {
        $stations = fn () => Station::query()->orderBy('sort')->pluck('name', 'id')->all();

        return [
            Action::make('export')->label('Εξαγωγή CSV')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->modalDescription('Λίστα μεταδόσεων για μια περίοδο, π.χ. για τις αναφορές στους οργανισμούς πνευματικών δικαιωμάτων. Ανοίγει στο Excel.')
                ->fillForm(['from' => now()->startOfMonth()->subMonth()->toDateString(), 'to' => now()->startOfMonth()->subDay()->toDateString(), 'mode' => 'plays'])
                ->schema([
                    Select::make('station_id')->label('Σταθμός')->options($stations)->placeholder('Όλοι οι σταθμοί'),
                    DatePicker::make('from')->label('Από')->native(false)->displayFormat('d/m/Y')->required(),
                    DatePicker::make('to')->label('Έως')->native(false)->displayFormat('d/m/Y')->required()->afterOrEqual('from'),
                    ToggleButtons::make('mode')->label('Μορφή')->inline()->required()
                        ->options(['plays' => 'Κάθε μετάδοση με ώρα', 'totals' => 'Σύνολο ανά τραγούδι']),
                ])
                ->action(fn (array $data) => $this->export($data)),

            Action::make('wipe')->label('Διαγραφή ιστορικού')->icon('heroicon-o-trash')->color('danger')
                ->modalHeading('Διαγραφή ιστορικού τραγουδιών')
                ->modalDescription(function () {
                    $u = AirplayPruner::usage();

                    return 'Σήμερα: '.number_format($u['rows']['airplays'], 0, ',', '.').' μεταδόσεις, '.number_format($u['rows']['songs'], 0, ',', '.').' τραγούδια, περίπου '.number_format($u['mb'], 1, ',', '.').' MB. Η διαγραφή δεν αναιρείται. Το αρχείο Top 40 δεν επηρεάζεται.';
                })
                ->modalSubmitActionLabel('Οριστική διαγραφή')
                ->schema([
                    Select::make('station_id')->label('Σταθμός')->options($stations)->placeholder('Όλοι οι σταθμοί'),
                    ToggleButtons::make('scope')->label('Τι σβήνεται')->inline()->required()->live()->default('older')
                        ->options(['older' => 'Παλαιότερα από…', 'all' => 'Όλο το ιστορικό']),
                    TextInput::make('days')->label('Παλαιότερα από')->numeric()->integer()->minValue(1)->maxValue(3650)->suffix('ημέρες')->default(30)
                        ->visible(fn ($get) => $get('scope') === 'older')->required(fn ($get) => $get('scope') === 'older'),
                    Checkbox::make('sure')->label('Καταλαβαίνω ότι η διαγραφή είναι οριστική')->accepted()
                        ->validationMessages(['accepted' => 'Επιβεβαιώστε τη διαγραφή.']),
                ])
                ->action(function (array $data) {
                    $r = app(AirplayPruner::class)->wipe($data['station_id'] ? (int) $data['station_id'] : null, $data['scope'] === 'older' ? (int) $data['days'] : null);
                    Notification::make()->success()->title('Διαγράφηκαν '.number_format($r['airplays'], 0, ',', '.').' μεταδόσεις')->send();
                }),
        ];
    }

    protected function export(array $data): StreamedResponse
    {
        $from = Carbon::parse($data['from'])->startOfDay();
        $to = Carbon::parse($data['to'])->endOfDay();
        $station = $data['station_id'] ? Station::query()->find($data['station_id']) : null;
        $name = 'metadoseis-'.($station ? \Illuminate\Support\Str::slug($station->name) : 'oloi').'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($data, $from, $to, $station) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // για σωστά ελληνικά στο Excel
            if ($data['mode'] === 'totals') {
                fputcsv($out, ['Σταθμός', 'Καλλιτέχνης', 'Τίτλος', 'Μεταδόσεις'], ';');
                DB::table('airplay_daily')->join('songs', 'songs.id', '=', 'airplay_daily.song_id')->join('stations', 'stations.id', '=', 'airplay_daily.station_id')
                    ->whereBetween('day', [$from->toDateString(), $to->toDateString()])->when($station, fn ($q) => $q->where('airplay_daily.station_id', $station->id))
                    ->groupBy('stations.name', 'songs.artist', 'songs.title', 'stations.sort')->orderBy('stations.sort')->orderByRaw('SUM(plays) DESC')
                    ->select('stations.name', 'songs.artist', 'songs.title', DB::raw('SUM(plays) as plays'))
                    ->chunk(2000, function ($rows) use ($out) {
                        foreach ($rows as $r) {
                            fputcsv($out, [$r->name, $r->artist, $r->title, $r->plays], ';');
                        }
                    });
            } else {
                fputcsv($out, ['Ημερομηνία', 'Ώρα', 'Σταθμός', 'Καλλιτέχνης', 'Τίτλος'], ';');
                DB::table('airplays')->join('songs', 'songs.id', '=', 'airplays.song_id')->join('stations', 'stations.id', '=', 'airplays.station_id')
                    ->whereBetween('played_at', [$from, $to])->when($station, fn ($q) => $q->where('airplays.station_id', $station->id))
                    ->orderBy('airplays.id')->select('airplays.id', 'airplays.played_at', 'stations.name', 'songs.artist', 'songs.title')
                    ->chunkById(2000, function ($rows) use ($out) {
                        foreach ($rows as $r) {
                            $t = Carbon::parse($r->played_at);
                            fputcsv($out, [$t->format('d/m/Y'), $t->format('H:i'), $r->name, $r->artist, $r->title], ';');
                        }
                    }, 'airplays.id', 'id');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
