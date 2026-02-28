<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tours — most queried table, all filter columns need indexes
        Schema::table('tours', function (Blueprint $table) {
            $table->index('country_id');
            $table->index('resort_id');
            $table->index('hotel_id');
            $table->index('departure_city_id');
            $table->index('tour_type_id');
            $table->index('program_type_id');
            $table->index('transport_type_id');
            $table->index('meal_type_id');
            $table->index('date_from');
            $table->index('price');
            $table->index('is_available');
            // Composite index for the most common search pattern
            $table->index(['is_available', 'country_id', 'date_from']);
        });

        // Hotels — filtered in search and used in whereHas
        Schema::table('hotels', function (Blueprint $table) {
            $table->index('resort_id');
            $table->index('hotel_category_id');
            $table->index('is_active');
        });

        // Flights — searched by airport codes and date
        Schema::table('flights', function (Blueprint $table) {
            $table->index('from_airport_id');
            $table->index('to_airport_id');
            $table->index('airline_id');
            $table->index('departure_date');
            // Composite for flight search queries
            $table->index(['from_airport_id', 'to_airport_id', 'departure_date']);
        });

        // Orders — queried by agency for claims/dashboard
        Schema::table('orders', function (Blueprint $table) {
            $table->index('agency_id');
            $table->index('user_id');
        });

        // Bookings — queried by order
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('order_id');
        });

        // Airports — looked up by IATA code
        Schema::table('airports', function (Blueprint $table) {
            $table->index('code');
        });

        // Stop sales — checked during booking
        Schema::table('stop_sales', function (Blueprint $table) {
            $table->index(['hotel_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropIndex(['country_id']);
            $table->dropIndex(['resort_id']);
            $table->dropIndex(['hotel_id']);
            $table->dropIndex(['departure_city_id']);
            $table->dropIndex(['tour_type_id']);
            $table->dropIndex(['program_type_id']);
            $table->dropIndex(['transport_type_id']);
            $table->dropIndex(['meal_type_id']);
            $table->dropIndex(['date_from']);
            $table->dropIndex(['price']);
            $table->dropIndex(['is_available']);
            $table->dropIndex(['is_available', 'country_id', 'date_from']);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropIndex(['resort_id']);
            $table->dropIndex(['hotel_category_id']);
            $table->dropIndex(['is_active']);
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->dropIndex(['from_airport_id']);
            $table->dropIndex(['to_airport_id']);
            $table->dropIndex(['airline_id']);
            $table->dropIndex(['departure_date']);
            $table->dropIndex(['from_airport_id', 'to_airport_id', 'departure_date']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['agency_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
        });

        Schema::table('airports', function (Blueprint $table) {
            $table->dropIndex(['code']);
        });

        Schema::table('stop_sales', function (Blueprint $table) {
            $table->dropIndex(['hotel_id', 'start_date', 'end_date']);
        });
    }
};
