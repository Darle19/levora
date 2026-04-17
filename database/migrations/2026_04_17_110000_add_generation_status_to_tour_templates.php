<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track async FlightPath generation runs on each TourTemplate so the admin
 * UI can show live status without blocking on RapidAPI latency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->string('generation_status')->nullable();
            $table->json('generation_summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->dropColumn(['generation_status', 'generation_summary']);
        });
    }
};
