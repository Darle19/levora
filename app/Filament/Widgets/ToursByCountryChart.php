<?php

namespace App\Filament\Widgets;

use App\Models\Tour;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ToursByCountryChart extends ChartWidget
{
    protected ?string $heading = 'Tours by Country';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $data = Tour::where('is_available', true)
            ->join('countries', 'tours.country_id', '=', 'countries.id')
            ->select('countries.name_en as country', DB::raw('count(*) as total'))
            ->groupBy('countries.name_en')
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
                    'label' => 'Tours',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
            'labels' => $data->pluck('country')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
