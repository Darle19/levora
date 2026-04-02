<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Creates tours from existing flights + hotels.
 * Requires: FlightSeeder (flights), BasicDataSeeder (reference data) to run first.
 * Uses raw DB queries — no Eloquent model dependencies.
 */
class ProductionTourSeeder extends Seeder
{
    private const IST_NIGHTS = 2;
    private const DEST_NIGHTS = 4;
    private const TOTAL_NIGHTS = 7;

    public function run(): void
    {
        // ── Settings ──
        $this->ensureSetting('tour_hidden_fee', '60', 'number', 'pricing');
        $this->ensureSetting('tour_agent_fee', '50', 'number', 'pricing');

        // ── Reference data IDs ──
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
        if (! $usdId) {
            $this->command->error('USD currency not found. Run: php artisan db:seed first.');
            return;
        }

        $mealBBId = $this->ensureRow('meal_types', ['code' => 'BB'], ['name_en' => 'Bed & Breakfast', 'name_ru' => 'Завтрак']);
        $transportId = $this->ensureRow('transport_types', ['name_en' => 'Airplane'], ['name_ru' => 'Авиа']);
        $programId = $this->ensureRow('program_types', ['name_en' => 'Standard'], ['name_ru' => 'Стандарт']);
        $tourTypeId = $this->ensureRow('tour_types', ['name_en' => 'Standard'], ['name_ru' => 'Стандарт']);
        $star3Id = $this->ensureRow('hotel_categories', ['stars' => 3], ['name' => '3 stars']);

        // ── Countries ──
        $trId = DB::table('countries')->where('name_en', 'Turkey')->value('id')
            ?? $this->ensureRow('countries', ['name_en' => 'Turkey'], ['name_ru' => 'Турция', 'code' => 'TR']);
        $frId = DB::table('countries')->where('name_en', 'France')->value('id')
            ?? $this->ensureRow('countries', ['name_en' => 'France'], ['name_ru' => 'Франция', 'code' => 'FR']);
        $azId = DB::table('countries')->where('name_en', 'Azerbaijan')->value('id')
            ?? $this->ensureRow('countries', ['name_en' => 'Azerbaijan'], ['name_ru' => 'Азербайджан', 'code' => 'AZ']);

        // ── Cities ──
        $tashkentId = DB::table('cities')->where('name_en', 'Tashkent')->value('id');
        $istanbulId = DB::table('cities')->where('name_en', 'Istanbul')->value('id')
            ?? $this->ensureRow('cities', ['name_en' => 'Istanbul'], ['name_ru' => 'Стамбул', 'country_id' => $trId]);
        $niceId = $this->ensureRow('cities', ['name_en' => 'Nice'], ['name_ru' => 'Ницца', 'country_id' => $frId]);
        $bakuId = DB::table('cities')->where('name_en', 'Baku')->value('id')
            ?? $this->ensureRow('cities', ['name_en' => 'Baku'], ['name_ru' => 'Баку', 'country_id' => $azId]);

        if (! $tashkentId) {
            $this->command->error('Tashkent city not found. Run: php artisan db:seed first.');
            return;
        }

        // ── Resorts ──
        $sultanahmetId = $this->ensureRow('resorts', ['name_en' => 'Sultanahmet'], ['name_ru' => 'Султанахмет', 'country_id' => $trId, 'city_id' => $istanbulId]);
        $fatihId = $this->ensureRow('resorts', ['name_en' => 'Fatih'], ['name_ru' => 'Фатих', 'country_id' => $trId, 'city_id' => $istanbulId]);
        $niceStadeId = $this->ensureRow('resorts', ['name_en' => 'Nice Stade'], ['name_ru' => 'Ницца Стад', 'country_id' => $frId, 'city_id' => $niceId]);
        $bakuBlvdId = $this->ensureRow('resorts', ['name_en' => 'Baku Boulevard'], ['name_ru' => 'Бакинский бульвар', 'country_id' => $azId, 'city_id' => $bakuId]);

        // ── Istanbul Hotels (price = per ROOM dbl with breakfast) ──
        $istHotels = [
            ['name' => 'Grand Liza Hotel', 'price' => 45, 'resort_id' => $fatihId],
            ['name' => 'Grand Emir Hotel', 'price' => 50, 'resort_id' => $fatihId],
            ['name' => 'All Seasons Hotel Istanbul', 'price' => 55, 'resort_id' => $fatihId],
            ['name' => 'New Emin Hotel', 'price' => 61, 'resort_id' => $sultanahmetId],
            ['name' => 'River Hotel', 'price' => 62, 'resort_id' => $fatihId],
            ['name' => 'Grand Washington Hotel', 'price' => 75, 'resort_id' => $sultanahmetId],
            ['name' => 'Sorisso Hotel', 'price' => 75, 'resort_id' => $sultanahmetId],
        ];

        $istHotelIds = [];
        foreach ($istHotels as $h) {
            $id = $this->ensureHotel($h['name'], $h['resort_id'], $star3Id, $h['price'], $usdId);
            $istHotelIds[] = ['id' => $id, 'resort_id' => $h['resort_id'], 'price' => $h['price']];
        }

        // ── Nice Hotel ──
        $niceHotelId = $this->ensureHotel('B&B HOTEL Nice Stade Riviera 3 étoiles', $niceStadeId, $star3Id, 110, $usdId);

        // ── Baku Hotel ──
        $nobelHotelId = $this->ensureHotel('Nobel Hotel', $bakuBlvdId, $star3Id, 50, $usdId);

        // ── Get TAS→IST flight dates (tours only on these dates) ──
        $tasAirportId = DB::table('airports')->where('code', 'TAS')->value('id');
        $istAirportId = DB::table('airports')->where('code', 'IST')->value('id');

        $tasIstFlights = DB::table('flights')
            ->where('from_airport_id', $tasAirportId)
            ->where('to_airport_id', $istAirportId)
            ->where('is_active', true)
            ->orderBy('departure_date')
            ->get();

        if ($tasIstFlights->isEmpty()) {
            $this->command->error('No TAS→IST flights found. Run: php artisan db:seed --class=FlightSeeder first.');
            return;
        }

        $this->command->info("Found {$tasIstFlights->count()} TAS→IST flights.");

        // ── GYD→TAS flights for return legs ──
        $gydAirportId = DB::table('airports')->where('code', 'GYD')->value('id');
        $gydTasFlights = DB::table('flights')
            ->where('from_airport_id', $gydAirportId)
            ->where('to_airport_id', $tasAirportId)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($f) => $f->departure_date);

