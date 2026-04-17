<?php

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Currency;
use App\Models\Flight;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

/**
 * Seed Qanot Sharq airline + TAS→IST weekly flights (Apr 27 → Jun 29, 2026).
 *
 * Source data (provided by ops): 10 flights, 20 seats each, Soft Block 350 USD.
 * Flight number and exact departure/arrival times were not supplied — using
 * reasonable placeholders; update via admin once confirmed.
 *
 * Airline IATA code "HH" is the commonly-cited code for Qanot Sharq; verify
 * with the carrier. Migration is idempotent: existing airline is reused.
 */
return new class extends Migration
{
    public function up(): void
    {
        $airline = Airline::firstOrCreate(
            ['code' => 'HH'],
            ['name' => 'Qanot Sharq', 'is_active' => true, 'baggage_fee' => 0]
        );

        $tas = Airport::where('code', 'TAS')->first();
        $ist = Airport::where('code', 'IST')->first();
        $usd = Currency::where('code', 'USD')->first();

        if (! $tas || ! $ist || ! $usd) {
            // Base reference data missing — skip rather than fail the migration.
            return;
        }

        $dates = [
            '2026-04-27', '2026-05-04', '2026-05-11', '2026-05-18', '2026-05-25',
            '2026-06-01', '2026-06-08', '2026-06-15', '2026-06-22', '2026-06-29',
        ];

        foreach ($dates as $date) {
            Flight::updateOrCreate(
                [
                    'airline_id' => $airline->id,
                    'flight_number' => 'HH 7501',
                    'departure_date' => $date,
                ],
                [
                    'from_airport_id' => $tas->id,
                    'to_airport_id' => $ist->id,
                    'origin_city_id' => $tas->city_id,
                    'destination_city_id' => $ist->city_id,
                    'departure_time' => '10:00:00',
                    'arrival_date' => $date,
                    'arrival_time' => '13:00:00',
                    'price_adult' => 350,
                    'price_child' => 350,
                    'price_infant' => 0,
                    'currency_id' => $usd->id,
                    'available_seats' => 20,
                    'class_type' => 'economy',
                    'soft_block_price' => 350,
                    'soft_block_release_days' => 14,
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        $airline = Airline::where('code', 'HH')->first();
        if (! $airline) {
            return;
        }

        Flight::where('airline_id', $airline->id)
            ->whereIn('departure_date', [
                '2026-04-27', '2026-05-04', '2026-05-11', '2026-05-18', '2026-05-25',
                '2026-06-01', '2026-06-08', '2026-06-15', '2026-06-22', '2026-06-29',
            ])
            ->delete();

        // Only delete the airline if no flights remain for it.
        if (Flight::where('airline_id', $airline->id)->doesntExist()) {
            $airline->delete();
        }
    }
};
