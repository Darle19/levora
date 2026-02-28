<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CrossingTourSearchController extends Controller
{
    /**
     * Display crossing tour search page.
     */
    public function index(): View
    {
        return view('search.crossing-tours.index');
    }
}
