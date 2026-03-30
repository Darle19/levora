<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->unsignedInteger('day_offset')->default(0)->after('arrival_city_id');
            $table->dropColumn(['departure_date', 'arrival_date']);
        });
    }

    public function down(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->dropColumn('day_offset');
            $table->date('departure_date')->nullable()->after('arrival_city_id');
            $table->date('arrival_date')->nullable()->after('departure_date');
        });
    }
};
