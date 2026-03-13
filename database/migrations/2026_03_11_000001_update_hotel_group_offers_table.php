<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_group_offers', function (Blueprint $table) {
            $table->json('rate_tiers')->nullable()->after('nationality');
            $table->unsignedSmallInteger('rooms_booked')->default(0)->after('rooms_count');
            $table->date('date_from')->nullable()->after('check_in_dates');
            $table->date('date_to')->nullable()->after('date_from');
        });

        // Migrate existing rate_per_night data into rate_tiers
        DB::table('hotel_group_offers')->whereNotNull('rate_per_night')->orderBy('id')->each(function ($offer) {
            DB::table('hotel_group_offers')
                ->where('id', $offer->id)
                ->update([
                    'rate_tiers' => json_encode([
                        ['description' => 'Default', 'rate' => number_format((float) $offer->rate_per_night, 2, '.', '')],
                    ]),
                ]);
        });

        Schema::table('hotel_group_offers', function (Blueprint $table) {
            $table->dropColumn('rate_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_group_offers', function (Blueprint $table) {
            $table->decimal('rate_per_night', 10, 2)->after('nationality');
        });

        // Migrate first rate tier back to rate_per_night
        DB::table('hotel_group_offers')->whereNotNull('rate_tiers')->orderBy('id')->each(function ($offer) {
            $tiers = json_decode($offer->rate_tiers, true);
            $rate = ! empty($tiers) ? $tiers[0]['rate'] : 0;
            DB::table('hotel_group_offers')
                ->where('id', $offer->id)
                ->update(['rate_per_night' => $rate]);
        });

        Schema::table('hotel_group_offers', function (Blueprint $table) {
            $table->dropColumn(['rate_tiers', 'rooms_booked', 'date_from', 'date_to']);
        });
    }
};
