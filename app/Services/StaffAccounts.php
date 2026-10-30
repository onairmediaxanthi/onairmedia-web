<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\StaffInvite;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;

/** Λογαριασμοί προσωπικού: πρόσκληση, κανόνες ασφαλείας (πάντα ένας ενεργός διαχειριστής) */
class StaffAccounts
{
    /** Πρόσκληση: link ορισμού κωδικού στη διαχείριση (24 ώρες). Το 2FA ζητείται στην πρώτη σύνδεση. */
    public function invite(User $user, ?User $by): void
    {
        $token = Password::broker('admins')->createToken($user);
        $url = Filament::getPanel('admin')->getResetPasswordUrl($token, $user);
        Mail::to($user->email)->send(new StaffInvite($user, $url, $by));
    }

    public static function activeAdmins(): int
    {
        return User::query()->where('role', UserRole::Admin->value)->whereNull('disabled_at')->count();
    }

    /** Ελέγχει μια αλλαγή πριν γίνει. Πετάει εξήγηση αν αφήνει τη σελίδα χωρίς διαχειριστή ή κλειδώνει τον ίδιο. */
    public function guard(User $target, ?User $actor, ?UserRole $newRole = null, bool $disable = false, bool $delete = false): void
    {
        $isSelf = $actor && $actor->is($target);
        if ($isSelf && ($disable || $delete)) {
            throw new InvalidArgumentException('Δεν μπορείτε να απενεργοποιήσετε ή να διαγράψετε τον δικό σας λογαριασμό.');
        }
        if ($isSelf && $newRole && $newRole !== $target->role) {
            throw new InvalidArgumentException('Δεν μπορείτε να αλλάξετε τον δικό σας ρόλο.');
        }
        $losesAdmin = $target->role === UserRole::Admin && $target->disabled_at === null
            && ($disable || $delete || ($newRole && $newRole !== UserRole::Admin));
        if ($losesAdmin && self::activeAdmins() <= 1) {
            throw new InvalidArgumentException('Πρέπει να υπάρχει πάντα τουλάχιστον ένας ενεργός διαχειριστής.');
        }
    }
}
