<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tour;
use App\Observers\FlightObserver;
use App\Observers\HotelObserver;
use App\Observers\PaymentObserver;
use App\Observers\TourObserver;
use App\Policies\BookingPolicy;
use App\Policies\OrderPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Hotel::observe(HotelObserver::class);
        Flight::observe(FlightObserver::class);
        Tour::observe(TourObserver::class);
        Payment::observe(PaymentObserver::class);

        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
