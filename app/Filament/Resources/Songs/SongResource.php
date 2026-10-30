<?php

namespace App\Filament\Resources\Songs;

use App\Filament\Resources\Songs\Pages\EditSong;
use App\Filament\Resources\Songs\Pages\ListSongs;
use App\Models\Song;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/** Το «λεξικό» τραγουδιών: διόρθωση ονομάτων, συγχώνευση διπλών, εξαίρεση jingles */
class SongResource extends Resource
{
    use \App\Filament\Concerns\RestrictedToArea;

    protected static string $area = 'media';

    protected static ?string $model = Song::class;

    protected static ?string $slug = 'mesa/tragoudia';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Τραγούδια';

    protected static ?string $modelLabel = 'τραγούδι';

    protected static ?string $pluralModelLabel = 'Τραγούδια';

    protected static string|\UnitEnum|null $navigationGroup = 'Μέσα';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('artist')->label('Καλλιτέχνης')->maxLength(200),
            TextInput::make('title')->label('Τίτλος')->required()->maxLength(200),
            Toggle::make('ignored')->label('Δεν είναι τραγούδι (jingle, διαφήμιση, εκπομπή)')
                ->helperText('Δεν καταγράφεται πια και δεν μπαίνει στο Top 40.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->withSum('daily as plays_total', 'plays')
                ->withMax('daily as last_day', 'day'))
            ->defaultSort('plays_total', 'desc')
            ->columns([
                TextColumn::make('title')->label('Τραγούδι')->weight('bold')->wrap()->description(fn (Song $r) => $r->artist)
                    ->searchable(['title', 'artist']),
                TextColumn::make('plays_total')->label('Μεταδόσεις')->numeric(locale: 'el')->sortable()->placeholder('0'),
                TextColumn::make('last_day')->label('Τελευταία')->date('d/m/Y')->sortable()->placeholder('—'),
                ToggleColumn::make('ignored')->label('Εξαίρεση'),
            ])
            ->filters([TernaryFilter::make('ignored')->label('Εξαιρέσεις')->trueLabel('Μόνο εξαιρέσεις')->falseLabel('Μόνο τραγούδια')])
            ->recordActions([
                EditAction::make()->label('Διόρθωση'),
                Action::make('merge')->label('Συγχώνευση')->icon('heroicon-o-arrows-pointing-in')->color('gray')
                    ->modalDescription('Όταν το ίδιο τραγούδι γράφεται με δύο τρόπους. Οι μεταδόσεις αυτού μεταφέρονται στο τραγούδι που διαλέγετε, και αυτό σβήνεται.')
                    ->schema(fn (Song $record) => [
                        Select::make('target')->label('Συγχώνευση στο')->required()->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Song::query()->whereKeyNot($record->id)
                                ->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('artist', 'like', "%{$search}%"))
                                ->limit(30)->get()->mapWithKeys(fn (Song $s) => [$s->id => $s->label()]))
                            ->getOptionLabelUsing(fn ($value) => Song::query()->find($value)?->label()),
                    ])
                    ->action(function (array $data, Song $record) {
                        $target = Song::query()->findOrFail($data['target']);
                        self::merge($record, $target);
                        Notification::make()->success()->title('Συγχωνεύτηκε στο «'.$target->label().'»')->send();
                    }),
            ]);
    }

    /** Μεταφέρει όλες τις μεταδόσεις και τα Top 40 του $from στο $to και σβήνει το $from */
    public static function merge(Song $from, Song $to): void
    {
        DB::transaction(function () use ($from, $to) {
            DB::table('airplays')->where('song_id', $from->id)->update(['song_id' => $to->id]);
            foreach (DB::table('airplay_daily')->where('song_id', $from->id)->get() as $d) {
                DB::table('airplay_daily')->upsert(
                    [['station_id' => $d->station_id, 'song_id' => $to->id, 'day' => $d->day, 'plays' => $d->plays]],
                    ['station_id', 'day', 'song_id'],
                    ['plays' => DB::raw('plays + '.(int) $d->plays)],
                );
            }
            DB::table('airplay_daily')->where('song_id', $from->id)->delete();
            DB::table('charts')->where('song_id', $from->id)->update(['song_id' => $to->id]);
            $from->delete();
        });
    }

    public static function getPages(): array
    {
        return ['index' => ListSongs::route('/'), 'edit' => EditSong::route('/{record}/edit')];
    }
}
