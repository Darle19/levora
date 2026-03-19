<?php

use App\Models\Hotel;
use App\Models\HotelGroupOffer;
use App\Models\TourStay;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Grand Emir Hotel (Sultanahmet, Istanbul, 4 stars)
        $grandEmir = Hotel::firstOrCreate(
            ['name' => 'Grand Emir Hotel'],
            [
                'name_en' => 'Grand Emir Hotel',
                'name_ru' => 'Гранд Эмир Отель',
                'name_uz' => 'Grand Emir Hotel',
                'description' => '4-star hotel in Sultanahmet, Istanbul',
                'address' => 'Sultanahmet, Istanbul, Turkey',
                'resort_id' => DB::table('resorts')->where('name_en', 'Sultanahmet')->value('id'),
                'hotel_category_id' => DB::table('hotel_categories')->where('stars', 4)->value('id') ?? 2,
                'rating' => 4.2,
                'is_active' => true,
                'price_per_person' => 43.00,
                'currency_id' => DB::table('currencies')->where('code', 'EUR')->value('id') ?? 3,
            ]
        );

        // 2. All Seasons Hotel Istanbul (Fatih, Istanbul, 3 stars)
        $allSeasons = Hotel::firstOrCreate(
            ['name' => 'All Seasons Hotel Istanbul'],
            [
                'name_en' => 'All Seasons Hotel Istanbul',
                'name_ru' => 'All Seasons Hotel Стамбул',
                'name_uz' => 'All Seasons Hotel Istanbul',
                'description' => '3-star hotel in Fatih, Istanbul',
                'address' => 'Fatih, Istanbul, Turkey',
                'resort_id' => DB::table('resorts')->where('name_en', 'Fatih')->value('id'),
                'hotel_category_id' => DB::table('hotel_categories')->where('stars', 3)->value('id') ?? 3,
                'rating' => 3.8,
                'is_active' => true,
                'price_per_person' => 55.00,
                'currency_id' => DB::table('currencies')->where('code', 'USD')->value('id') ?? 1,
            ]
        );

        $eurId = DB::table('currencies')->where('code', 'EUR')->value('id') ?? 3;
        $usdId = DB::table('currencies')->where('code', 'USD')->value('id') ?? 1;
        $dates = ['13.04.2026','20.04.2026','27.04.2026','04.05.2026','11.05.2026','18.05.2026','01.06.2026','08.06.2026','15.06.2026','22.06.2026','29.06.2026'];
        $policy = 'Non-refundable. Prepayment required. No cancellation, refund, or modification allowed.';

        // 3. Group Offer: Grand Emir (EUR rates)
        HotelGroupOffer::firstOrCreate(
            ['hotel_id' => $grandEmir->id, 'title' => 'Series Group Offer - April-June 2026'],
            [
                'check_in_dates' => $dates,
                'nights' => 6,
                'pax_count' => 30,
                'rooms_count' => 15,
                'rooms_booked' => 0,
                'room_configuration' => '15 double rooms - French bed',
                'nationality' => 'Uzbek',
                'rate_tiers' => [
                    ['description' => 'April', 'rate' => '43.00'],
                    ['description' => 'May', 'rate' => '53.00'],
                    ['description' => 'June', 'rate' => '55.00'],
                ],
                'currency_id' => $eurId,
                'cancellation_policy' => $policy,
                'is_active' => true,
            ]
        );

        // 4. Group Offer: All Seasons Istanbul (USD rates)
        HotelGroupOffer::firstOrCreate(
            ['hotel_id' => $allSeasons->id, 'title' => 'Series Group Offer - April-June 2026'],
            [
                'check_in_dates' => $dates,
                'nights' => 6,
                'pax_count' => 30,
                'rooms_count' => 15,
                'rooms_booked' => 0,
                'room_configuration' => '15 double rooms - French bed',
                'nationality' => 'Uzbek',
                'rate_tiers' => [
                    ['description' => 'April', 'rate' => '55.00'],
                    ['description' => 'May', 'rate' => '60.00'],
                    ['description' => 'June', 'rate' => '65.00'],
                ],
                'currency_id' => $usdId,
                'cancellation_policy' => $policy,
                'is_active' => true,
            ]
        );

        // 5. Assign Grand Emir to Istanbul stays that have no hotel
        TourStay::where('stay_order', 1)
            ->where('city_id', DB::table('cities')->where('name_en', 'Istanbul')->value('id'))
            ->whereNull('hotel_id')
            ->update(['hotel_id' => $grandEmir->id]);
    }

    public function down(): void
    {
        $grandEmirId = DB::table('hotels')->where('name', 'Grand Emir Hotel')->value('id');
        $allSeasonsId = DB::table('hotels')->where('name', 'All Seasons Hotel Istanbul')->value('id');

        if ($grandEmirId) {
            DB::table('hotel_group_offers')->where('hotel_id', $grandEmirId)->delete();
            TourStay::where('hotel_id', $grandEmirId)->update(['hotel_id' => null]);
            DB::table('hotels')->where('id', $grandEmirId)->delete();
        }
        if ($allSeasonsId) {
            DB::table('hotel_group_offers')->where('hotel_id', $allSeasonsId)->delete();
            DB::table('hotels')->where('id', $allSeasonsId)->delete();
        }
    }
};
