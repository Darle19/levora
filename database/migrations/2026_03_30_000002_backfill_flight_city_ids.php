<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill origin_city_id from from_airport_id → airports.city_id
        DB::statement('
            UPDATE flights
            SET origin_city_id = (
                SELECT airports.city_id FROM airports WHERE airports.id = flights.from_airport_id
            )
            WHERE origin_city_id IS NULL AND from_airport_id IS NOT NULL
        ');

        // Backfill destination_city_id from to_airport_id → airports.city_id
        DB::statement('
            UPDATE flights
            SET destination_city_id = (
                SELECT airports.city_id FROM airports WHERE airports.id = flights.to_airport_id
            )
            WHERE destination_city_id IS NULL AND to_airport_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::table('flights')->update([
            'origin_city_id' => null,
            'destination_city_id' => null,
        ]);
    }
};
