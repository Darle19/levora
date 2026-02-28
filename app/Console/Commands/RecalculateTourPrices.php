<?php

namespace App\Console\Commands;

use App\Models\Tour;
use App\Services\TourPricingService;
use Illuminate\Console\Command;

class RecalculateTourPrices extends Command
{
    protected $signature = 'tours:recalculate-prices {--tour= : Specific tour ID}';

    protected $description = 'Recalculate tour prices based on hotel + flight + markup formula';

    public function handle(TourPricingService $service): int
    {
        if ($tourId = $this->option('tour')) {
            $tour = Tour::findOrFail($tourId);
            $price = $service->recalculate($tour);

            if ($price !== null) {
                $this->info("Tour #{$tourId} price recalculated: {$price}");
            } else {
                $this->warn("Tour #{$tourId}: insufficient data for price calculation (hotel price or flights missing).");
            }
        } else {
            $count = $service->recalculateAll();
            $this->info("{$count} tour prices recalculated.");
        }

        return 0;
    }
}
