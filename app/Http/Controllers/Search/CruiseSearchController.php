<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CruiseSearchController extends Controller
{
    /**
     * Display cruise search page.
     */
    public function index(): View
    {
        return view('search.cruises.index');
    }
}
