<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if locale is stored in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');

            // Validate locale
            if (in_array($locale, ['en', 'ru', 'uz'])) {
                App::setLocale($locale);
            }
        }

        return $next($request);
    }
}
