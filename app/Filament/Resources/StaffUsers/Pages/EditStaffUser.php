<?php

namespace App\Filament\Resources\StaffUsers\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\StaffUsers\StaffUserResource;
use App\Models\User;
use App\Services\StaffAccounts;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EditStaffUser extends EditRecord
{
    protected static string $resource = StaffUserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $role = isset($data['role']) ? UserRole::from($data['role']) : $record->role;
        try {
            app(StaffAccounts::class)->guard($record, Filament::auth()->user(), newRole: $role);
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();

            throw new Halt;
        }
        if (! $role->isStaff()) {
            throw new Halt;
        }
        $record->name = $data['name'];
        $record->email = mb_strtolower(trim($data['email']));
        $record->role = $role;
        $record->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            ...StaffUserResource::securityActions(),
            DeleteAction::make()
                ->hidden(fn (User $record) => $record->is(Filament::auth()->user()))
                ->before(function (User $record, DeleteAction $action) {
                    try {
                        app(StaffAccounts::class)->guard($record, Filament::auth()->user(), delete: true);
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                        $action->cancel();
                    }
                }),
        ];
    }
}
