<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Agencies\AgencyResource;
use App\Filament\Resources\Flights\FlightResource;
use App\Filament\Resources\Hotels\HotelResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Tours\TourResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Agency;
use App\Models\Flight;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Tour;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $newUsersThisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();

        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $confirmedOrders = Order::where('status', 'confirmed')->count();

        $totalRevenue = Order::where('status', 'confirmed')->sum('total_price');
        $monthlyRevenue = Order::where('status', 'confirmed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_price');

        $activeTours = Tour::where('is_available', true)->count();
        $hotDeals = Tour::where('is_hot', true)->where('is_available', true)->count();

        $activeAgencies = Agency::where('is_active', true)->count();

        $activeFlights = Flight::where('is_active', true)->count();
        $activeHotels = Hotel::where('is_active', true)->count();

        // Build mini charts from last 7 days
        $orderChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $orderChart[] = Order::whereDate('created_at', now()->subDays($i))->count();
        }

        $userChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $userChart[] = User::whereDate('created_at', now()->subDays($i))->count();
        }

        return [
            Stat::make('Total Users', $totalUsers)
                ->description($newUsersThisMonth . ' new this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($userChart)
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . UserResource::getUrl() . '\'',
                ]),

            Stat::make('Active Tours', $activeTours)
                ->description($hotDeals . ' hot deals')
                ->descriptionIcon('heroicon-m-fire')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . TourResource::getUrl() . '\'',
                ]),

            Stat::make('Total Orders', $totalOrders)
                ->description($pendingOrders . ' pending, ' . $confirmedOrders . ' confirmed')
                ->chart($orderChart)
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . OrderResource::getUrl() . '\'',
                ]),

            Stat::make('Revenue', '$' . number_format($totalRevenue, 2))
                ->description('$' . number_format($monthlyRevenue, 2) . ' this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Active Agencies', $activeAgencies)
                ->description('Registered agencies')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . AgencyResource::getUrl() . '\'',
                ]),

            Stat::make('Flights & Hotels', $activeFlights . ' / ' . $activeHotels)
                ->description($activeFlights . ' flights, ' . $activeHotels . ' hotels')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . FlightResource::getUrl() . '\'',
                ]),
        ];
    }
}
