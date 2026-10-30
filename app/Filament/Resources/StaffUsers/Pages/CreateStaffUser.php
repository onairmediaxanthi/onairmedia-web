<?php

namespace App\Filament\Resources\StaffUsers\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\StaffUsers\StaffUserResource;
use App\Models\User;
use App\Services\StaffAccounts;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateStaffUser extends CreateRecord
{
    protected static string $resource = StaffUserResource::class;

    protected static ?string $title = 'Νέος χρήστης διαχείρισης';

    protected function handleRecordCreation(array $data): Model
    {
        $user = new User(['name' => $data['name'], 'email' => mb_strtolower(trim($data['email'])), 'password' => Str::password(40)]);
        $user->role = UserRole::from($data['role']);
        $user->email_verified_at = now();
        $user->save();

        if ($data['send_invite'] ?? false) {
            app(StaffAccounts::class)->invite($user, Filament::auth()->user());
        }

        return $user;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Ο χρήστης δημιουργήθηκε'.(($this->data['send_invite'] ?? false) ? ' και του στάλθηκε πρόσκληση' : '');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
