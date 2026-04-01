<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('min_nights');
            $table->unsignedInteger('max_nights');
            $table->decimal('commission', 8, 2);
            $table->timestamps();
        });

        // Default tiers: 1-3=$35, 4-7=$40, 8+=$45
        DB::table('hotel_commission_tiers')->insert([
            ['min_nights' => 1, 'max_nights' => 3, 'commission' => 35, 'created_at' => now(), 'updated_at' => now()],
            ['min_nights' => 4, 'max_nights' => 7, 'commission' => 40, 'created_at' => now(), 'updated_at' => now()],
            ['min_nights' => 8, 'max_nights' => 999, 'commission' => 45, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_commission_tiers');
    }
};
