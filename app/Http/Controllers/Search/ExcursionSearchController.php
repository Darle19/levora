<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ExcursionSearchController extends Controller
{
    /**
     * Display excursion search page.
     */
    public function index(): View
    {
        return view('search.excursions.index');
    }
}
