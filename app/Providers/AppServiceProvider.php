<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TourTemplateLeg;
use App\Observers\FlightObserver;
use App\Observers\HotelObserver;
use App\Observers\PaymentObserver;
use App\Observers\TourTemplateLegObserver;
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
        $this->app->singleton(\App\Services\RapidApiFlightService::class, function ($app) {
            return new \App\Services\RapidApiFlightService(
                apiKey: config('services.rapidapi.key', ''),
            );
        });

        $this->app->bind(
            \App\Contracts\FlightProviderInterface::class,
            config('tour.flight_provider', \App\Services\Flights\DummyFlightProvider::class),
        );

        $this->app->singleton(\App\Services\Flights\RapidApiFlightProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Filament's JS date picker everywhere instead of browser native
        \Filament\Forms\Components\DatePicker::configureUsing(fn ($component) => $component->native(false)->displayFormat('d M Y'));
        \Filament\Forms\Components\DateTimePicker::configureUsing(fn ($component) => $component->native(false)->displayFormat('d M Y H:i'));

        Hotel::observe(HotelObserver::class);
        Flight::observe(FlightObserver::class);
        Payment::observe(PaymentObserver::class);
        Booking::observe(\App\Observers\BookingObserver::class);
        TourTemplateLeg::observe(TourTemplateLegObserver::class);

        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);

        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
