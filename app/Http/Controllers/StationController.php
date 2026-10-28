<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StationController extends Controller
{
    public function show(Station $station): View
    {
        abort_unless($station->is_active, 404);

        return view('stations.show', [
            'station' => $station,
            // Το προφίλ γράφεται στη διαχείριση: καθαρίζουμε κάθε script/επικίνδυνο attribute
            'description' => $station->description ? Str::sanitizeHtml($station->description) : null,
            'others' => Station::query()->active()->whereKeyNot($station->getKey())->get(),
        ]);
    }
}
