<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_amadeus_segments', function (Blueprint $table) {
            $table->unsignedSmallInteger('offset_days')->default(0)->after('destination_airport_id');
        });

        // Update existing segments with correct offsets:
        // leg_order 2 (IST→NCE/DPS) = depart after 2 nights in IST, offset = 2
        // leg_order 3 (NCE/DPS→IST) = depart after 4 nights in Nice, offset = 6
        DB::table('tour_amadeus_segments')->where('leg_order', 2)->update(['offset_days' => 2]);
        DB::table('tour_amadeus_segments')->where('leg_order', 3)->update(['offset_days' => 6]);

        // Fix Nice stays: 4 nights not 5
        $amadeusToursIds = DB::table('tour_amadeus_segments')->distinct()->pluck('tour_id');
        $niceCityId = DB::table('cities')->where('name_en', 'Nice')->value('id');
        if ($niceCityId && $amadeusToursIds->isNotEmpty()) {
            DB::table('tour_stays')
                ->whereIn('tour_id', $amadeusToursIds)
                ->where('stay_order', 2)
                ->where('city_id', $niceCityId)
                ->update(['nights' => 4]);
        }
    }

    public function down(): void
    {
        Schema::table('tour_amadeus_segments', function (Blueprint $table) {
            $table->dropColumn('offset_days');
        });
    }
};
