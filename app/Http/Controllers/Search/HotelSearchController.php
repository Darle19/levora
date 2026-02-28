<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HotelSearchController extends Controller
{
    /**
     * Display hotel search page.
     */
    public function index(): View
    {
        return view('search.hotels.index');
    }
}
