<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('resort_id')->constrained('cities')->nullOnDelete();
        });

        // Populate city_id from resort's city_id
        DB::statement('UPDATE hotels SET city_id = (SELECT city_id FROM resorts WHERE resorts.id = hotels.resort_id) WHERE resort_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
