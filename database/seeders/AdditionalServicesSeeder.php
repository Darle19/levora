<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use App\Models\Tour;
use Illuminate\Database\Seeder;

class AdditionalServicesSeeder extends Seeder
{
    public function run(): void
    {
        $usdId = 1;

        $services = [
            [
                'code' => 'GRP_TRANSFER_IN',
                'name_en' => 'Group Transfer: Airport → Hotel',
                'name_ru' => 'Групповой трансфер: аэропорт → отель',
                'name_uz' => 'Guruh transferi: aeroport → mehmonxona',
                'service_type' => 'transfer',
                'price' => 10.00,
                'currency_id' => $usdId,
                'is_per_person' => true,
                'is_active' => true,
            ],
            [
                'code' => 'GRP_TRANSFER_OUT',
                'name_en' => 'Group Transfer: Hotel → Airport',
                'name_ru' => 'Групповой трансфер: отель → аэропорт',
                'name_uz' => 'Guruh transferi: mehmonxona → aeroport',
                'service_type' => 'transfer',
                'price' => 10.00,
                'currency_id' => $usdId,
                'is_per_person' => true,
                'is_active' => true,
            ],
            [
                'code' => 'IND_TRANSFER_IN',
                'name_en' => 'Individual Transfer: Airport → Hotel',
                'name_ru' => 'Индивидуальный трансфер: аэропорт → отель',
                'name_uz' => 'Individual transfer: aeroport → mehmonxona',
                'service_type' => 'transfer',
                'price' => 45.00,
                'currency_id' => $usdId,
                'is_per_person' => false,
                'is_active' => true,
            ],
            [
                'code' => 'IND_TRANSFER_OUT',
                'name_en' => 'Individual Transfer: Hotel → Airport',
                'name_ru' => 'Индивидуальный трансфер: отель → аэропорт',
                'name_uz' => 'Individual transfer: mehmonxona → aeroport',
                'service_type' => 'transfer',
                'price' => 45.00,
                'currency_id' => $usdId,
                'is_per_person' => false,
                'is_active' => true,
            ],
            [
                'code' => 'CITY_TOUR',
                'name_en' => 'City Sightseeing Tour',
                'name_ru' => 'Обзорная экскурсия по городу',
                'name_uz' => 'Shahar bo\'ylab sayohat',
                'service_type' => 'excursion',
                'price' => 35.00,
                'currency_id' => $usdId,
                'is_per_person' => true,
                'is_active' => true,
            ],
            [
                'code' => 'TRAVEL_INSURANCE',
                'name_en' => 'Travel Insurance (10,000 USD coverage)',
                'name_ru' => 'Страхование путешественника (покрытие 10,000 USD)',
                'name_uz' => 'Sayohat sug\'urtasi (10,000 USD qoplama)',
                'service_type' => 'insurance',
                'price' => 15.00,
                'currency_id' => $usdId,
                'is_per_person' => true,
                'is_active' => true,
            ],
        ];

        foreach ($services as $data) {
            AdditionalService::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('Created ' . count($services) . ' additional services.');

        // Link group transfers to all existing tours
        $grpIn = AdditionalService::where('code', 'GRP_TRANSFER_IN')->first();
        $grpOut = AdditionalService::where('code', 'GRP_TRANSFER_OUT')->first();
        $insurance = AdditionalService::where('code', 'TRAVEL_INSURANCE')->first();

        if ($grpIn && $grpOut && $insurance) {
            $tourIds = Tour::pluck('id')->toArray();
            $attached = 0;

            foreach ($tourIds as $tourId) {
                $tour = Tour::find($tourId);
                $tour->additionalServices()->syncWithoutDetaching([
                    $grpIn->id => ['is_included' => false],
                    $grpOut->id => ['is_included' => false],
                    $insurance->id => ['is_included' => false],
                ]);
                $attached++;
            }

            $this->command->info("Linked transfers + insurance to {$attached} tours.");
        }
    }
}