        // ── Lookup IST↔NCE and IST↔GYD flights (keyed by departure_date) ──
        $nceAirportId = DB::table('airports')->where('code', 'NCE')->value('id');

        $istNceFlights = DB::table('flights')
            ->where('from_airport_id', $istAirportId)->where('to_airport_id', $nceAirportId)
            ->where('is_active', true)->get()->keyBy(fn ($f) => $f->departure_date);

        $nceIstFlights = DB::table('flights')
            ->where('from_airport_id', $nceAirportId)->where('to_airport_id', $istAirportId)
            ->where('is_active', true)->get()->keyBy(fn ($f) => $f->departure_date);

        $istGydFlights = DB::table('flights')
            ->where('from_airport_id', $istAirportId)->where('to_airport_id', $gydAirportId)
            ->where('is_active', true)->get()->keyBy(fn ($f) => $f->departure_date);

        $gydIstFlights = DB::table('flights')
            ->where('from_airport_id', $gydAirportId)->where('to_airport_id', $istAirportId)
            ->where('is_active', true)->get()->keyBy(fn ($f) => $f->departure_date);

        // ── Generate Istanbul+Nice tours ──
        $niceCount = 0;
        foreach ($tasIstFlights as $outbound) {
            $depDate = $outbound->departure_date;
            $istNceDate = date('Y-m-d', strtotime($depDate . ' +2 days'));  // IST→NCE: day+2
            $nceIstDate = date('Y-m-d', strtotime($depDate . ' +6 days'));  // NCE→IST: day+6

            foreach ($istHotelIds as $istH) {
                $exists = DB::table('tours')
                    ->where('date_from', $depDate)
                    ->where('hotel_id', $niceHotelId)
                    ->whereExists(function ($q) use ($istH) {
                        $q->select(DB::raw(1))->from('tour_stays')
                            ->whereColumn('tour_stays.tour_id', 'tours.id')
                            ->where('tour_stays.hotel_id', $istH['id']);
                    })
                    ->exists();
                if ($exists) { continue; }

                $tourId = DB::table('tours')->insertGetId([
                    'tour_type_id' => $tourTypeId,
                    'program_type_id' => $programId,
                    'country_id' => $frId,
                    'resort_id' => $niceStadeId,
                    'hotel_id' => $niceHotelId,
                    'transport_type_id' => $transportId,
                    'departure_city_id' => $tashkentId,
                    'nights' => self::TOTAL_NIGHTS,
                    'price' => 0,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => date('Y-m-d', strtotime($depDate . ' +' . self::TOTAL_NIGHTS . ' days')),
                    'adults' => 2,
                    'children' => 0,
                    'meal_type_id' => $mealBBId,
                    'is_available' => true,
                    'is_hot' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Stays
                DB::table('tour_stays')->insert([
                    ['tour_id' => $tourId, 'stay_order' => 1, 'city_id' => $istanbulId, 'resort_id' => $istH['resort_id'], 'hotel_id' => $istH['id'], 'nights' => self::IST_NIGHTS, 'meal_type_id' => $mealBBId, 'created_at' => now(), 'updated_at' => now()],
                    ['tour_id' => $tourId, 'stay_order' => 2, 'city_id' => $niceId, 'resort_id' => $niceStadeId, 'hotel_id' => $niceHotelId, 'nights' => self::DEST_NIGHTS, 'meal_type_id' => $mealBBId, 'created_at' => now(), 'updated_at' => now()],
                ]);

                // Attach flights: TAS→IST (leg 1), IST→NCE (leg 2), NCE→IST (leg 3)
                DB::table('tour_flight')->insert([
                    'tour_id' => $tourId, 'flight_id' => $outbound->id, 'direction' => 'outbound', 'leg_order' => 1,
                ]);
                $istNceFlight = $istNceFlights[$istNceDate] ?? null;
                if ($istNceFlight) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $istNceFlight->id, 'direction' => 'outbound', 'leg_order' => 2,
                    ]);
                }
                $nceIstFlight = $nceIstFlights[$nceIstDate] ?? null;
                if ($nceIstFlight) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $nceIstFlight->id, 'direction' => 'return', 'leg_order' => 3,
                    ]);
                }

                $niceCount++;
            }
        }
        $this->command->info("Created {$niceCount} Istanbul+Nice tours.");

        // ── Generate Istanbul+Baku tours ──
        $bakuCount = 0;
        foreach ($tasIstFlights as $outbound) {
            $depDate = $outbound->departure_date;
            $istGydDate = date('Y-m-d', strtotime($depDate . ' +2 days'));  // IST→GYD: day+2
            $gydIstDate = date('Y-m-d', strtotime($depDate . ' +6 days'));  // GYD→IST: day+6
            $returnDate = date('Y-m-d', strtotime($depDate . ' +' . self::TOTAL_NIGHTS . ' days'));
            $returnFlight = $gydTasFlights[$returnDate] ?? null;

            foreach ($istHotelIds as $istH) {
                $exists = DB::table('tours')
                    ->where('date_from', $depDate)
                    ->where('hotel_id', $nobelHotelId)
                    ->whereExists(function ($q) use ($istH) {
                        $q->select(DB::raw(1))->from('tour_stays')
                            ->whereColumn('tour_stays.tour_id', 'tours.id')
                            ->where('tour_stays.hotel_id', $istH['id']);
                    })
                    ->exists();
                if ($exists) { continue; }

                $tourId = DB::table('tours')->insertGetId([
                    'tour_type_id' => $tourTypeId,
                    'program_type_id' => $programId,
                    'country_id' => $azId,
                    'resort_id' => $bakuBlvdId,
                    'hotel_id' => $nobelHotelId,
                    'transport_type_id' => $transportId,
                    'departure_city_id' => $tashkentId,
                    'nights' => self::TOTAL_NIGHTS,
                    'price' => 0,
                    'currency_id' => $usdId,
                    'date_from' => $depDate,
                    'date_to' => $returnDate,
                    'adults' => 2,
                    'children' => 0,
                    'meal_type_id' => $mealBBId,
                    'is_available' => true,
                    'is_hot' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Stays
                DB::table('tour_stays')->insert([
                    ['tour_id' => $tourId, 'stay_order' => 1, 'city_id' => $istanbulId, 'resort_id' => $istH['resort_id'], 'hotel_id' => $istH['id'], 'nights' => self::IST_NIGHTS, 'meal_type_id' => $mealBBId, 'created_at' => now(), 'updated_at' => now()],
                    ['tour_id' => $tourId, 'stay_order' => 2, 'city_id' => $bakuId, 'resort_id' => $bakuBlvdId, 'hotel_id' => $nobelHotelId, 'nights' => self::DEST_NIGHTS, 'meal_type_id' => $mealBBId, 'created_at' => now(), 'updated_at' => now()],
                ]);

                // Attach flights: TAS→IST (leg 1), IST→GYD (leg 2), GYD→IST (leg 3), GYD→TAS (leg 4)
                DB::table('tour_flight')->insert([
                    'tour_id' => $tourId, 'flight_id' => $outbound->id, 'direction' => 'outbound', 'leg_order' => 1,
                ]);
                $istGydFlight = $istGydFlights[$istGydDate] ?? null;
                if ($istGydFlight) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $istGydFlight->id, 'direction' => 'outbound', 'leg_order' => 2,
                    ]);
                }
                $gydIstFlight = $gydIstFlights[$gydIstDate] ?? null;
                if ($gydIstFlight) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $gydIstFlight->id, 'direction' => 'return', 'leg_order' => 3,
                    ]);
                }
                if ($returnFlight) {
                    DB::table('tour_flight')->insert([
                        'tour_id' => $tourId, 'flight_id' => $returnFlight->id, 'direction' => 'return', 'leg_order' => 4,
                    ]);
                }

                $bakuCount++;
            }
        }
        $this->command->info("Created {$bakuCount} Istanbul+Baku tours.");

        // Prices are dynamic — no recalculation needed

        DB::table('cache')->truncate();
    }

    private function ensureSetting(string $key, string $value, string $type, string $group): void
    {
        if (! DB::table('settings')->where('key', $key)->exists()) {
            DB::table('settings')->insert([
                'key' => $key, 'value' => $value, 'type' => $type, 'group' => $group,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function ensureRow(string $table, array $where, array $extra = []): int
    {
        $row = DB::table($table)->where($where)->first();
        if ($row) {
            return $row->id;
        }

        return DB::table($table)->insertGetId(array_merge($where, $extra, [
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function ensureHotel(string $name, int $resortId, int $categoryId, float $price, int $currencyId): int
    {
        $row = DB::table('hotels')->where('name', $name)->first();
        if ($row) {
            DB::table('hotels')->where('id', $row->id)->update(['price_per_person' => $price]);
            return $row->id;
        }

        return DB::table('hotels')->insertGetId([
            'name' => $name, 'name_en' => $name, 'resort_id' => $resortId,
            'hotel_category_id' => $categoryId, 'rating' => 3.5, 'is_active' => true,
            'price_per_person' => $price, 'currency_id' => $currencyId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
