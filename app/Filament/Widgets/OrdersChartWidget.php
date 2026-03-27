<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue & Bookings (Last 6 Months)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $labels = [];
        $revenueData = [];
        $bookingData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $revenueData[] = (float) Order::where('status', 'confirmed')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('total_price');

            $bookingData[] = Order::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenueData,
                    'type' => 'bar',
                    'backgroundColor' => 'rgba(27, 107, 46, 0.7)',
                    'borderColor' => '#1B6B2E',
                    'borderWidth' => 1,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Bookings',
                    'data' => $bookingData,
                    'type' => 'line',
                    'borderColor' => '#366383',
                    'backgroundColor' => 'rgba(54, 99, 131, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => '(value) => "$" + value.toLocaleString()',
                    ],
                ],
                'y1' => [
                    'position' => 'right',
                    'beginAtZero' => true,
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}
