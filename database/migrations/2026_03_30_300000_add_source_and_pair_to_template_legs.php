<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->string('flight_source', 20)->default('local_db')->after('passenger_count');
            $table->foreignId('round_trip_pair_id')->nullable()->after('flight_source')
                ->constrained('tour_template_legs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->dropForeign(['round_trip_pair_id']);
            $table->dropColumn(['flight_source', 'round_trip_pair_id']);
        });
    }
};
