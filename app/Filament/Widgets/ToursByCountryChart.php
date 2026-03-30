<?php

namespace App\Filament\Widgets;

use App\Models\FlightPath;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ToursByCountryChart extends ChartWidget
{
    protected ?string $heading = 'Tours by Route';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $data = FlightPath::where('is_available', true)
            ->select('route_name', DB::raw('count(*) as total'))
            ->groupBy('route_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $colors = [
            '#0ea5e9', '#8b5cf6', '#f59e0b', '#10b981',
            '#ef4444', '#ec4899', '#6366f1', '#14b8a6',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Flight Paths',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
            'labels' => $data->pluck('route_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
