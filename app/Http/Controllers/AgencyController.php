<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgencyController extends Controller
{
    /**
     * Display agency profile.
     */
    public function profile(): View
    {
        $agency = Auth::user()->agency;
        return view('agency.profile', compact('agency'));
    }

    /**
     * Display agency employees.
     */
    public function employees(): View
    {
        $agency = Auth::user()->agency;
        $employees = $agency->users;
        return view('agency.employees', compact('agency', 'employees'));
    }
}
