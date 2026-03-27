<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Agencies\AgencyResource;
use App\Filament\Resources\Flights\FlightResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Tours\TourResource;
use App\Models\Agency;
use App\Models\Flight;
use App\Models\Order;
use App\Models\Tour;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Active Tours
        $activeTours = Tour::where('is_available', true)->count();
        $upcomingDepartures = Tour::where('is_available', true)
            ->whereBetween('date_from', [now(), now()->addDays(14)])
            ->count();

        // Bookings This Month
        $confirmedThisMonth = Order::where('status', 'confirmed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $pendingThisMonth = Order::where('status', 'pending')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $totalBookingsThisMonth = $confirmedThisMonth + $pendingThisMonth;

        // Mini chart for bookings (last 7 days)
        $bookingChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $bookingChart[] = Order::whereDate('created_at', now()->subDays($i))->count();
        }

        // Monthly Revenue
        $monthlyRevenue = Order::where('status', 'confirmed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total_price');
        $lastMonthRevenue = Order::where('status', 'confirmed')
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->sum('total_price');
        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($monthlyRevenue > 0 ? 100 : 0);
        $revenueIcon = $revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
        $revenueColor = $revenueChange >= 0 ? 'success' : 'danger';

        // Low Inventory Flights
        $lowInventoryFlights = Flight::where('is_active', true)
            ->where('available_seats', '<', 5)
            ->count();

        // Active Agencies
        $activeAgencies = Agency::where('is_active', true)->count();

        // Pending Payments
        $pendingOrders = Order::where('status', 'pending');
        $pendingCount = $pendingOrders->count();
        $pendingAmount = $pendingOrders->sum('total_price');

        return [
            Stat::make('Active Tours', $activeTours)
                ->description($upcomingDepartures . ' departing in next 14 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . TourResource::getUrl() . '\'',
                ]),

            Stat::make('Bookings This Month', $totalBookingsThisMonth)
                ->description($confirmedThisMonth . ' confirmed, ' . $pendingThisMonth . ' pending')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->chart($bookingChart)
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . OrderResource::getUrl() . '\'',
                ]),

            Stat::make('Monthly Revenue', '$' . number_format($monthlyRevenue, 2))
                ->description(($revenueChange >= 0 ? '+' : '') . $revenueChange . '% from last month')
                ->descriptionIcon($revenueIcon)
                ->color($revenueColor)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . OrderResource::getUrl() . '\'',
                ]),

            Stat::make('Low Inventory Flights', $lowInventoryFlights)
                ->description($lowInventoryFlights > 0 ? 'Flights with < 5 seats' : 'All flights well stocked')
                ->descriptionIcon($lowInventoryFlights > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowInventoryFlights > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . FlightResource::getUrl() . '\'',
                ]),

            Stat::make('Active Agencies', $activeAgencies)
                ->description('Registered partner agencies')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . AgencyResource::getUrl() . '\'',
                ]),

            Stat::make('Pending Payments', $pendingCount)
                ->description('$' . number_format($pendingAmount, 2) . ' total pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href=\'' . OrderResource::getUrl() . '\'',
                ]),
        ];
    }
}
