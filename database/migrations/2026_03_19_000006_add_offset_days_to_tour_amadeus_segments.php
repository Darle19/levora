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
        // leg_order 2 (IST→NCE/DPS) = day 3, offset = 2
        // leg_order 3 (NCE/DPS→IST) = day 8, offset = 7
        DB::table('tour_amadeus_segments')->where('leg_order', 2)->update(['offset_days' => 2]);
        DB::table('tour_amadeus_segments')->where('leg_order', 3)->update(['offset_days' => 7]);
    }

    public function down(): void
    {
        Schema::table('tour_amadeus_segments', function (Blueprint $table) {
            $table->dropColumn('offset_days');
        });
    }
};
