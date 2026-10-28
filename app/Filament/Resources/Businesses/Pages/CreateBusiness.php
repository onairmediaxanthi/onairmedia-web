<?php

namespace App\Filament\Resources\Businesses\Pages;

use App\Filament\Resources\Businesses\BusinessResource;
use App\Models\Business;
use App\Models\User;
use App\Services\CustomerInvites;
use App\Services\CustomerEvents;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Νέος πελάτης από το προσωπικό: φτιάχνεται και ο λογαριασμός του, χωρίς κωδικό μέχρι την ενεργοποίηση */
class CreateBusiness extends CreateRecord
{
    protected static string $resource = BusinessResource::class;

    protected static ?string $title = 'Νέος πελάτης';

    protected function handleRecordCreation(array $data): Model
    {
        $email = mb_strtolower(trim($data['account_email']));
        $invite = (bool) ($data['send_invite'] ?? false);
        unset($data['account_email'], $data['send_invite']);
        $staff = Filament::auth()->user();

        $business = DB::transaction(function () use ($data, $email, $staff) {
            $user = new User(['name' => $data['contact_person'], 'email' => $email, 'password' => Str::password(40)]);
            $user->save(); // ρόλος: customer (προεπιλογή)

            $business = new Business($data);
            $business->source = 'staff';
            $business->created_by_id = $staff?->id;
            $user->business()->save($business);

            return $business;
        });

        CustomerEvents::record('staff_created', $business->user->load('business'), null, ['by' => $staff?->name]);
        if ($invite) {
            app(CustomerInvites::class)->send($business);
        }

        return $business;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
