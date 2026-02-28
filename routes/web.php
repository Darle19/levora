<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Search\CrossingTourSearchController;
use App\Http\Controllers\Search\CruiseSearchController;
use App\Http\Controllers\Search\ExcursionSearchController;
use App\Http\Controllers\Search\HotelSearchController;
use App\Http\Controllers\Search\TicketSearchController;
use App\Http\Controllers\Search\TourSearchController;
use Illuminate\Support\Facades\Route;

// Language switch route
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Public routes - Search is the home page (no auth required)
Route::get('/', [TourSearchController::class, 'index'])->name('home');

Route::prefix('search')->name('search.')->group(function () {
    Route::get('/tours', [TourSearchController::class, 'index'])->name('tours');
    Route::post('/tours', [TourSearchController::class, 'search'])->name('tours.search');
    Route::get('/tours/results', [TourSearchController::class, 'results'])->name('tours.results');
    Route::get('/hotels', [HotelSearchController::class, 'index'])->name('hotels');
    Route::get('/tickets', [TicketSearchController::class, 'index'])->name('tickets');
    Route::post('/tickets', [TicketSearchController::class, 'search'])->name('tickets.search');
    Route::get('/excursions', [ExcursionSearchController::class, 'index'])->name('excursions');
    Route::get('/crossing-tours', [CrossingTourSearchController::class, 'index'])->name('crossing-tours');
    Route::get('/cruises', [CruiseSearchController::class, 'index'])->name('cruises');
});

// Tour details (public)
Route::get('/tours/{tour}', [TourSearchController::class, 'show'])->name('tours.show');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/registration-pending', fn() => view('auth.pending'))->name('registration.pending');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Protected routes (auth required for booking, dashboard, agency)
Route::middleware(['auth', 'agency.active'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Booking (requires auth)
    Route::get('/tours/{tour}/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('bookings.confirmation');

    // Agency routes
    Route::prefix('agency')->name('agency.')->group(function () {
        Route::get('/profile', [AgencyController::class, 'profile'])->name('profile');
        Route::get('/employees', [AgencyController::class, 'employees'])->name('employees');
    });

    // Claims routes
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', [ClaimController::class, 'index'])->name('index');
        Route::get('/{order}', [ClaimController::class, 'show'])->name('show');
    });

    // Document download
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
});
