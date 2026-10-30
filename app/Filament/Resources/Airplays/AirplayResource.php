<?php

namespace App\Filament\Resources\Airplays;

use App\Filament\Resources\Airplays\Pages\ListAirplays;
use App\Models\Airplay;
use App\Models\Station;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Ιστορικό «τι έπαιξε» ανά σταθμό */
class AirplayResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'media';

    protected static ?string $model = Airplay::class;

    protected static ?string $slug = 'mesa/ti-paizoun';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static ?string $navigationLabel = 'Τι παίζουν';

    protected static ?string $modelLabel = 'μετάδοση';

    protected static ?string $pluralModelLabel = 'Τι παίζουν οι σταθμοί';

    protected static string|\UnitEnum|null $navigationGroup = 'Μέσα';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('played_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['song', 'station']))
            ->poll('60s')
            ->columns([
                TextColumn::make('played_at')->label('Ώρα')->dateTime('d/m H:i')->sortable()
                    ->description(fn (Airplay $r) => $r->played_at->locale('el')->isoFormat('dddd')),
                TextColumn::make('station.name')->label('Σταθμός')->badge()
                    ->color(fn (Airplay $r) => \Filament\Support\Colors\Color::hex($r->station?->color ?: '#c80c92')),
                TextColumn::make('song.title')->label('Τραγούδι')->weight('bold')->wrap()
                    ->description(fn (Airplay $r) => $r->song?->artist)
                    ->searchable(query: fn ($query, string $search) => $query->whereHas('song', fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('artist', 'like', "%{$search}%"))),
            ])
            ->filters([
                SelectFilter::make('station_id')->label('Σταθμός')->options(fn () => Station::query()->orderBy('sort')->pluck('name', 'id')),
                Filter::make('period')->label('Περίοδος')
                    ->schema([DatePicker::make('from')->label('Από')->native(false)->displayFormat('d/m/Y'), DatePicker::make('to')->label('Έως')->native(false)->displayFormat('d/m/Y')])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'] ?? null, fn ($q, $d) => $q->where('played_at', '>=', \Illuminate\Support\Carbon::parse($d)->startOfDay()))
                        ->when($data['to'] ?? null, fn ($q, $d) => $q->where('played_at', '<=', \Illuminate\Support\Carbon::parse($d)->endOfDay())))
                    ->indicateUsing(fn (array $data) => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Από '.\Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y') : null,
                        ($data['to'] ?? null) ? 'Έως '.\Illuminate\Support\Carbon::parse($data['to'])->format('d/m/Y') : null,
                    ]))),
            ])
            ->recordActions([
                Action::make('ignore')->label('Δεν είναι τραγούδι')->icon('heroicon-o-no-symbol')->color('gray')->iconButton()
                    ->tooltip('Jingle, διαφήμιση ή εκπομπή: να μη γράφεται και να μη μπαίνει στο Top 40')
                    ->requiresConfirmation()->modalHeading(fn (Airplay $r) => 'Εξαίρεση: '.$r->song?->label())
                    ->modalDescription('Δεν θα καταγράφεται πια σε κανέναν σταθμό και βγαίνει από τα Top 40. Οι παλιές μεταδόσεις του σβήνονται.')
                    ->action(function (Airplay $r) {
                        $song = $r->song;
                        $song->forceFill(['ignored' => true])->save();
                        $song->airplays()->delete();
                        $song->daily()->delete();
                        Notification::make()->success()->title('Εξαιρέθηκε: '.$song->label())->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAirplays::route('/')];
    }
}
