<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Available languages
     */
    protected array $availableLocales = ['en', 'ru', 'uz'];

    /**
     * Switch the application language
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        // Validate the locale
        if (!in_array($locale, $this->availableLocales)) {
            $locale = 'en';
        }

        // Store in session
        Session::put('locale', $locale);

        // Set the application locale
        App::setLocale($locale);

        // Redirect back to the previous page
        return redirect()->back();
    }
}
