<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use App\Models\Station;
use App\Services\NewsFeed;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(NewsFeed $news): View
    {
        return view('home', [
            'slides' => Slide::query()->showing()->get(),
            'stations' => Station::query()->active()->get(),
            'news' => $news->latest(7),
        ]);
    }
}
