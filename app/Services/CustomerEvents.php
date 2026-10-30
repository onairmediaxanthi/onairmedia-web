<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\CompanyAlert;
use App\Models\CustomerActivity;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Κάθε ενέργεια πελάτη γράφεται στο ημερολόγιο και, ανάλογα με τις ρυθμίσεις,
 * ειδοποιεί το προσωπικό στη διαχείριση (καμπανάκι) και με email.
 * Αποτυχία email δεν σταματά ποτέ τη δουλειά του πελάτη.
 */
class CustomerEvents
{
    /** Ενέργειες που εμφανίζονται στο καμπανάκι της διαχείρισης */
    protected const BELL = ['registered', 'quote_saved', 'quote_changed', 'quote_submitted', 'quote_deleted', 'deposit_declared', 'files_uploaded', 'materials_done', 'order_message'];

    public static function record(string $type, ?User $user, ?Quote $quote = null, array $meta = []): CustomerActivity
    {
        $summary = self::summary($type, $user, $quote);

        $activity = CustomerActivity::query()->create([
            'user_id' => $user?->id, 'quote_id' => $quote?->id, 'type' => $type, 'summary' => $summary, 'meta' => $meta ?: null,
        ]);

        if (in_array($type, self::BELL, true)) {
            self::bell($type, $summary, $quote);
        }

        if (Setting::get('notify_'.$type, false)) {
            self::email($type, $summary, $user, $quote, $meta);
        }

        return $activity;
    }

    protected static function summary(string $type, ?User $user, ?Quote $quote): string
    {
        $who = $user?->business?->displayName() ?? $user?->name ?? 'Επισκέπτης';
        $label = CustomerActivity::TYPES[$type] ?? $type;
        $q = $quote ? ' '.$quote->number.' ('.$quote->money('total').')' : '';

        return mb_substr($who.' · '.$label.$q, 0, 255);
    }

    protected static function bell(string $type, string $summary, ?Quote $quote): void
    {
        try {
            $staff = User::query()->whereIn('role', [UserRole::Admin->value, UserRole::Sales->value])->whereNull('disabled_at')->get();
            if ($staff->isEmpty()) {
                return;
            }
            $n = Notification::make()->title(CustomerActivity::TYPES[$type] ?? $type)->body($summary)
                ->icon(match ($type) {
                    'registered' => 'heroicon-o-user-plus',
                    'quote_submitted' => 'heroicon-o-shopping-cart',
                    'deposit_declared' => 'heroicon-o-banknotes',
                    'order_message' => 'heroicon-o-chat-bubble-left-ellipsis',
                    'files_uploaded', 'materials_done' => 'heroicon-o-arrow-up-tray',
                    'quote_deleted' => 'heroicon-o-trash',
                    default => 'heroicon-o-document-text',
                })
                ->iconColor(in_array($type, ['quote_submitted', 'deposit_declared'], true) ? 'success' : ($type === 'quote_deleted' ? 'danger' : 'primary'));
            if ($quote?->order) {
                $n->actions([Action::make('view')->label('Άνοιγμα')->url(\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $quote->order], panel: 'admin'))->markAsRead()]);
            } elseif ($quote) {
                $n->actions([Action::make('view')->label('Άνοιγμα')->url(\App\Filament\Resources\Quotes\QuoteResource::getUrl('view', ['record' => $quote], panel: 'admin'))->markAsRead()]);
            }
            $n->sendToDatabase($staff);
        } catch (Throwable $e) {
            Log::warning('Admin notification failed: '.$e->getMessage());
        }
    }

    /** @return list<string> */
    public static function recipients(): array
    {
        $raw = (string) (Setting::get('notify_emails') ?: Setting::get('email') ?: config('mail.from.address'));

        return array_values(array_filter(array_map('trim', preg_split('/[,;\s]+/', $raw)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
    }

    protected static function email(string $type, string $summary, ?User $user, ?Quote $quote, array $meta = []): void
    {
        $to = self::recipients();
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send(new CompanyAlert($type, $summary, $user, $quote, $meta));
        } catch (Throwable $e) {
            Log::warning('Company alert email failed: '.$e->getMessage());
        }
    }
}
