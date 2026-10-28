<?php

namespace App\Filament\Resources\CustomerActivities\Pages;

use App\Filament\Resources\CustomerActivities\CustomerActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerActivities extends ListRecords
{
    protected static string $resource = CustomerActivityResource::class;
}
