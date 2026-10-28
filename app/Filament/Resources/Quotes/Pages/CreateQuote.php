<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\Concerns\BuildsQuotes;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\AdSlot;
use App\Models\Business;
use App\Models\Screen;
use App\Models\Setting;
use App\Models\Station;
use App\Services\Quotes\QuoteCalculator;
use App\Services\Quotes\QuoteService;
use App\Services\Quotes\Recommender;
use App\Support\Audience;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/**
 * Το Studio Καμπάνιας για το προσωπικό, μέσα στη διαχείριση:
 * ίδιος οδηγός, ίδια μηχανή τιμών, και επιπλέον έκπτωση και συνοδευτικό μήνυμα.
 */
class CreateQuote extends CreateRecord
{
    use BuildsQuotes;

    protected static string $resource = QuoteResource::class;

    protected static ?string $title = 'Νέα προσφορά για πελάτη';

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();
        $id = (int) request()->query('pelatis');
        $b = $id ? Business::query()->find($id) : null;
        $this->form->fill([
            'business_id' => $b?->id,
            'title' => $b ? 'Πρόταση προβολής · '.$b->displayName() : null,
            'starts_on' => today()->addDays(3)->format('Y-m-d'),
            'days' => 30, 'goal' => 'awareness', 'budget' => 1000, 'media' => ['radio', 'news', 'led'], 'material' => 'ready', 'seconds' => 30,
            'radio' => [], 'banners' => [], 'screens' => [], 'articles' => 0, 'creative' => [], 'discount' => 0,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $business = Business::query()->with('user')->findOrFail($data['business_id']);
        try {
            return app(QuoteService::class)->createForCustomer(
                Filament::auth()->user(), $business->user, $data['title'], self::specsFrom($data), (int) $data['days'],
                $data['starts_on'] ?? null, $data['intro'] ?? null,
                ['wizard' => collect($data)->only(['goal', 'budget', 'ages', 'profiles', 'gender', 'media', 'material', 'seconds'])->all()],
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Η προσφορά αποθηκεύτηκε. Πατήστε «Αποστολή στον πελάτη» όταν είναι έτοιμη.';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
