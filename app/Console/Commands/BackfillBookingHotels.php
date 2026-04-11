<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\FlightPath;
use App\Models\Hotel;
use Illuminate\Console\Command;

class BackfillBookingHotels extends Command
{
    protected $signature = 'bookings:backfill-hotels
                            {booking : Booking ID}
                            {hotels : Comma-separated hotel IDs in stay order (e.g. "12,45")}';

    protected $description = 'Attach hotels to an existing FlightPath booking (for old records without booking_hotels data)';

    public function handle(): int
    {
        $bookingId = (int) $this->argument('booking');
        $hotelIds = array_filter(array_map('trim', explode(',', $this->argument('hotels'))));

        $booking = Booking::with('bookable')->find($bookingId);

        if (! $booking) {
            $this->error("Booking #{$bookingId} not found.");
            return self::FAILURE;
        }

        if (! $booking->bookable instanceof FlightPath) {
            $this->error("Booking #{$bookingId} is not a FlightPath booking.");
            return self::FAILURE;
        }

        $fp = $booking->bookable;
        $fp->loadMissing('stays.city');

        if ($fp->stays->count() !== count($hotelIds)) {
            $this->error("FlightPath has {$fp->stays->count()} stays, but you provided " . count($hotelIds) . " hotel IDs.");
            return self::FAILURE;
        }

        $hotels = Hotel::with('city')->whereIn('id', $hotelIds)->get()->keyBy('id');

        foreach ($hotelIds as $id) {
            if (! $hotels->has((int) $id)) {
                $this->error("Hotel #{$id} not found.");
                return self::FAILURE;
            }
        }

        // Clear existing pivot entries
        $booking->hotels()->detach();

        $dayOffset = 0;
        foreach ($fp->stays->sortBy('stay_order') as $i => $stay) {
            $hotelId = (int) $hotelIds[$i];
            $hotel = $hotels->get($hotelId);

            if ($hotel->city_id !== $stay->city_id) {
                $this->warn("⚠ Hotel #{$hotelId} ({$hotel->city->name_en}) doesn't match stay city ({$stay->city->name_en}). Continuing anyway.");
            }

            $checkIn = $fp->departure_date->copy()->addDays($dayOffset);
            $checkOut = $checkIn->copy()->addDays($stay->nights);

            $booking->hotels()->attach($hotelId, [
                'stay_order' => $stay->stay_order,
                'nights' => $stay->nights,
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
            ]);

            $this->info("✓ Stay {$stay->stay_order}: {$hotel->name_en} in {$stay->city->name_en} — {$stay->nights} nights, {$checkIn->format('d.m.Y')} → {$checkOut->format('d.m.Y')}");

            $dayOffset += $stay->nights;
        }

        $this->info("Done. Regenerate documents to include hotels:");
        $this->line("  \$order = \\App\\Models\\Order::find({$booking->order_id});");
        $this->line("  \$order->bookings->first()->documents()->delete();");
        $this->line("  app(\\App\\Services\\DocumentGenerationService::class)->generateAllForOrder(\$order);");

        return self::SUCCESS;
    }
}
